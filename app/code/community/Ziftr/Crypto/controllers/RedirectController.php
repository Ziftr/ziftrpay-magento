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

class Ziftr_Crypto_RedirectController extends Mage_Core_Controller_Front_Action
{
	public function successAction()
	{
		$this->_redirect('checkout/onepage/success', array('_secure'=>true));
	}

	public function failureAction()
	{
		$order = Mage::getModel('sales/order')->load($_GET["oid"]);
		#cancel order
        $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true);
        $order->save();

        #restore customer's cart
		$quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
		if ($quote->getId()){
			$quote->setIsActive(true)->setReservedOrderId(NULL)->save();
			Mage::getModel('checkout/session')->replaceQuote($quote);				
		}
		Mage::getModel('checkout/session')->unsLastRealOrderId();				
		$this->_redirect('checkout/cart');
	}

}