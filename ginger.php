<?php
/**
 * Plugin Name: NoPayn Payments
 * Plugin URI: https://nopayn.io/
 * Description: NoPayn WooCommerce plugin
 * Version: 1.0.21
 * Author: Ginger Payments
 * Author URI: https://www.gingerpayments.com/
 * License: The MIT License (MIT)
 * Text Domain: NoPayn
 * Domain Path: /languages
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Define the Plugin version
 */
define('GINGER_PLUGIN_VERSION', get_file_data(__FILE__, array('Version'), 'plugin')[0]);
define('GINGER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GINGER_PLUGIN_PATH', plugin_dir_path(__FILE__));

add_action('plugins_loaded', 'woocommerce_ginger_init', 0);

/**
 * Declare compatibility with the WooCommerce Cart and Checkout blocks.
 * Note: 'custom_order_tables' (HPOS) is intentionally not declared, the order meta
 * is still stored through the WordPress post meta API.
 */
add_action('before_woocommerce_init', function ()
{
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil'))
    {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

spl_autoload_register(function ($class)
{
    $file = str_replace('_', '-', strtolower($class));
    $filepath = untrailingslashit(plugin_dir_path(__FILE__)).'/classes/class-'.$file.'.php';
    if(!is_file($filepath)) $filepath = untrailingslashit(plugin_dir_path(__FILE__)).'/classes/Blocks/class-'.$file.'.php'; //trying to find file in Blocks subdir
    if(!is_file($filepath)) $filepath = untrailingslashit(plugin_dir_path(__FILE__)).'/interfaces/'.$class.'.php'; //trying to find file in interfaces dir
    if(!is_file($filepath)) $filepath = untrailingslashit(plugin_dir_path(__FILE__)).'/strategies/'.$class.'.php'; //trying to find file in strategies dir
    if (is_readable($filepath) && is_file($filepath)) require_once($filepath);
});

require_once(untrailingslashit(plugin_dir_path(__FILE__)).'/vendor/autoload.php');


function woocommerce_ginger_init()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    // Just an alias for API callback action
    class woocommerce_ginger extends WC_Ginger_Callback
    {
    }

    function woocommerce_add_ginger($methods)
    {
        return array_merge($methods, WC_Ginger_BankConfig::$WC_BANK_PAYMENT_METHODS);
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_ginger');
    add_action('woocommerce_api_callback', array(new woocommerce_ginger(), 'ginger_handle_callback'));

    function ginger_register_shipped_order_status()
    {
        register_post_status('wc-shipped', array(
            'label' => 'Shipped',
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>')
        ));
    }

    add_action('init', 'ginger_register_shipped_order_status');

    /**
     * @param array $order_statuses
     * @return array
     */
    function ginger_add_shipped_to_order_statuses(array $order_statuses)
    {
        $new_order_statuses = array();
        foreach ($order_statuses as $key => $status)
        {
            $new_order_statuses[$key] = $status;
            if ('wc-processing' === $key) $new_order_statuses['wc-shipped'] = 'Shipped';
        }
        return $new_order_statuses;
    }

    add_filter('wc_order_statuses', 'ginger_add_shipped_to_order_statuses');
    add_action('woocommerce_order_status_shipped', 'ginger_ship_an_order', 10, 2);
    add_action('woocommerce_order_item_add_action_buttons', 'ginger_add_refund_description');

    load_plugin_textdomain(WC_Ginger_BankConfig::BANK_PREFIX, false, basename(dirname(__FILE__)).'/languages');

    /**
     * Function ginger_add_refund_description
     *
     * @param $order
     */
    function ginger_add_refund_description($order)
    {
        if (strstr($order->data['payment_method'],WC_Ginger_BankConfig::BANK_PREFIX)) //shows only for orders which were paid by bank's payment method
        {
            echo "<p style='color: red; ' class='description'>" . esc_html__( "Please beware that for bank transactions the refunds will process directly to the gateway!", WC_Ginger_BankConfig::BANK_PREFIX) . "</p>";
        }
    }

    /**
     * Function ginger_ship_an_order - Support for Klarna and Afterpay order shipped state
     *
     * @param $order_id
     * @param $order
     */
    function ginger_ship_an_order($order_id, $order)
    {
        if ($order->get_status() == 'shipped')
        {
            $client = WC_Ginger_Clientbuilder::gingerBuildClient($order->get_payment_method());
            try {
                $id = get_post_meta($order_id, WC_Ginger_BankConfig::BANK_PREFIX.'_order_id', true);
                $gingerOrder = $client->getOrder($id);
                if (current($gingerOrder['transactions'])['is_capturable'])//check if order can be captured
                {
                    $transactionID = current($gingerOrder['transactions']) ? current($gingerOrder['transactions'])['id'] : null;
                    $client->captureOrderTransaction($gingerOrder['id'], $transactionID);
                }
            } catch (\Exception $exception) {
                WC_Admin_Notices::add_custom_notice('ginger-error', $exception->getMessage());
            }
        }
    }

    /**
     * Filter out The plugin gateways by countries and IPs.
     * @param $gateways
     * @return mixed
     */
    function ginger_additional_filter_gateways($gateways)
    {
        if (!is_checkout()) return $gateways;
        unset($gateways['ginger']);
        foreach ($gateways as $key => $gateway)
        {
            if (!strstr($gateway->id,WC_Ginger_BankConfig::BANK_PREFIX)) continue; //skip woocommerce default payment methods
            $settings = get_option('woocommerce_'.$gateway->id.'_settings');

            if($gateway instanceof GingerCountryValidation)
            {
                if (array_key_exists('countries_available', $settings) && $settings['countries_available'])
                {
                    $countryList = array_map("trim", explode(',', $settings['countries_available']));
                    if (!WC_Ginger_Helper::gingerGetBillingCountry() || !in_array(WC_Ginger_Helper::gingerGetBillingCountry(), $countryList))
                    {
                        unset($gateways[$key]);
                        continue;
                    }
                }
            }

            if ($gateway instanceof GingerAdditionalTestingEnvironment)
            {
                if (array_key_exists('debug_ip', $settings) && $settings['debug_ip'])
                {
                    $whiteListIP = array_map('trim', explode(",", $settings['debug_ip']));
                    if (!in_array(WC_Geolocation::get_ip_address(), $whiteListIP))
                    {
                        unset($gateways[$key]);
                        continue;
                    }
                }

            }
        }

        return $gateways;
    }

    /**
     *  Function ginger_remove_notices
     */
    function ginger_remove_notices()
    {
        wc_clear_notices();
    }

    function applepay_detection()
    {
        if (is_checkout()):?>
            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    $(document.body).on('updated_checkout', function () {
                        let BANK_PREFIX = '<?php echo WC_Ginger_BankConfig::BANK_PREFIX?>';
                        if(!window.ApplePaySession)
                        {
                            let payment = document.getElementsByClassName('payment_method_'+BANK_PREFIX+'_apple-pay')[0];
                            payment.style.display = 'none';
                        }
                    });
                });
            </script>
        <?php
        endif;
    }

    function woocommerce_order_status_completed($post_id): void
    {
        $order = wc_get_order($post_id);

        //check if capture on complete is enabled
        if (str_replace(WC_Ginger_BankConfig::BANK_PREFIX.'_', '', $order->get_payment_method()) == 'credit-card'){
            $settings = get_option('woocommerce_'.WC_Ginger_BankConfig::BANK_PREFIX.'_credit-card_settings');

            $captureManual = $settings['capture_manual'] ?? 'no';
            if ($captureManual == 'no') {
                return;
            }
        }

        $gingerOrderIDMeta = get_post_meta($post_id, WC_Ginger_BankConfig::BANK_PREFIX.'_order_id', true);

        if(!$gingerOrderIDMeta){
            return;
        }

        $client = WC_Ginger_Clientbuilder::gingerBuildClient($order->get_payment_method());

        $gingerOrder = $client->getOrder($gingerOrderIDMeta);

        //check if order was already captured
        if (!empty($gingerOrder['flags']) && in_array('has-captures', $gingerOrder['flags'])) {
            return;
        }

        try {
            if (current($gingerOrder['transactions'])['is_capturable'])//check if order can be captured
            {
                $transactionID = current($gingerOrder['transactions']) ? current($gingerOrder['transactions'])['id'] : null;
                $client->captureOrderTransaction($gingerOrder['id'], $transactionID);
            }
        } catch (\Exception $exception) {
            $order->add_order_note( $exception->getMessage() );
            WC_Admin_Notices::add_custom_notice('ginger-error', $exception->getMessage());
        }
    }

    function woocommerce_order_status_cancelled($post_id): void
    {
        $order = wc_get_order($post_id);

        //check if capture on complete is enabled
        if (str_replace(WC_Ginger_BankConfig::BANK_PREFIX.'_', '', $order->get_payment_method()) == 'credit-card'){
            $settings = get_option('woocommerce_'.WC_Ginger_BankConfig::BANK_PREFIX.'_credit-card_settings');

            $captureManual = $settings['capture_manual'] ?? 'no';
            if ($captureManual == 'no') {
                return;
            }
        }

        $gingerOrderIDMeta = get_post_meta($post_id, WC_Ginger_BankConfig::BANK_PREFIX.'_order_id', true);

        if(!$gingerOrderIDMeta){
            return;
        }

        $client = WC_Ginger_Clientbuilder::gingerBuildClient($order->get_payment_method());

        $gingerOrder = $client->getOrder($gingerOrderIDMeta);

        if ($gingerOrder['status'] !== 'completed') {
            return;
        }

        $transaction = current($gingerOrder['transactions']);
        if (!isset($transaction['transaction_type']) || $transaction['transaction_type'] !== 'authorization') {
            return;
        }

        //check if order was already captured or voided
        if (!empty($gingerOrder['flags']) && (in_array('has-captures', $gingerOrder['flags']) || in_array('has-voids', $gingerOrder['flags']))) {
            return;
        }

        try {
            $transactionID = current($gingerOrder['transactions']) ? current($gingerOrder['transactions'])['id'] : null;

            $client->send('POST', sprintf('/orders/%s/transactions/%s/voids/amount', $gingerOrder['id'], $transactionID),
                ['amount' => $gingerOrder['amount'], 'description' => sprintf(
                    "Void %s of the full %s on order %s ",
                    $gingerOrder['amount'], $gingerOrder['amount'], $gingerOrder['merchant_order_id']
                )]);

        } catch (\Exception $exception) {
            $order->add_order_note( $exception->getMessage() );
            WC_Admin_Notices::add_custom_notice('ginger-error', $exception->getMessage());
        }
    }

    add_filter('woocommerce_available_payment_gateways', 'ginger_additional_filter_gateways', 10);
    add_action('woocommerce_thankyou', 'ginger_remove_notices', 20);
    add_action('woocommerce_after_checkout_form', 'applepay_detection',10);
    add_action( 'woocommerce_order_status_completed', 'woocommerce_order_status_completed', 10 );
    add_action( 'woocommerce_order_status_cancelled', 'woocommerce_order_status_cancelled', 10 );
    // WooCommerce Blocks support
    add_action('woocommerce_blocks_loaded', 'ginger_register_blocks_support');

}

/**
 * Register WooCommerce Blocks support for NoPayn payment methods
 */
function ginger_register_blocks_support()
{
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    // Ensure abstract class is loaded before child classes (autoloader handles the rest)
    require_once plugin_dir_path(__FILE__) . 'classes/Blocks/class-wc-ginger-abstract-block-payment.php';

    add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function(Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                $methods = [
                        'WC_Ginger_Creditcard_Block',
                        'WC_Ginger_Applepay_Block',
                        'WC_Ginger_Googlepay_Block',
                        'WC_Ginger_Mobilepay_Block',
                        'WC_Ginger_Swish_Block',
                        'WC_Ginger_Vipps_Mobilepay_Block',
                ];

                foreach ($methods as $method_class) {
                    try {
                        $payment_method_registry->register(new $method_class());
                    } catch (Exception $e) {
                        error_log('Payment Blocks: Failed to register ' . $method_class . ': ' . $e->getMessage());
                    }
                }
            }
    );
}
