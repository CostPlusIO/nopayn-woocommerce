<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WC_Ginger_Helper
 */
class WC_Ginger_Helper
{

    /**
     * List of payment methods that support capturing
     */
    const GATEWAYS_SUPPORT_CAPTURING = [
        WC_Ginger_BankConfig::BANK_PREFIX.'_afterpay',
        WC_Ginger_BankConfig::BANK_PREFIX.'_klarna-pay-later',
        WC_Ginger_BankConfig::BANK_PREFIX.'_klarna-direct-debit',
    ];

    /**
     * Method retrieves custom field from POST array.
     *
     * @param string $field
     * @return string|null
     */
    public static function gingerGetCustomPaymentField($field)
    {
        if (array_key_exists($field, $_POST) && strlen($_POST[$field]) > 0) return sanitize_text_field($_POST[$field]);
        return null;
    }


    /**
     * Form helper for admin settings display
     *
     * @param string $gateway - payment method
     * @return array
     */
    public static function gingerGetFormFields($gateway)
    {
        switch (str_replace(WC_Ginger_BankConfig::BANK_PREFIX.'_','',$gateway->id))
        {
            case 'ideal':
                $default = __('iDEAL', WC_Ginger_BankConfig::BANK_PREFIX);
                $label = __('Enable iDEAL Payments', WC_Ginger_BankConfig::BANK_PREFIX);
                break;
            case 'credit-card':
                $default = __('Credit Card', WC_Ginger_BankConfig::BANK_PREFIX);
                $label = __('Enable Credit Card Payments', WC_Ginger_BankConfig::BANK_PREFIX);
                return [
                    'enabled' => [
                        'title' => __('Enable/Disable', WC_Ginger_BankConfig::BANK_PREFIX),
                        'type' => 'checkbox',
                        'label' => $label,
                        'default' => 'no'
                    ],
                    'title' => [
                        'title' => __('Title', WC_Ginger_BankConfig::BANK_PREFIX),
                        'type' => 'text',
                        'description' => __('This controls the title which the user sees during checkout.', WC_Ginger_BankConfig::BANK_PREFIX),
                        'default' => $default,
                        'desc_tip' => true
                    ],
                    'capture_manual' => [
                        'title' => __('Capture on complete', WC_Ginger_BankConfig::BANK_PREFIX),
                        'label' => __('Captures payment when an order is marked as complete', WC_Ginger_BankConfig::BANK_PREFIX),
                        'type' => 'checkbox'
                    ],
                ];
            case 'bank-transfer':
                $default = __('Bank Transfer', WC_Ginger_BankConfig::BANK_PREFIX);
                $label = __('Enable Bank Transfer Payments', WC_Ginger_BankConfig::BANK_PREFIX);
                break;
            case 'klarna-pay-now':
                $default = __('Klarna Pay Now', WC_Ginger_BankConfig::BANK_PREFIX);
                $label = __('Enable Klarna Pay Now Payments', WC_Ginger_BankConfig::BANK_PREFIX);
                break;
            case 'bancontact':
                $default = __('Bancontact', WC_Ginger_BankConfig::BANK_PREFIX);
                $label = __('Enable Bancontact Payments', WC_Ginger_BankConfig::BANK_PREFIX);
                break;
            case 'paypal':
                $default = __('PayPal', WC_Ginger_BankConfig::BANK_PREFIX);
                $label = __('Enable PayPal Payments', WC_Ginger_BankConfig::BANK_PREFIX);
                break;
            case 'afterpay':
                $default = __('AfterPay', WC_Ginger_BankConfig::BANK_PREFIX);
                $label = __('Enable AfterPay Payments', WC_Ginger_BankConfig::BANK_PREFIX);
                break;
            case 'klarna-pay-later':
                $default = __('Klarna Pay Later', WC_Ginger_BankConfig::BANK_PREFIX);
                $label = __('Enable Klarna Pay Later Payments', WC_Ginger_BankConfig::BANK_PREFIX);
                break;
            case 'payconiq':
                $default = __('Payconiq', WC_Ginger_BankConfig::BANK_PREFIX);
                $label = __('Enable Payconiq Payments', WC_Ginger_BankConfig::BANK_PREFIX);
                break;
            case 'apple-pay':
                $default = __('Apple Pay', WC_Ginger_BankConfig::BANK_PREFIX);
                $label = __('Enable Apple Pay Payments', WC_Ginger_BankConfig::BANK_PREFIX);
                break;
            case 'pay-now':
                $default = __('Pay Now', WC_Ginger_BankConfig::BANK_PREFIX);
                $label = __('Enable Pay Now Payments', WC_Ginger_BankConfig::BANK_PREFIX);
                break;
            case 'amex':
                $default = __('American Express', WC_Ginger_BankConfig::BANK_PREFIX);
                $label = __('Enable American Express Payments', WC_Ginger_BankConfig::BANK_PREFIX);
                break;
            case 'tikkie-payment-request':
                $default = __('Tikkie Payment Request', WC_Ginger_BankConfig::BANK_PREFIX);
                $label = __('Enable Tikkie Payment Request Payments', WC_Ginger_BankConfig::BANK_PREFIX);
                break;
            case 'wechat':
                $default = __('WeChat', WC_Ginger_BankConfig::BANK_PREFIX);
                $label = __('Enable WeChat Payments', WC_Ginger_BankConfig::BANK_PREFIX);
                break;
            case 'google-pay':
                $default = __('Google Pay', WC_Ginger_BankConfig::BANK_PREFIX);
                $label = __('Enable Google Pay Payments', WC_Ginger_BankConfig::BANK_PREFIX);
                break;
            case 'klarna-direct-debit':
                $default = __('Klarna Direct Debit', WC_Ginger_BankConfig::BANK_PREFIX);
                $label = __('Enable Klarna Direct Debit Payments', WC_Ginger_BankConfig::BANK_PREFIX);
                break;
            case 'sofort':
                $default = __('Sofort', WC_Ginger_BankConfig::BANK_PREFIX);
                $label = __('Enable Sofort Payments', WC_Ginger_BankConfig::BANK_PREFIX);
                break;
            case 'swish':
                $default = __('Swish', WC_Ginger_BankConfig::BANK_PREFIX);
                $label = __('Enable Swish Payments', WC_Ginger_BankConfig::BANK_PREFIX);
                break;
            case 'mobilepay':
                $default = __('MobilePay', WC_Ginger_BankConfig::BANK_PREFIX);
                $label = __('Enable MobilePay Payments', WC_Ginger_BankConfig::BANK_PREFIX);
                break;
            case 'vipps-mobilepay':
                $default = __('Vipps/MobilePay', WC_Ginger_BankConfig::BANK_PREFIX);
                $label = __('Enable Vipps/MobilePay Payments', WC_Ginger_BankConfig::BANK_PREFIX);
                break;
            case 'viacash':
                $default = __('Viacash', WC_Ginger_BankConfig::BANK_PREFIX);
                $label = __('Enable Viacash Payments', WC_Ginger_BankConfig::BANK_PREFIX);
                break;
            case 'ginger':
                return [
                    'lib_title' => [
                        'title' => __( 'Title', WC_Ginger_BankConfig::BANK_PREFIX ),
                        'type' => 'text',
                        'description' => __( 'This is the general module with settings, during checkout the user will not see this option.', WC_Ginger_BankConfig::BANK_PREFIX ),
                        'default' => __( 'Plugin', WC_Ginger_BankConfig::BANK_PREFIX )
                    ],
                    'api_key' => [
                        'title' => __('API key', WC_Ginger_BankConfig::BANK_PREFIX),
                        'type' => 'text',
                        'description' => __('API key provided by '.WC_Ginger_BankConfig::BANK_LABEL, WC_Ginger_BankConfig::BANK_PREFIX),
                    ],
                    'expiration_period' => [
                        'title' => __('Transaction Expiration Period (minutes)', WC_Ginger_BankConfig::BANK_PREFIX),
                        'type' => 'number',
                        'description' => __('Set the number of minutes after which the order and transaction will automatically expire. By default order and transaction will be expired after 5 minutes', WC_Ginger_BankConfig::BANK_PREFIX),
                    ],
                    'failed_redirect' => [
                        'title' => __('Failed payment page', WC_Ginger_BankConfig::BANK_PREFIX),
                        'description' => __(
                            'Page where user is redirected after payment has failed.',
                            WC_Ginger_BankConfig::BANK_PREFIX
                        ),
                        'type' => 'select',
                        'options' => [
                            'checkout' => __('Checkout Page', WC_Ginger_BankConfig::BANK_PREFIX),
                            'cart' => __('Shopping Cart', WC_Ginger_BankConfig::BANK_PREFIX)
                        ],
                        'default' => 'checkout',
                        'desc_tip' => true
                    ],
                    'bundle_cacert' => [
                        'title' => __('cURL CA bundle', WC_Ginger_BankConfig::BANK_PREFIX),
                        'label' => __('Use cURL CA bundle', WC_Ginger_BankConfig::BANK_PREFIX),
                        'description' => __(
                            'Resolves issue when curl.cacert path is not set in PHP.ini',
                            WC_Ginger_BankConfig::BANK_PREFIX
                        ),
                        'type' => 'checkbox',
                        'desc_tip' => true
                    ],
                    'auto_complete' => [
                        'title' => __('Auto complete orders', WC_Ginger_BankConfig::BANK_PREFIX),
                        'label' => __('Enable automatically change store orders status to Completed after receiving payment', WC_Ginger_BankConfig::BANK_PREFIX),
                        'type' => 'checkbox'
                    ]
                ];
            default:
                $default = '';
                $label = '';
                break;
        }

        $formFields = [
            'enabled' => [
                'title' => __('Enable/Disable', WC_Ginger_BankConfig::BANK_PREFIX),
                'type' => 'checkbox',
                'label' => $label,
                'default' => 'no'
            ],
            'title' => [
                'title' => __('Title', WC_Ginger_BankConfig::BANK_PREFIX),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', WC_Ginger_BankConfig::BANK_PREFIX),
                'default' => $default,
                'desc_tip' => true
            ],
        ];

        if ($gateway instanceof GingerAdditionalTestingEnvironment)
        {
            $additionalFields = [
                'test_api_key' => [
                    'title' => __('Test API key', WC_Ginger_BankConfig::BANK_PREFIX),
                    'type' => 'text',
                    'description' => __('Test API key for testing implementation ' . $gateway->method_title, WC_Ginger_BankConfig::BANK_PREFIX),
                ],
                'debug_ip' => [
                    'title' => __('AfterPay Debug IP', WC_Ginger_BankConfig::BANK_PREFIX),
                    'type' => 'text',
                    'description' => __('IP address for testing '.$gateway->method_title.'. If empty, visible for all. If filled, only visible for specified IP addresses. (Example: 127.0.0.1, 255.255.255.255)', WC_Ginger_BankConfig::BANK_PREFIX),
                ],
            ];

            $formFields = array_merge($formFields, $additionalFields);
        }

        if ($gateway instanceof GingerCaptureMode)
        {
            $additionalFields = [
                'capture_mode' => [
                    'title' => __('Capture mode', WC_Ginger_BankConfig::BANK_PREFIX),
                    'type' => 'select',
                    'description' => __(
                        'Manual: the payment is only authorized and is captured when the order is marked as completed. Delayed: the payment is authorized and captured automatically by the gateway after the reservation period.',
                        WC_Ginger_BankConfig::BANK_PREFIX
                    ),
                    'options' => [
                        GingerCaptureMode::GINGER_CAPTURE_MODE_MANUAL => __('Manual', WC_Ginger_BankConfig::BANK_PREFIX),
                        GingerCaptureMode::GINGER_CAPTURE_MODE_DELAYED => __('Delayed', WC_Ginger_BankConfig::BANK_PREFIX)
                    ],
                    'default' => GingerCaptureMode::GINGER_DEFAULT_CAPTURE_MODE,
                    'desc_tip' => true
                ],
                'delayed_capture_period' => [
                    'title' => __('Delayed capture period', WC_Ginger_BankConfig::BANK_PREFIX),
                    'type' => 'select',
                    'description' => __(
                        'How long the amount stays reserved before the gateway captures it automatically. Only applies when the capture mode is Delayed.',
                        WC_Ginger_BankConfig::BANK_PREFIX
                    ),
                    'options' => self::gingerGetDelayedCapturePeriodOptions(),
                    'default' => GingerCaptureMode::GINGER_DEFAULT_DELAYED_CAPTURE_PERIOD,
                    'desc_tip' => true
                ]
            ];
            $formFields = array_merge($formFields, $additionalFields);
        }

        if ($gateway instanceof GingerCountryValidation)
        {
            $additionalFields = [
                'countries_available' => [
                    'title' => __('Countries available for ' . $gateway->method_title, WC_Ginger_BankConfig::BANK_PREFIX),
                    'type' => 'text',
                    'default' => implode(', ',self::gingerGetAvailableCountries($gateway->id)),
                    'description' => __('To allow '. $gateway->method_title.' to be used for any other country just add its country code (in ISO 2 standard) to the "Countries available for '. $gateway->method_title.'" field. Example: BE, NL, FR <br>  If field is empty then ' .$gateway->method_title. ' will be available for all countries.', WC_Ginger_BankConfig::BANK_PREFIX),
                ]
            ];
            $formFields = array_merge($formFields, $additionalFields);
        }

        return $formFields;
    }

