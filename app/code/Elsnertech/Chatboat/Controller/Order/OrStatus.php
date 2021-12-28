<?php
namespace Elsnertech\Chatboat\Controller\Order;

class OrStatus extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    protected $_orderFactory;
    protected $_api;


    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Elsnertech\Chatboat\Model\Api $api,
         \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        $this->_pageFactory = $pageFactory;
        $this->request = $request;
        $this->_api = $api;
        $this->_json = $json;
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
        $status = $order->getstatus();
        $orderItems = $order->getAllItems();
        $grandtotal = $order->getgrand_total();
        $shipping = $order->getShippingAddress()->getData();
        $billing = $order->getBillingAddress()->getData();
        $itemQty = [];
        foreach ($orderItems as $item) {
            $productname =  $item->getname();
            $price = $item->getprice();
            if ($price!=0) {
                $res = [
                    "productname"=>$productname,
                    "status" => $status
                ];
                $rece = "productname is"." ".$productname." "."status is"." ".$status;
                //$this->_api->chatStore(" ",$rece);
                $res = $this->_json->serialize($res); 
                echo $res;
            }
        }
    }
}
