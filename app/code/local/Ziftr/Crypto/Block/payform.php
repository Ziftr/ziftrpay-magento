<?php
class Ziftr_Crypto_Block_PayForm extends Mage_Payment_Block_Form
{
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate('payment/form/ziftrpayform.phtml');
	}
}