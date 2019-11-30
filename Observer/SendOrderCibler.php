<?php

namespace Cibler\Shop\Observer;

class SendOrderCibler implements \Magento\Framework\Event\ObserverInterface {

	protected $logger;
	protected $_customerFactory;
	protected $_ciblerFactory;
	protected $_productRepository;
	protected $scopeConfig;

	public function __construct(
		\Psr\Log\LoggerInterface $logger,
		\Magento\Customer\Model\CustomerFactory $customerFactory,
		\Cibler\Shop\Model\CiblerFactory $ciblerFactory,
		\Magento\Catalog\Model\ProductRepository $productRepository,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	) {
	    $this->logger = $logger;
	    $this->_customerFactory = $customerFactory;
	    $this->_ciblerFactory = $ciblerFactory;
	    $this->_productRepository = $productRepository;
	    $this->scopeConfig = $scopeConfig;
	}


	public function execute(\Magento\Framework\Event\Observer $observer) {
		$order = $observer->getEvent()->getOrder();
		$this->logger->debug($order->getState());

		if ($order instanceof \Magento\Framework\Model\AbstractModel) {
			if ($order->getState() == 'new') {
				$this->logger->debug("------ sendOrder -------");
				$this->sendOrder($order);
			}
		}
	}

	public function getCustomerById($id) {
        return $this->_customerFactory->create()->load($id);
    }

    public function getProductById($id)
	{
		return $this->_productRepository->getById($id);
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

	private function sendOrder($order) {
		if (empty($order)) {
            return;
        }

        $coupon = '';
        $shippingAddress = $order->getShippingAddress();
        $customer_id = $order->getCustomerId();
        $customer = $this->getCustomerById($customer_id);
        $cartId = (int) $order->getQuoteId();

        $cibler = $this->_ciblerFactory->create();
        $collection = $cibler->getCollection()->addFieldToFilter('id_cart',$cartId); 

        $cookie="";
        foreach ($collection as $values) {
            $cookie = isset($values['cookie']) ? $values['cookie']  : "not found" ;
        }

        /* coupon section start here */

        $coupon="";

        if ($order->getCouponCode()) {
            $coupon=$order->getCouponCode();
        }

        /* coupon section end here */

        /* customer gender start here */
		
        $genderText ="Robot";

        /* customer gender end here */

        /* orderlines start here */

        $items = $order->getAllItems();
        $productIds = array();

        foreach ($items as $i) {
            $productIds[$i->getProductId()] = $i->getQtyOrdered();
        }

        $order_lines = array();

        foreach ($productIds as $id => $qty) {
        	$product = $this->getProductById($id);

        	$name = $product->getName();
            $price = $product->getPrice();
            $cats = $product->getCategoryIds();

            $order_lines[] = array(
                "productId" => $id,
                "productName" => $name,
                "quantity" => $qty,
                "category" => $cats[0],
                "orderType" => "Standard", // can be "Standard" OR "Marketplace"
                "unitPrice" => $price,
                "ShelvingId" => 0,
                "SellerId" => '' // if orderType = Marketplace
            );
        }

		/* orderlines end here */

		/* orderpost start here */

		$key_api = $this->getKeyapi();
		$billingAddress = $order->getBillingAddress();

		$orderPost=array(
	      "orderContext"=>array(
	        "key" => $key_api,
	        "email" => $billingAddress->getEmail(),
	        "firstName" => $billingAddress->getFirstname(),
	        "lastName" => $billingAddress->getLastname(),
	        "gender" => $genderText,
	        "birthday" => '', // $customer->birthday,
	        "country" => $shippingAddress->getData("country_id"),
	        "postalCode" => $shippingAddress->getData("postcode"),
	        "customerGuid" => $shippingAddress->getId(),
	        "cookieId" => $cookie
	      ),
	      "orderInformation"=>array(
	        "clientStatus"=>"Standard",// si abonné PREMIUM "VIP" sinon "Standard"
	        "orderId" => $order->getId(),
	        "totalSpent" => $order->getGrandTotal(),
	        "giftCode" => $coupon,
	        "shippingCost" => $order->getShippingAmount(),
	        "discountAmount" => abs($order->getDiscountAmount()),
            "orderLines" => $order_lines
	      )
    	);

    	$url = $this->getEnvUrl();
        $path = "/api/campaignBehaviors/order/".$this->getCustomerId();

        $data_string = json_encode($orderPost);
        $this->logger->debug('sending order :'.print_r($data_string, true));

		/* orderpost end here */

		$ch = curl_init($url.$path);
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
		$this->logger->debug('return order :'.print_r($result, true));
	}

}
