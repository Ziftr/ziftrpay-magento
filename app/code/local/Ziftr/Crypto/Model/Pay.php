<?php

require_once(Mage::getModuleDir('ziftr-php', 'Ziftr_Crypto') . "/ziftr-php/ziftr-api.php");

class Ziftr_Crypto_Model_Pay extends Mage_Payment_Model_Method_Abstract
{
	protected $_code          = 'crypto';
	protected $_formBlockType = "crypto/payform";

	protected $_isGateway              = true;
	protected $_canAuthorize           = true;
	protected $_canCapture             = false;
	protected $_canCapturePartial      = false;
	protected $_canRefund              = false;
	protected $_canVoid                = false;
	protected $_canUseInternal         = false;
	protected $_canUseCheckout         = true;
	protected $_canUseForMultishipping = false;
	protected $_canSaveCc              = false;

	public function authorize(Varien_Object $payment, $amount){
		#get merchant api keys from store configuration
		$api_private_key = Mage::getStoreConfig('payment/crypto/secret_key');
		$api_publishable_key = Mage::getStoreConfig('payment/crypto/publishable_key');

		if ($api_private_key == null || $api_publishable_key == null){
			Mage::throwException("For ZiftrPay plugin, You need to set private and publishable api keys in Magento Admin.");
		}

		#create order request with Ziftr API
		$api_config = new Ziftr\ApiClient\Configuration();
		$api_config->load_from_array(array(
			'host' => 'sandbox.fpa.bz',
			'port' => 443,
			'private_key' => Mage::getStoreConfig('payment/crypto/secret_key'),
			'publishable_key' => Mage::getStoreConfig('payment/crypto/publishable_key')
			));
		$order_request = new Ziftr\ApiClient\Request("/orders/", $api_config);

		try {

			$magento_order = $payment->getOrder();
			$currency = $magento_order->getBaseCurrencyCode();
			// TODO add these seller_order_success/failure_urls after testing locally
			// Mage::log(Mage::getUrl('crypto/redirect') . 'failure?oid=' . $magento_order->getId());
			// Mage::log(Mage::getUrl('crypto/redirect') . 'success');
			$order_request = $order_request->post(
				array(
					'order' => array(
						'currency_code' => "USD",
						'is_shipping_required' => true,
						'shipping_price' => $payment->getShippingAmount() * 100,
						'seller_order_success_url' => "http://magentotest.com:8888/magento/crypto/redirect/success",
						'seller_order_failure_url' => "http://magentotest.com:8888/magento/crypto/redirect/failure",
						'seller_data' => array("magento_id" => $magento_order->getId())
					)
				)
			);
			Mage::log($magento_order->getId());
			#add field to order request for the items to be put into
			$itemsReq = $order_request->linkRequest('items');
			$magento_order_items = $magento_order->getAllVisibleItems();
			foreach ($magento_order_items as $item){
				#add each item to the order
				$itemsReq->post(array(
					'order_item' => array (
						'name' => $item->getName(),
						'price' => $item->getPrice() * 100,
						'quantity' => $item->getQtyOrdered(),
						'currency_code' => $currency,
					)
				));
			}
			#get checkout link
			$order_response = $order_request->getResponse();

			foreach($order_response->links as $link){
				if ($link->rel == "checkout"){
					Mage::getSingleton('customer/session')->setRedirectUrl($link->href);
					break;
				}
			}
			//TODO replace url with
			// Mage::getUrl('crypto/callback') . "/response";
			$this->setupWebhook($api_config, 'http://magentotest.com:8888/magento/crypto/callback/response');

			if (Mage::getSingleton('customer/session')->getRedirectUrl() === null){
				Mage::throwException("Error getting ZiftrPay Checkout url.");
			} else {
				$payment->setIsTransactionPending(true);
			}

		} catch (Ziftr\ApiClient\Exceptions\ValidationException $e){
			$str = print_r($e->getResponseBody(), true);
			Mage::log("ValidationException:\n" . $str . "\nFields:\n" . print_r($e->getFields(), true));
			Mage::throwException("ValidationError sending data to server: " . $e);
		} catch (Exception $e){
			Mage::log("Exception: " . print_r($e->getResponseBody(), true));
			Mage::throwException("Error getting response from server." . $e);
		}

		return $this;
	}

	public function getOrderPlaceRedirectUrl(){
		return Mage::getSingleton('customer/session')->getRedirectUrl();
	}

	private function setupWebhook($config, $desired_prefix){
		$webhook_request = new Ziftr\ApiClient\Request("/webhooks/", $config);
		$webhooks = $webhook_request->get()->getResponse();
		foreach ($webhooks->webhooks as $webhook_data){
			//check if hook has the uri $desired_prefix
			if ($webhook_data->webhook->uri_prefix == $desired_prefix){
				Mage::log("Webhook found for " . $desired_prefix);
				return;
			}
		}
		//create new webhook
		$webhook_request = new Ziftr\ApiClient\Request("/webhooks/", $config);
		$webhook_request->post(
			array(
				'webhook' => array(
					'resource_filter' => '/order/:id',
					'http_verb' => 'post',
					'uri_prefix' => $desired_prefix
				)
			)
		);
	}
}
