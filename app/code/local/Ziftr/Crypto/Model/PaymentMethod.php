<?php

class Ziftr_Crypto_Model_Pay extends Mage_Payment_Model_Method_Abstract
{
	protected $_code = 'pay';

	protected $_isGateway = true;
	protected $_canAuthorize = true;
	protected $_canCapture = true;
	protected $_canCapturePartial = false;
	protected $_canRefund = false;
	protected $_canVoid = true;

}