<?php

namespace Cibler\Shop\Observer;

use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollection;

class CheckCouponCibler implements \Magento\Framework\Event\ObserverInterface {

	protected $_ruleCollection;
	protected $_customerSession;
	protected $_storeManager;
	protected $logger;
	protected $scopeConfig;
	protected $ruleFactory;

	public function __construct(
		RuleCollection $ruleCollection,
		\Magento\Customer\Model\SessionFactory $customerSession,
		\Magento\Checkout\Model\Cart $cart,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Psr\Log\LoggerInterface $logger,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\SalesRule\Model\RuleFactory $ruleFactory
	) {
	    $this->_ruleCollection = $ruleCollection;
	    $this->_customerSession = $customerSession->create();
	    $this->_cart = $cart;
	    $this->_storeManager = $storeManager;
	    $this->logger = $logger;
	    $this->scopeConfig = $scopeConfig;
	    $this->ruleFactory = $ruleFactory;
	}

	public function CheckCustomerIsLoggedIn() {
        return $this->_customerSession->isLoggedIn();
    }

    public function getCartData() {
        return $this->_cart->getQuote();
    }

    public function getCurrentCurrencyCode() {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    }

    public function getCustomerData() {
        if ($this->_customerSession->isLoggedIn()) {
            return $this->_customerSession->getCustomerData();
        }
        return false;
    }

    public function getKeyapi() {
    	$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    	return $this->scopeConfig->getValue('cshop/general/keyapi', $storeScope);
    }

    public function getCustomerId() {
    	$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    	return $this->scopeConfig->getValue('cshop/general/customerid', $storeScope);
    }

    public function getEnvUrl() {
    	$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    	return $this->scopeConfig->getValue('cshop/general/envurl', $storeScope);
    }

	public function execute(\Magento\Framework\Event\Observer $observer) {
		$event = $observer->getEvent();
        $event_name = $event->getName();

        if($event_name == 'controller_action_predispatch_checkout_cart_couponpost') {
        	$controller = $observer->getControllerAction();
        	$code = $controller->getRequest()->getParam('coupon_code');
        } else {
        	$code = $observer->getData('coupon_code');
        }

		$result = $this->CheckCoupon($code);
		
        if ($result) {
        	$jsn = json_decode($result);
			
        	if($jsn->canUse) {
        		$cart = $this->getCartData();
		        $rules_object = $this->ruleFactory->create();
		        $rules = $rules_object->getCollection();
		        $coupon_collection = array();

		        foreach ($rules as $coupon) {
		            $coupon_collection[] = $coupon['code'];
		        }
		        
		        $nb_coupon = $cart->getCouponCode();
		        if($jsn->combinable) {
		        	if (!in_array($code, $coupon_collection)) {
		        		$this->AddCoupon($code, $result);
		        	}
		        } else {
		        	if ($nb_coupon == 0) {
		        		if (!in_array($code, $coupon_collection)) {
		        			$this->AddCoupon($code, $result);
		        		}
		        	} else {
		        		$this->logger->debug("Cibler submitAddDiscount false.");
		        	}
		        }
        	}
        } else {
			$this->logger->debug("Cabler il y a aucun retour sur la requête API , Svp verifier votre Acces API");
        }

        return $this;
	}

	public function AddCoupon($code,$json) {
		$result= json_decode($json);

        $ruleModel = $this->ruleFactory->create();

        $ruleModel->setName($result->description)
            ->setDescription($result->description)
            ->setFromDate(date('Y-m-d H:i:s'))
            ->setToDate(date('Y-m-d H:i:s', strtotime("+10 day")))
            ->setUsesPerCustomer(1)
            ->setCustomerGroupIds(array('0','1','2','3'))
            ->setIsActive('1')
            ->setDiscountQty(1)
            ->setApplyToShipping(0)
            ->setTimesUsed(1)
            ->setWebsiteIds(array('1',))
            ->setCouponType('2')
            ->setCouponCode($code)
            ->setUsesPerCoupon(NULL);

        if ($result->discountType == "PERCENT") {
           
            $ruleModel->setSimpleAction('by_percent')
            ->setDiscountAmount($result->value);
        }
        if ($result->discountType == "VALUE") { 
            $ruleModel->setSimpleAction('by_fixed') 
            ->setDiscountAmount($result->value);
        }

        $ruleModel->save();

        $this->logger->debug("Cabler: coupon created " . $code);

	}

	public function CheckCoupon($code) {
		$customer = $this->CheckCustomerIsLoggedIn();
		/*if (!$customer) {
			return;
		}*/

		$cartItems = $this->getCartData();

		$items = array();
		$grandTotal = $cartItems->getGrandTotal();

		foreach ($cartItems->getAllItems() as $item) {
			$categoriesIds = $item->getProduct()->getCategoryIds();
			
			if (count($categoriesIds)) {
                $categoryId = $categoriesIds[0];
            } else {
				$categoryId = 0;
            }
			
			$items[] = array(
                "productId" => $item->getProductId(),
                "quantity" => $item->getQty(),
                "name" => $item->getProduct()->getName(),
                "unitPrice" => $item->getProduct()->getPrice(),
                "priceCurrencyCode" => $this->getCurrentCurrencyCode(),
				"category" => $categoryId
            );
		}

		if(count($items) > 0) {
			$total = $cartItems->getGrandTotal();
		}

		$customerData = $this->getCustomerData();
		
		$custEmail = "";

        if ($customer) {
            $custEmail = $customerData->getEmail();
        }

		$post = array(
            "email" => $custEmail,
            "code" => $code,
            "cart" => array(
                "total" => $total,
                "items"=>$items
            )
    	);

    	$this->logger->debug("return coupon: " . print_r($post, true));

    	$env_url = $this->getEnvUrl();
        $customer_id = $this->getCustomerId();

        $path = "/api/giftCodes/validate/".$customer_id;
        $data_string = json_encode($post);
        $ch = curl_init($env_url.$path);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
        );
        $result = curl_exec($ch);
        curl_close($ch);
        $this->logger->debug("return coupon: " . print_r($result, true));
        return $result;
	}

}
