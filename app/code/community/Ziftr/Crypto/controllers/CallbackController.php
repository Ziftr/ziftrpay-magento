<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category    Ziftr
 * @package     Ziftr_Crypto
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @generator   http://www.mgt-commerce.com/kickstarter/ Mgt Kickstarter
 */

require_once(Mage::getModuleDir('ziftr-php', 'Ziftr_Crypto') . "/ziftr-php/ziftr-api.php");

class Ziftr_Crypto_CallbackController extends Mage_Core_Controller_Front_Action
{

	public static $paidStatuses = array("Shipping", "Shipped", "Delivered", "RMA");

	public function responseAction()
	{
		#return if not a post
        if (!$this->getRequest()->isPost()) {
            return;
        }
        $post_data = json_decode(file_get_contents('php://input'));
        $magento_order_id = $post_data->order->seller_data->magento_id;

        #get ziftr order id from post data
        $id = $post_data->order->id;
        $api_config = new Ziftr\ApiClient\Configuration();
		$api_config->load_from_array(array(
			'host' => 'sandbox.fpa.bz',
			'port' => 443,
			'private_key' => Mage::getStoreConfig('payment/crypto/secret_key'),
			'publishable_key' => Mage::getStoreConfig('payment/crypto/publishable_key')
			));
		#make a GET request for the order items of this order
		$order_items_request = new Ziftr\ApiClient\Request("/orders/" . $id . "/order_items", $api_config);
		$order_items_response = $order_items_request->get()->getResponse();
		#loop through each order item and GET status of order item and populate array of order items and statuses
		$all_status = array();
		foreach ($order_items_response->order_items as $item) {
			$order_item_request = new Ziftr\ApiClient\Request("/order_items/" . $item->order_item->id . "/order_item_statuses", $api_config);
			$order_item_response = $order_item_request->get()->getResponse();
			array_push($all_status, $this->mostRecentOrderStatus($order_item_response));
		}
		#update status in magento database if possible
		$this->updateOrderStatus($all_status, $magento_order_id);
				
	}

	#returns the most recent order status of an order item
	private function mostRecentOrderStatus($order_item_response){
		$most_recent_status = array();
		#order item response contains history of all order statuses,loop through and find recent
		foreach ($order_item_response as $orderHistory){
			foreach ($orderHistory as $order_status){
				$order_item_status = $order_status->order_item_status;
				$status_date = strtotime($order_item_status->created_at);
				$type = $order_item_status->type;
				if (empty($most_recent_status) || $status_date > $most_recent_status["created_at"]){
					$most_recent_status = array("created_at" => $status_date, "type" => $type);
				}
			}
		}
		return $most_recent_status["type"];
	}

	private function updateOrderStatus($all_status, $magento_id){
		$first_status = null;
		foreach ($all_status as $status) {
			if ($first_status == null){
				$first_status = $status;
			}
			if ($status != $first_status){
				#not all items in this order have the same status we have inconsistent order items 
				#(i.e. one item was paid for but another was not in the same order), can't automatically update order
				return;
			}
		}
		#all items have the same status and if the status is paid we can process the payment
		if (in_array($first_status, Ziftr_Crypto_CallbackController::$paidStatuses)) {
			$order = Mage::getModel('sales/order')->load($magento_id);
			$payment = $order->getPayment();
			$payment->registerCaptureNotification($order->getGrandTotal());
			$order->save();
		}
	}

}