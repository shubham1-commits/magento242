<?php
namespace Elsnertech\Chatboat\Controller\Cart;

class Cart extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    protected $_productloader;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customer,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Catalog\Model\ProductFactory $_productloader,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Data\Form\FormKey $formKey
    ) {
        $this->_productRepository = $productRepository;
        $this->_cart = $cart;
        $this->_productloader = $_productloader;
        $this->request = $request;
        $this->customer = $customer;
        $this->formKey = $formKey;
        return parent::__construct($context);
    }

    public function execute()
    {
        $customer = $this->customer;
        $sku = $this->request->getParam('numone');
        //$qty = $this->request->getParam('cqty');
        $qty = 1;
        if ($customer->isLoggedIn()) {
            $customerId = $customer->getId();
            $a = $this->_productRepository->get($sku);
            $id = $a->getid();
            $producttype = $a->gettype_id();
            if ($producttype=="downloadable" or $producttype=="virtual" or $producttype=="simple") {
                $params = [
                    'form_key' => $this->formKey->getFormKey(),
                    'product' => $id,
                    'qty'   =>1
                ];
                $product = $this->_productRepository->getById($id);
                $proname = $product->getname();
                $this->_cart->addProduct($product, $params);
                $this->_cart->save();
                $res = '<div class="reci-wrapper" style="display: inline-block;"><p id="reci">'."Product"." ".$proname."is Add to cart".'</p></div>';
                echo $res;
            } elseif ($producttype=='bundle') {
                $product = $this->_productRepository->getById($id);
                $proname = $product->getname();
                $selectionCollection = $product->getTypeInstance(true)->getSelectionsCollection($product->getTypeInstance(true)->getOptionsIds($product), $product);
                foreach ($selectionCollection as $proselection) {
                    $params['bundle_option'][$proselection->getOptionId()] = $proselection->getSelectionId();
                }
                $this->_cart->addProduct($product, $params);
                $this->_cart->save();
                $res = '<div class="reci-wrapper" style="display: inline-block;"><p id="reci">'."Product"." ".$proname."is Add to cart".'</p></div>';
                echo $res;

            } elseif ($producttype=='grouped') {

                $product = $this->_productloader->create()->load($id);
                $childProductCollection = $product->getTypeInstance()->getAssociatedProducts($product);
                
                $child_qty = 2;

                foreach ($childProductCollection as $child) {
                    $super_group[] = $child_qty;
                }

                $params = ['qty'=>1];
                $this->_cart->addProduct($product, $params);
                $this->_cart->save();

            } else {
                $res = '<div class="reci-wrapper" style="display: inline-block;"><p id="reci">Product is-not avalible</p></div>';
                echo $res;
            }

        } else {
            echo "please sign-in";
        }
    }
}
