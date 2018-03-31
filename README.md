# decred-woocommerce-plugin

WordPress plugin that enables Decred as payment method for the WooCommerce plugin.

# Backlog

## required for beta
* search TODOs in code, most of them already mentioned in this file
* complete backend features
  * fix defect: in case of DCR conversion error or zero amount disable somehow payment with DCR
* JavaScript features (like Magento plugin in principle) in thank you page:
  * make the copy icons work
  * generate & show QR code in JS
  * live status widget  
* test several environments (travis setup?)
* register plugin on WordPress site
* minimal documentation

## possible improvements

### Backend
* Internationalization (i18n)
* extended documentation

### Frontend
* JavaScript features (like Magento plugin in principle) in checkout page: 
  - DCR amount formating besides PHP (worth it?)
  - JS refund address validation
  - JS refund address required (when configuration set accordingly)
* cleanup CSS styles copied from Magento plugin
* themes/styling/fonts: try several themes, try to make styling more generic so it blends better with any theme