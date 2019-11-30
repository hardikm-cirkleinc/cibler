<?php

namespace Cibler\Shop\Model;

class Cibler extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
	const CACHE_TAG = 'wio_cibler';

	protected $_cacheTag = 'wio_cibler';

	protected $_eventPrefix = 'wio_cibler';

	protected function _construct()
	{
		$this->_init('Cibler\Shop\Model\ResourceModel\Cibler');
	}

	public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getId()];
	}

	public function getDefaultValues()
	{
		$values = [];

		return $values;
	}
}
