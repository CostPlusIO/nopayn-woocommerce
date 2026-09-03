# NoPayn plugin for Wordpress WooCommerce

## About
The NoPayn WooCommerce plugin allows you to integrate NoPayn’s payment gateway into your WooCommerce store. From your order overview, you can easily manage key actions such as auto-capture, manual capture on order completion, refunds, and cancellations—giving you full control over your payment flow.
NoPayn is built on a foundation of transparency and simplicity. With clear, fixed transaction fees and no hidden costs, our solution is designed to be straightforward for merchants of all sizes. In addition, every transaction made through NoPayn contributes to tree planting, making your payment setup climate positive by default.

## Version number
Version 1.0.21

## Pre-requisites to install the plug-ins
* PHP v5.4 and above
* MySQL v5.4 and above

## Installation
Manual installation of the NoPayn WooCommerce plugin using (s)FTP

1. Upload the folder 'nopayn' in the ZIP file into the 'wp-content/plugins' folder of your WordPress installation.
   You can use an sFTP or SCP program, for example, to upload the files. There are various sFTP clients that you can download free of charge from the internet, such as WinSCP or Filezilla.
2. Activate the NoPayn plugin in ‘Plugins’ > Installed Plugins.
3. Select ‘WooCommerce’ > ‘Settings’ > Payments and click on NoPayn (Enabled).
4. Configure the NoPayn module ('Manage' button)
- Copy the API key
- Select your preferred Failed payment page. This setting determines the page to which your customer is redirected after a payment attempt has failed. You can choose between the Checkout page (the page where you can choose a payment method) or the Shopping cart page (the page before checkout where the content of the shopping cart is displayed).
- Enable the cURL CA bundle option.
  This fixes a cURL SSL Certificate issue that appears in some web-hosting environments where you do not have access to the PHP.ini file and therefore are not able to update server certificates.
- Each payment method has a Allowed currencies(settlement) setting with which it works. Depending on this setting, the selected store currency and the allowed currencies for the NoPayn gateway, payment methods will be filtered on the Checkout page. This setting can be edited for each payment method, if some currencies are not added, but the payment method works with it.
5. Configure each payment method you would like to offer in your webshop.
   Enable only those payment methods that you applied for and for which you have received a confirmation from us.
- To configure credit-card do the following:
	- Go to ‘WooCommerce’ > ‘Settings’ > Payments > ‘NoPayn: Credit-Card’.
	- Select Enable Credit-Card Payment to include the payment method in your pay page.
- Follow the same procedure for all other payment methods you have enabled.

Manual installation by uploading ZIP file from WordPress administration environment

1. Go to your WordPress admin environment. Upload the ZIP file to your WordPress installation by clicking on ‘Plugins’ > ‘Add New’. No files are overwritten.
2. Select ´Upload plugin´.
3. Select the nopayn.zip file.
4. Continue with step 3 of Installation using (s)FTP.

Compatibility: WordPress 5.6 or higher