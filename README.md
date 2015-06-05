## ZiftrPay-Magento
Use ZiftrPay's multi-currency payment system with your magento site.

##Setup
Download the plugin or zip file https://github.com/coinbase/coinbase-magento/archive/master.zip

Copy the contents of the app folder to your Magento installation app files

You will need a ZiftrPay merchant account:

1. Head to https://www.ziftrpay.com/ to sign up or log in.
2. Go to Merchants->Account Info
3. Obtain your Publishable Key and Secret Key

Head to your magento admin panel:

1. System->Configuration->Payment Methods
2. Open ZiftrCrypto Payment (if not showing then try clearing your Magento Cache)
3. Set Enable to Yes
4. Enter your ZiftrPay Merchant Account Email, Publishable Key and Secret Key
