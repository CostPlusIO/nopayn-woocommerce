# Changelog NoPayn payment plugin

** 1.0.0 **

* Initial version

** 1.0.1 **

* Implemented Capture on Complete feature
* Implemented Void on Cancelled feature
* Updated translations

** 1.0.2 **

* Updated Description

** 1.0.3 **

* Updated Tested up version

** 1.0.4 **

* Improved void on Cancelled func

** 1.0.5 **

* Fixed bug: Void transaction was created incorrectly

** 1.0.6 **

* Fixed bug with Void functionality
* Fixed bug with order status mapping

** 1.0.7 **

* Set default expiration time to 5 minutes for orders and transactions

** 1.0.8 **

* Provided possibility to customize the expiration period in main module settings
* Fixed bug: plugin was updating the order status even when it was already set to a final state in the store.
  
** 1.0.9 **

* Fixed Bug: Where completing an order triggered an error in case order is not related to our plugin.

** 1.0.10 **

* Fixed Bug: Where the order status did not update after a successful retry following a failed payment.

** 1.0.11 **

* Added refund button.

** 1.0.12 **

* Added support for WooCommerce Checkout Blocks.

** 1.0.13 **

* Added Vipps/MobilePay payment method.

** 1.0.14 **

* Updated icons for Vipps/MobilePay.

** 1.0.15 **

* Fixed bug: authorized payments set to "Pending" were voided by WooCommerce's auto-cancel cron before capture.

** 1.0.16 **

* Fixed bug: webhook status updates after payment method change.
* Updated icons

** 1.0.17 **

* Fixed bug: use order number instead of post ID as merchant_order_id.

** 1.0.18 **

* Fixed bug: callback failed to match order when order number differs from post ID.

** 1.0.19 **

* Fixed WooCommerce order status updates for stores using HPOS.

** 1.0.20 **

* Rollback plugin to 1.0.16 version.

** 1.0.21 **

* Fixed bug: process_payment() returns boolean false, which causes an uncaught TypeError.
* Fixed bug: customer address and phone number mapping issue.

** 1.0.22 **
* Implemented Delayed and manual capture mode for Vipps/MobilePay payment method
* Fixed bug: customer address and phone number mapping issue.