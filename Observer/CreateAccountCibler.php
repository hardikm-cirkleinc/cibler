<?php

namespace Cibler\Shop\Observer;

class CreateAccountCibler implements \Magento\Framework\Event\ObserverInterface {

	protected $_customerRepositoryInterface;
	protected $logger;
	protected $scopeConfig;

	public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
    	$this->logger = $logger;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->scopeConfig = $scopeConfig;
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
		$customer = $observer->getEvent()->getCustomer();
		$firstName = $customer->getFirstname();
     	$lastName = $customer->getLastname();
    	$email = $customer->getEmail();

    	$cutomerData = array(
	        "firstName" => $firstName,
	        "lastName" => $lastName,
	        "email" => $email,
	        "sponsor" => "" //$_COOKIE["cibler_sponsor"]
        );

        $this->logger->debug(print_r($cutomerData, true));

        $url = $this->getEnvUrl();
        $path = "/api/users/customersidesubscription/".$this->getCustomerId();

        $data_string = json_encode($cutomerData);

        $ch = curl_init($url.$path);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
        	$ch,
        	CURLOPT_HTTPHEADER,
        	array(
        		'Content-Type: application/json',
        		'Content-Length: ' . strlen($data_string)
        	)
        );

        $result = curl_exec($ch);
        $this->logger->debug(print_r($result, true));
        $this->_customerRepositoryInterface->save($customer);
	}

}
