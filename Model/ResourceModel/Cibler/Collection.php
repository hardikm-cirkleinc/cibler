<?php

namespace Cibler\Shop\Model\ResourceModel\Cibler;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
	protected $_idFieldName = 'id_cibler';
	protected $_eventPrefix = 'cibler_shop_collection';
	protected $_eventObject = 'cibler_collection';

	/**
	 * Define resource model
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->_init('Cibler\Shop\Model\Cibler', 'Cibler\Shop\Model\ResourceModel\Cibler');
	}

}
