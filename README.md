# decred-woocommerce-plugin

Official implementation of Decred payments for WooCommerce.

# Backlog

## required for beta

* port JavaScript features from Magento plugin implementation (detailed below)
* make it work! using decred-php middle layer
* test several environments (travis setup)
* register plugin on WordPress site
* minimal documentation

JavaScript features (like Magento plugin in principle):
* checkout page
 - refund address validation
 - refund address required (when configuration set accordingly)
* thank you page
  - generate & show QR code in JS
  - live status widget

## possible improvements

* automated plugin build process
* extensive documentation
* Internacionalization (i18n)
