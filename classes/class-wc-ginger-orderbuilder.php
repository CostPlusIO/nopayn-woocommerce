<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Ginger_Orderbuilder
{
    private $billingAddress;
    private $shippingAddress;
    private $paymentMethod;
    private $id;
    protected $woocommerceOrder;
    protected $merchant_order_id;
    public function __construct($paymentMethod,$id,$woocommerceOrder,$merchant_order_id)
    {
        $this->paymentMethod = $paymentMethod;
        $this->id = $id;
        $this->woocommerceOrder = $woocommerceOrder;
        $this->merchant_order_id = $merchant_order_id;
    }

    /**
     * Function builds an order
     * @return array
     * @throws Exception
     */
    public function gingerGetBuiltOrder(): array
    {
        $order = [];

        $order['merchant_order_id'] = $this->gingerGetMerchantOrderID();
        $order['customer'] = $this->gingerGetCustomerInfo();
        $order['currency'] = $this->gingerGetCurrency();
        $order['client'] = $this->gingerGetClient();
        $order['amount'] = $this->gingerGetAmount();
        $order['description'] = $this->gingerGetOrderDescription();
        $order['return_url'] = $this->gingergetReturnUrl();
        $order['webhook_url'] = $this->gingerGetWebhookUrl();
        $order['order_lines'] = $this->gingerGetOrderLines($this->woocommerceOrder);
        $order['expiration_period'] = $this->gingerGetExpirationPeriod();

        if (!$this instanceof GingerHostedPaymentPage) //HPP order must not contains transaction field
        {
            $order['transactions'][] = $this->gingerGetTransactions();
        }

        return $order;

    }

    /**
     * Function returns transaction array
     * @return array
     * @throws Exception
     */
    public function gingerGetTransactions(): array
    {
        return array_filter([
            'payment_method' => $this->gingerGetPaymentMethod(),
            'payment_method_details' => $this->gingerGetPaymentMethodDetails(),
            'capture_mode' => $this->gingerGetCaptureMode(),
            'expiration_period' => $this->gingerGetExpirationPeriod(),
        ]);
    }

    public function gingerGetExpirationPeriod(): string
    {
        $settings = get_option('woocommerce_ginger_settings');
        if (!is_array($settings)) return "PT5M";

        $expirationPeriod = $settings['expiration_period'] ?? null;

        if (isset($expirationPeriod) && ctype_digit($expirationPeriod) && (int)$expirationPeriod > 0) {
            return 'PT' . (int)$expirationPeriod . 'M';
        }

        return 'PT5M';
    }

    public function gingerGetCaptureMode(): string
    {
        if (str_replace(WC_Ginger_BankConfig::BANK_PREFIX.'_', '', $this->id) == 'credit-card'){
            $settings = get_option('woocommerce_'.WC_Ginger_BankConfig::BANK_PREFIX.'_credit-card_settings');

            $captureManual = $settings['capture_manual'] ?? 'no';
            if ($captureManual == 'yes') {
                return 'manual';
            }
        }

        return '';
    }

    /**
     * @return array|string[]
     * @throws Exception
     */
    public function gingerGetPaymentMethodDetails(): array
    {
        $paymentMethodDetails = [];

        //uses for afterpay
        if ($this->paymentMethod instanceof GingerTermsAndConditions)
        {
            $termsAndConditionFlag = WC_Ginger_Helper::gingerGetCustomPaymentField('toc');
            if ($termsAndConditionFlag)
            {
                $paymentMethodDetails = [
                    'verified_terms_of_service' => true,
                ];
            }
            return $paymentMethodDetails;

        }

        return $paymentMethodDetails;

    }


    /**
     * Function returns chosen payment method
     * @return string|string[]
     */
    public function gingerGetPaymentMethod(): string
    {
        return str_replace(WC_Ginger_BankConfig::BANK_PREFIX.'_', '', $this->id);
    }

    /**
     * Function returns extra fields
     * @return array
     */
    public function gingerGetClient(): array
    {
        return [
            'user_agent' => $this->gingerGetUserAgent(),
            'platform_name' => $this->gingerGetPlatformName(),
            'platform_version' => $this->gingerGetPlatformVersion(),
            'plugin_name' => $this->gingerGetPluginName(),
            'plugin_version' => $this->gingerGetPluginVersion()
        ];
    }
    public function gingerGetPluginVersion(): string
    {
        return GINGER_PLUGIN_VERSION;
    }

    public function gingerGetPluginName()
    {
        return WC_Ginger_BankConfig::PLUGIN_NAME;
    }
    public function gingerGetPlatformName()
    {
        return 'WooCommerce';
    }

    public function gingerGetPlatformVersion()
    {
        return get_option('woocommerce_version');
    }

    /**
     * Method returns returns WC_Api callback URL
     *
     * @return string
     */
    public function gingerGetReturnUrl()
    {
        return add_query_arg('wc-api', 'woocommerce_ginger', home_url('/'));
    }

    /**
     * Method formats the floating point amount to amount in cents
     *
     * @param float $total
     * @return int
     */
    public function gingerGetAmountInCents($total)
    {
        return (int) round($total * 100);
    }

    /**
     * Method returns order total in cents based on current WooCommerce version.
     * @return int
     */
    public function gingerGetAmount()
    {
        if (version_compare(get_option('woocommerce_version'), '3.0', '>=')) {
            $orderTotal = $this->woocommerceOrder->get_total();
        } else {
            $orderTotal = $this->woocommerceOrder->order->order_total;
        }

        return $this->gingerGetAmountInCents($orderTotal);
    }

    /**
     * Method returns currencyCurrency in ISO-4217 format
     *
     * @return string
     */
    public function gingerGetCurrency()
    {
        return get_woocommerce_currency();
    }

    /**
     * Method returns customer information from the order
     * @return array
     */
    public function gingerGetCustomerInfo()
    {
        $this->billingAddress = (array) $this->woocommerceOrder->get_address('billing');
        $this->shippingAddress = (array) $this->woocommerceOrder->get_address('shipping');

        return array_filter([
            'address_type' => $this->gingerGetAddressType(),
            'merchant_customer_id' => $this->gingerGetMerchantCustomerID(),
            'email_address' => $this->gingerGetEmailAddress(),
            'first_name' => $this->gingerGetFirstName(),
            'last_name' => $this->gingerGetLastName(),
            'address' => $this->gingerGetAddress(),
            'postal_code' => $this->gingerGetPostalCode(),
            'city' => $this->gingerGetCity(),
            'country' => $this->gingerGetCountry(),
            'phone_numbers' => $this->gingerGetPhoneNumbers(),
            'user_agent' => $this->gingerGetUserAgent(),
            'ip_address' => $this->gingerGetIPAddress(),
            'locale' => $this->gingerGetLocale(),
            'gender' => $this->gingerGetGender(),
            'birthdate' => $this->gingerGetBirthdate(),
            'additional_addresses' => $this->gingerGetAdditionalAddresses()
        ]);
    }

    /**
     * Function returns value from gender field
     * @return string|null
     */
    public function gingerGetGender()
    {
        return WC_Ginger_Helper::gingerGetCustomPaymentField('gender');
    }

    /**
     * Function returns values from birthday fields
     * @return string
     */
    public function gingerGetBirthdate():string
    {
        $birthdate = implode('-', [
            WC_Ginger_Helper::gingerGetCustomPaymentField('ginger_afterpay_date_of_birth_year'),
            WC_Ginger_Helper::gingerGetCustomPaymentField('ginger_afterpay_date_of_birth_month'),
            WC_Ginger_Helper::gingerGetCustomPaymentField('ginger_afterpay_date_of_birth_day')
        ]);

        // removing it will make sure it gets removed if empty and thus not validated
        if ($birthdate == '--') $birthdate = '';
        return $birthdate;
    }

    /**
     * Function returns additional addresses, the shipping address is sent as a delivery address.
     * The billing address is already sent as the root customer address and must not be repeated here.
     * @return string[][]
     */
    public function gingerGetAdditionalAddresses():array
    {
        $address = $this->gingerGetAddressLines($this->shippingAddress);

        if ($address === '')
        {
            return []; //no separate delivery address available
        }

        return [
            array_filter([
                'address_type' => 'delivery',
                'address' => $address,
                'postal_code' => str_replace(' ', '', $this->gingerGetFieldValue($this->shippingAddress, 'postcode')),
                'city' => $this->gingerGetFieldValue($this->shippingAddress, 'city'),
                'country' => strtoupper($this->gingerGetFieldValue($this->shippingAddress, 'country'))
            ])
        ];
    }

    /**
     * Function returns a trimmed value of the given address field
     * @param array $address
     * @param string $field
     * @return string
     */
    protected function gingerGetFieldValue($address, $field):string
    {
        return isset($address[$field]) ? trim((string) $address[$field]) : '';
    }

    /**
     * Function returns a billing field value with a fallback to the shipping one
     * @param string $field
     * @return string
     */
    protected function gingerGetAddressField($field):string
    {
        $billingValue = $this->gingerGetFieldValue($this->billingAddress, $field);

        return $billingValue !== '' ? $billingValue : $this->gingerGetFieldValue($this->shippingAddress, $field);
    }

    /**
     * Function returns street and house number of the given address, the city and the postal code
     * are sent as separate fields
     * @param array $address
     * @return string
     */
    protected function gingerGetAddressLines($address):string
    {
        return trim($this->gingerGetFieldValue($address, 'address_1')
            .' '.$this->gingerGetFieldValue($address, 'address_2'));
    }

    /**
     * Function returns customer address, the billing address is preferred and the shipping address
     * is only used when there is no billing street available
     * @return string
     */
    public function gingerGetAddress():string
    {
        $address = $this->gingerGetAddressLines($this->billingAddress);

        return $address !== '' ? $address : $this->gingerGetAddressLines($this->shippingAddress);
    }

    /**
     * Function returns customer ip
     * @return string
     */
    public function gingerGetIPAddress():string
    {
        return version_compare(get_option('woocommerce_version'), '3.0', '>=')
            ? $this->woocommerceOrder->get_customer_ip_address()
            : $this->woocommerceOrder->customer_ip_address;
    }

    /**
     * Function returns customer user agent
     * @return string
     */
    public function gingerGetUserAgent():string
    {
        return version_compare(get_option('woocommerce_version'), '3.0', '>=')
            ? $this->woocommerceOrder->get_customer_user_agent()
            : $this->woocommerceOrder->customer_user_agent;
    }

    /**
     * Function returns locale, an unsupported locale is omitted instead of failing the validation
     * @return string
     */
    public function gingerGetLocale():string
    {
        $locale = (string) get_locale();

        return preg_match('/^[a-zA-Z]{2}([-_][a-zA-Z]{2})?$/', $locale) ? $locale : '';
    }

    /**
     * Functions return customer phone numbers
     * @return array
     */
    public function gingerGetPhoneNumbers(): array
    {
        $phoneNumber = $this->gingerNormalizePhoneNumber(
            $this->gingerGetAddressField('phone'),
            $this->gingerGetCountry()
        );

        return $phoneNumber === '' ? [] : [$phoneNumber];
    }

    /**
     * Function converts a phone number into the E.164 format, the country is used to resolve
     * numbers that are stored in a national format. An empty string is returned when the number
     * can not be normalised, so the field gets omitted instead of failing the validation.
     * @param string $phoneNumber
     * @param string $country
     * @return string
     */
    public function gingerNormalizePhoneNumber($phoneNumber, $country): string
    {
        $phoneNumber = trim((string) $phoneNumber);
        if ($phoneNumber === '') return '';

        $isInternational = strpos($phoneNumber, '+') === 0;
        $digits = preg_replace('/\D+/', '', $phoneNumber);
        if ($digits === '') return '';

        if (!$isInternational && strpos($digits, '00') === 0)
        {
            $digits = substr($digits, 2);
            $isInternational = true;
        }

        if (!$isInternational)
        {
            $callingCode = preg_replace('/\D+/', '', $this->gingerGetCountryCallingCode($country));
            if ($callingCode === '') return '';

            $digits = strpos($digits, $callingCode) === 0
                ? $digits //the number already carries the country calling code
                : $callingCode.ltrim($digits, '0'); //strip the national trunk prefix
        }

        $phoneNumber = '+'.$digits;

        return preg_match('/^\+[1-9]\d{7,14}$/', $phoneNumber) ? $phoneNumber : '';
    }

    /**
     * Function returns the country calling code of the given country
     * @param string $country
     * @return string
     */
    public function gingerGetCountryCallingCode($country): string
    {
        if (!$country || !function_exists('WC') || !WC()->countries) return '';

        $callingCode = WC()->countries->get_country_calling_code(strtoupper($country));
        if (is_array($callingCode)) $callingCode = reset($callingCode); //some countries have several calling codes

        return (string) $callingCode;
    }

    /**
     * Function returns customer country
     * @return string
     */
    public function gingerGetCountry():string
    {
        return strtoupper($this->gingerGetAddressField('country'));
    }

    /**
     * Function returns address's post code
     * @return string
     */
    public function gingerGetPostalCode():string
    {
        return str_replace(' ', '', $this->gingerGetAddressField('postcode'));
    }

    /**
     * Function returns address's city
     * @return string
     */
    public function gingerGetCity():string
    {
        return $this->gingerGetAddressField('city');
    }

    /**
     * Function returns customer first name
     * @return string
     */
    public function gingerGetFirstName():string
    {
        return $this->gingerGetAddressField('first_name');
    }

    /**
     * Function returns customer last name
     * @return string
     */
    public function gingerGetLastName():string
    {
        return $this->gingerGetAddressField('last_name');
    }

    /**
     * Function returns customer email address
     * @return string
     */
    public function gingerGetEmailAddress():string
    {
        return $this->gingerGetAddressField('email');
    }

    /**
     * Function returns merchant customer ID
     * @return string
     */
    public function gingerGetMerchantCustomerID():string
    {
        return $this->woocommerceOrder->get_user_id();
    }

    /**
     * Function returns merchant order ID
     * @return string
     */
    public function gingerGetMerchantOrderID():string
    {
        return $this->merchant_order_id;
    }

    /**
     * Function returns address type
     * @return string
     */
    public function gingerGetAddressType(): string
    {
        return 'billing';
    }

    /**
     * Get product price based on WooCommerce version.
     *
     * @param WC_Product $product
     * @return float|string
     */
    public function gingerGetProductPrice($orderLine, $order)
    {
        if (version_compare(get_option('woocommerce_version'), '3.0', '>=')) {
            return $order->get_item_total( $orderLine, true );
        } else {
            $product = $orderLine->get_product();
            return $product->get_price_including_tax();
        }
    }

    /**
     * Function returns order lines
     * @param $order
     * @return array
     */
    public function gingerGetOrderLines($order)
    {
        $orderLines = [];
        $productIds = [];

        foreach ($order->get_items() as $orderLine)
        {
            $productId = (int) $orderLine->get_variation_id() ?: $orderLine->get_product_id();
            $productIds[] = $productId;

            $imageURL = wp_get_attachment_url($orderLine->get_product()->get_image_id());
            $orderLines[] = array_filter([
                'url' => get_permalink($productId),
                'name' => $orderLine->get_name(),
                'type' => 'physical',
                'amount' => $this->gingerGetAmountInCents($this->gingerGetProductPrice($orderLine, $order)),
                'currency' => $this->gingerGetCurrency(),
                'quantity' => (int) $orderLine->get_quantity(),
                'image_url' => $imageURL ? $imageURL : null,
                'vat_percentage' => $this->gingerGetAmountInCents($this->gingerGetProductTaxRate($orderLine->get_product())),
                'merchant_order_line_id' => (string) $productId
            ],
                function($value) {
                    return ! is_null($value);
                });
        }

        if ($order->get_total_shipping() > 0) {
            $orderLines[] = $this->gingerGetShippingOrderLine($order);
        }


        //bug-fix: PLUG-1381
        if (count($productIds) !== count(array_unique($productIds))) {
            $orderLines = $this->gingerGetUniqueOrderLines($orderLines);
        }

        return $orderLines;
    }

    /**
     * @param $orderLines - array that contains order line duplications
     * @return array - array without duplications
     */
    public function gingerGetUniqueOrderLines($orderLines)
    {
        $updatedOrderLines = [];

        foreach ($orderLines as $orderLine) {

            $addOrderLine = true;

            foreach ($updatedOrderLines as $key => $updatedOrderLine) {
                if ($updatedOrderLine['merchant_order_line_id'] == $orderLine['merchant_order_line_id']) {
                    $updatedOrderLines[$key]['quantity']+= $orderLine['quantity'];
                    $addOrderLine = false; //order line already exists, so we just sum the quantity
                    break;
                }
            }

            if ($addOrderLine) {
                $updatedOrderLines[] = $orderLine;
            }

        }

        return $updatedOrderLines;
    }

    /**
     * Since single item in the cart can have multiple taxes,
     * we need to sum those taxes up.
     *
     * @param $product
     * @return int
     */
    public function gingerGetProductTaxRate(WC_Product $product)
    {
        $WC_Tax = new WC_Tax();
        $totalTaxRate = 0;
        foreach ($WC_Tax->get_rates($product->get_tax_class()) as $taxRate) {
            $totalTaxRate += $taxRate['rate'];
        }
        return $totalTaxRate;
    }

    /**
     * Function returns shipping order line
     * @param $order
     * @return array
     */
    public function gingerGetShippingOrderLine($order)
    {
        return [
            'name' => $order->get_shipping_method(),
            'type' => 'shipping_fee',
            'amount' => $this->gingerGetAmountInCents($order->get_shipping_total() + $order->get_shipping_tax()),
            'currency' => $this->gingerGetCurrency(),
            'vat_percentage' => $this->gingerGetAmountInCents($this->gingerGetShippingTaxRate()),
            'quantity' => 1,
            'merchant_order_line_id' => (string) (count($order->get_items()) + 1)
        ];
    }

    /**
     * Since shipping fees can have multiple taxes applied,
     * we need to sum those taxes up.
     *
     * @return int
     */
    public function gingerGetShippingTaxRate()
    {
        $totalTaxRate = 0;
        foreach (WC_Tax::get_shipping_tax_rates() as $taxRate) {
            $totalTaxRate += $taxRate['rate'];
        }
        return $totalTaxRate;
    }

    /**
     * Generate order description
     * @return string
     */
    public function gingerGetOrderDescription()
    {
        return sprintf(__('Your order %s at %s', WC_Ginger_BankConfig::BANK_PREFIX), $this->merchant_order_id, get_bloginfo('name'));
    }

    /**
     * Function returns webhook URL
     * @param WC_Payment_Gateway $gateway
     * @return null|string
     */
    public function gingerGetWebhookUrl()
    {
        return $this->gingerGetReturnUrl();
    }

    /**
     * Function set the merchant order ID
     * @param $merchantOrderID
     */
    public function gingerSetMerchantOrderID($merchantOrderID)
    {
        $this->merchant_order_id = $merchantOrderID;
    }

}