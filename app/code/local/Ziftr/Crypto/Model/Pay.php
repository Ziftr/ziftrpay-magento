<?php

class Ziftr_Crypto_Model_Pay extends Mage_Payment_Model_Method_Abstract
{
	protected $_code = 'crypto';

	protected $_isGateway = true;
	protected $_canAuthorize = true;
	protected $_canCapture = true;
	protected $_canCapturePartial = false;
	protected $_canRefund = false;
	protected $_canVoid = true;
	protected $_canUseInternal = true;
	protected $_canUseCheckout = true;

	protected $_formBlockType = "crypto/payform";
	protected $_infoBlockType = "crypto/payform";

}