<?php
namespace Elsnertech\Chatboat\Controller\Wishlist;

class Wishlistadd extends \Magento\Framework\App\Action\Action
{
    protected $_wishlistRepository;
    protected $_productRepository;
    protected $_api;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customer,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Elsnertech\Chatboat\Model\Api $api,
        \Magento\Wishlist\Model\WishlistFactory $wishlistRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->_wishlistRepository= $wishlistRepository;
        $this->customer = $customer;
        $this->request = $request;
        $this->_api = $api;
        $this->_json = $json;
        $this->_productRepository = $productRepository;
        return parent::__construct($context);
    }

    public function execute()
    {

        try {
            $customer = $this->customer;
            if ($customer->isLoggedIn()) {
                $sku = $this->request->getParam('numone');
                $customerId = $customer->getId();
                $product = $this->_productRepository->get($sku);
                $id = $product->getid();
                $product = $this->_productRepository->getById($id);
                $wishlist = $this->_wishlistRepository->create()->loadByCustomerId($customerId, true);
                $wishlist->addNewItem($product);
                $wishlist->save();
                $res = 'wishlist is Add';
                $this->_api->chatStore(" ",$res);
                echo $res;
            }

        } catch (\Exception $e) {
            $res = '<div class="reci-wrapper" style="display: inline-block;"><p id="reci">product is not</p></div>';
            echo $res;
        }
    }
}