    /**
     * Labels for the selectable delayed capture reservation periods, keyed by ISO 8601 duration
     *
     * @return array
     */
    public static function gingerGetDelayedCapturePeriodOptions(): array
    {
        $labels = [
            'PT1H' => __('1 hour', WC_Ginger_BankConfig::BANK_PREFIX),
            'PT6H' => __('6 hours', WC_Ginger_BankConfig::BANK_PREFIX),
            'PT12H' => __('12 hours', WC_Ginger_BankConfig::BANK_PREFIX),
            'P1D' => __('1 day', WC_Ginger_BankConfig::BANK_PREFIX),
            'P2D' => __('2 days', WC_Ginger_BankConfig::BANK_PREFIX),
            'P3D' => __('3 days', WC_Ginger_BankConfig::BANK_PREFIX),
            'P7D' => __('7 days', WC_Ginger_BankConfig::BANK_PREFIX),
        ];

        //the interface decides which periods the gateway accepts, the labels only decorate them
        return array_intersect_key($labels, array_flip(GingerCaptureMode::GINGER_DELAYED_CAPTURE_PERIODS));
    }

    public static function gingerGetAvailableCountries($gateway): array
    {
        $countryMapping = [
            WC_Ginger_BankConfig::BANK_PREFIX.'_afterpay' => ['NL', 'BE'],
        ];
        return $countryMapping[$gateway];
    }
    /**
     * Method returns payment method icon
     *
     * @param $method
     * @return null|string
     */
    public static function gingerGetIconSource($method)
    {
        if (in_array($method, WC_Ginger_BankConfig::$BANK_PAYMENT_METHODS))
        {
            $imageTitle = str_replace(WC_Ginger_BankConfig::BANK_PREFIX,'ginger',$method);
            $imageType = $imageTitle == 'ginger_pay-now' ? 'png' : 'svg';
            $imagePath = GINGER_PLUGIN_URL."images/{$imageTitle}.$imageType";
            return '<img src="'.WC_HTTPS::force_https_url($imagePath).'" />';
        }
    }

    /**
     * Function gingerGetBillingCountry
     */
    public static function gingerGetBillingCountry()
    {
        return (WC()->customer ? WC()->customer->get_billing_country() : false);
    }

}