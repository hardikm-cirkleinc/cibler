<?php

namespace Cibler\Shop\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Ciblerpoint extends Template {

	protected $_request;
	protected $scopeConfig;
    protected $_ciblerFactory;
    protected $_storeManager;
    protected $_registry;
    protected $_stockItemRepository;

	public function __construct(
        Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Cart $cart,
        \Cibler\Shop\Model\CiblerFactory $ciblerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository,
        array $data = []
    )
    {
    	$this->_request = $request;
    	$this->scopeConfig = $scopeConfig;
    	$this->_cart = $cart;
        $this->_ciblerFactory = $ciblerFactory;
        $this->_storeManager = $storeManager;
        $this->_registry = $registry;
        $this->_stockItemRepository = $stockItemRepository;
        parent::__construct($context, $data);
    }

    public function getFullActionName() {
        return $this->_request->getFullActionName();
    }

    public function getCustomerId() {
    	$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    	return $this->scopeConfig->getValue('cshop/general/customerid', $storeScope);
    }

    public function getCartId() {
    	return $this->_cart->getQuote()->getId();
    }

    public function checkCartId($cartId) {
        $cibler = $this->_ciblerFactory->create();
        $collection = $cibler->getCollection()->addFieldToFilter('id_cart',$cartId); 
        return count($collection);
    }

    public function insertRecord($cartId, $cookieCiblerId) {
        $cibler = $this->_ciblerFactory->create();
        $cibler->setCookie($cookieCiblerId);
        $cibler->setIdCart($cartId);
        $cibler->save();
    }

    public function getCartData() {
        return $this->_cart->getQuote();
    }

    public function getCurrentCurrencyCode() {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    }

    public function encodeData($data) {
        return base64_encode($data);
    }

    public function getCurrentProduct()
    {       
        return $this->_registry->registry('current_product');
    }

    public function getCurrentCategory()
    {       
        return $this->_registry->registry('current_category');
    }

    public function getStockItem($productId)
    {
        return $this->_stockItemRepository->get($productId);
    }
}
