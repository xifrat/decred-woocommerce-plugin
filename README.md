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
  - make the copy icons work
  - generate & show QR code in JS
  - live status widget

## possible improvements

* see TODOs in code, most of them already mentioned in this file
* automated plugin build process
* Internationalization (i18n)
* extensive documentation
* cleanup CSS styles copied from Magento plugin
* themes/styling/fonts: try several themes, try to make styling more generic so it blends better with any theme