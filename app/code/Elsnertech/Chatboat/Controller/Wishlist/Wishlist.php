<?php
namespace Elsnertech\Chatboat\Controller\Wishlist;

class Wishlist extends \Magento\Framework\App\Action\Action
{
    protected $_wishlistRepository;
    protected $_productRepository;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customer,
        \Magento\Framework\App\RequestInterface $request,
        \Elsnertech\Chatboat\Model\Api $api,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Wishlist\Model\WishlistFactory $wishlistRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->_wishlistRepository= $wishlistRepository;
        $this->customer = $customer;
        $this->request = $request;
        $this->_json = $json;
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
                $productimages = $product->getMediaGalleryImages();
                foreach ($productimages as $productimage) {
                    $res = "<div class='product-img-wrap'><img src = ".$productimage['url']. " height=100 width=100 /></div><div class='product-action-btn'><button value='$sku' class='maincart1'>Addtocart</button><button value='$sku' class='wish'>wishlist</button></div>";
                }
                echo $res;
                
            } else{
                $res =  "customer is not login";
                $res = $this->_json->serialize($res);
                echo $res;
            }

        } catch (\Exception $e) {
            $res = "<div class='reci-wrapper' style='display: inline-block;'><p id='reci'>product is not avalible</p></div>";
            $res = $this->_json->serialize($res);
            echo $res;
        }
    }
}
