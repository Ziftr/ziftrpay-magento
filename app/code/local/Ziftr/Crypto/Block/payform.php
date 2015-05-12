<?php
class Ziftr_Crypto_Block_PayForm extends Mage_Payment_Block_Form
{
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate('payment/form/ziftrpayform.phtml');
	}

	public function assignData($data)
	{
		if (!($data instanceof Varien_Object)){
			$data = new Varien_Object($data);
		}
	}

	public function validate()
	{

	}
}