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
		$order_configuration = new Ziftr\ApiClient\Configuration();
		$order_configuration->load_from_array(array(
			'host' => 'sandbox.fpa.bz',
			'port' => 443,
			'private_key' => Mage::getStoreConfig('payment/crypto/secret_key'),
			'publishable_key' => Mage::getStoreConfig('payment/crypto/publishable_key')
			));
		
		$order_request = new Ziftr\ApiClient\Request("/orders/", $order_configuration);
		Mage::log(Mage::getUrl('crypto/index') . 'success');
		try {

			$magento_order = $payment->getOrder();
			$currency = $magento_order->getBaseCurrencyCode();
			
			$order_request = $order_request->post(
				array(
					'order' => array(
						'currency_code' => "USD",
						'is_shipping_required' => true,
						'shipping_price' => $payment->getShippingAmount() * 100,
						'seller_order_success_url' => Mage::getUrl('crypto/index') . 'success'),
						'seller_order_failure_url' => Mage::getUrl('crypto/index') . 'failure')
					)
				)
			);
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
						'currency_code' => $currency
					)
				));
			}
			// $itemsReq->getResponse();
			#get checkout link
			$order_response = $order_request->getResponse();
			foreach($order_response->links as $link){
				if ($link->rel == "checkout"){
					Mage::getSingleton('customer/session')->setRedirectUrl($link->href);
					break;
				}
			}

		} catch (Exception $e){
			Mage::throwException("Error getting response from ZiftrPay server");
		}


		return $this;
	}

	public function getOrderPlaceRedirectUrl(){
		return Mage::getSingleton('customer/session')->getRedirectUrl();
	}


}