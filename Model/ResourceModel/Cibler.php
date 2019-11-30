<?php

namespace Cibler\Shop\Model\ResourceModel;

class Cibler extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
	
	public function __construct(
		\Magento\Framework\Model\ResourceModel\Db\Context $context
	)
	{
		parent::__construct($context);
	}
	
	protected function _construct()
	{
		$this->_init('wio_cibler', 'id_cibler');
	}
	
}
