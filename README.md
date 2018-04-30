# decred-woocommerce-plugin

WordPress plugin that enables Decred as payment method for the WooCommerce plugin.

## Warning on exchange rates

Between the moment a DCR amount is quoted and the moment the customer submits 
the payment transaction to the blockchain the exchange rate may have changed significantly.
Even more so when the merchant eventually sells for fiat the collected DCR. Therefore this plugin
is useful mostly for merchants who want hold DCR or are otherwise fine with this risk.

## Known issues with current design

This plugin, through the Decred PHP API dependency (https://github.com/decred/decred-php-api) 
has the following limitations/risks at the time of writing:

* Dependency on two external services that could be not reachable or provide 
  wrong data due to some malicious attack or server/network malfunctioning.
  - CoinMarketCap API for getting up-to-date fiat-to-DCR exchange rates.
  - Official Decred blockchain explorer (explorer.dcrdata.org) for payment confirmation.

# Backlog

## selected for beta

* Test on mainnet
* JavaScript features (like Magento plugin in principle) in thank you page:
  - make the copy icons work
  - generate & show QR code in JS
* Show relevant Decred payment data wherever order data is shown. E.g. order list/view pages.
* UX: Automated emails: review all automated emails for customer and merchant, 
  and eventually include additional information so they are clearly informed about what’s going on, 
  and if some action is required, particularly after updates in orders’ statuses derived from blockchain queries.
* Refunds. Study how WooCommerce manages them & see if some 
  specific feature for Decred payments would be important to have.
* Potential issues that may need fixing
  - this implementation expects a unique transaction with the exact amount.
    Would it make sense to allow for several transactions as long as the total matches?.
    Or allow for small differences in the amount received?.
  - in case of DCR conversion error or zero amount disable somehow payment with DCR
  - clarify potential issue regarding order id used as extended key's child id -- would payments be missed by wallets?
  - unschedule status updater when no orders on hold, or the plugin is deactivated. 
    Re-schedule it when some order put on hold manually or plugin activated. 
    Or just keep checking, but maybe less often.
  - better refund address validation, probably decred-php-api already does it somewhere somehow
    TODO futher validation of the third+ characters.
* Minimal logging using WC_Logger, show in admin page (link already exists)
* Test several environments (travis setup?)
* Register plugin on WordPress site
* Minimal documentation

## possible improvements

### Backend
* Search TODOs in code, the most important already included in this file
  * Complete unit test coverage. Search TODO TEST in code.
  * order status updater. TODO TEST complete coverage with all execution paths and error conditions.
* Functional tests
* Internationalization (i18n)
* Additional documentation
* Validate MPK when entered in admin (possibly by getting an address)
* Detect issues with plugin and show a message in admin settings 
  (idea from another plugin that shows “operational” or not on top)
* Release process. Use githubs’ feature. Automated plugin build process (extend bin/packplugin.sh).

### Frontend
* JavaScript features (like Magento plugin in principle) in checkout page:
  - live status widget (usefulness doubtful, if implemented consider decreasing Constant:CRON_INTERVAL)
* Cleanup CSS styles copied from Magento plugin
* Themes/styling/fonts: try several themes, try to make styling more generic so it blends better with any theme