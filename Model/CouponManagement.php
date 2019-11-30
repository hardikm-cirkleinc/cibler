<?php

namespace Cibler\Shop\Model;

use \Magento\Quote\Api\CouponManagementInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

 
class CouponManagement extends  \Magento\Quote\Model\CouponManagement
{
    /**
     * {@inheritdoc}
     */
    public function set($cartId, $couponCode)
    {
    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    $eventManager = $objectManager->create('\Magento\Framework\Event\Manager');
    $eventManager->dispatch('check_coupon_success',['coupon_code' => $couponCode]);
    
        // \Magento\Framework\Event\Manager::eventManager()->dispatch('check_coupon_success');
        parent::set($cartId, $couponCode);
    }
   
}
