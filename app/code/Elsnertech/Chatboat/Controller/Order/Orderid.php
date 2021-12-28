<?php
namespace Elsnertech\Chatboat\Controller\Order;

class Orderid extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    protected $_orderFactory;


    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Elsnertech\Chatboat\Model\Api $api,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        $this->_pageFactory = $pageFactory;
        $this->request = $request;
        $this->_json = $json;
        $this->_api = $api;
        $this->_orderFactory = $orderFactory;
        return parent::__construct($context);
    }

    public function execute()
    {
        $post = $this->request->getParam('numone');
        $order = $this->_orderFactory->create();
        $order = $order->loadByIncrementId($post);
        $shipcharge = $order->getshipping_amount();
        $customerid = $order->getCustomerId();
        $orderItems = $order->getAllItems();
        $grandtotal = $order->getbase_grand_total();
        $shipping = $order->getShippingAddress()->getData();
        $billing = $order->getBillingAddress()->getData();
        $itemQty = [];
        foreach ($orderItems as $item) {
            $productname =  $item->getname();
            $price = $item->getprice();
            if ($price!=0) {
                $res = [
                    "productname"=>$productname,
                    "price" => $grandtotal,
                    "region" => $shipping['region'],
                    "postcode" => $shipping['postcode'],
                    "street" => $shipping['street'],
                    "telephone" =>$shipping['telephone']
                ];
                $r = "product name is".$productname."price is"." ".$grandtotal."region is
                ".$shipping['region']."street is".$shipping['street'];
                //$this->_api->chatStore(" ",$r);
                $res = $this->_json->serialize($res); 
                echo $res;
            }
        }
    }
}
