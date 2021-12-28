<?php
namespace Elsnertech\Chatboat\Controller\Order;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class OrderStatus extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    protected $_api;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $logincustomer,
        CollectionFactory $orderCollectionFactory,
        \Elsnertech\Chatboat\Model\Api $api,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Customer\Model\CustomerFactory $_customerloader
    ) {
        $this->_customerloader = $_customerloader;
        $this->logincustomer = $logincustomer;
        $this->json = $json;
        $this->request = $request;
        $this->_api = $api;
        $this->orderCollectionFactory = $orderCollectionFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $loggin = $this->logincustomer;
        $post = $this->request->getParam('numone');
        if ($loggin->isLoggedIn()) {
            $customerId = $loggin->getId();
            $customerOrder = $this->orderCollectionFactory->create()->addFieldToFilter('customer_id', $customerId);
            $t = count($customerOrder);
            $data = $customerOrder->getdata();
            if ($t!=0) {
                foreach ($data as $key) {
                    $id = $key['increment_id'];
                    //$this->_api->chatStore($post,$id);
                    $post = " ";
                    $jsonArray[] = ["increment_id"=>$id];
                }
                $res = $this->json->serialize($jsonArray);
                echo $res;
            } else {
                $jsonArray[] = "Order is not avalible";
                $res = $this->json->serialize($jsonArray);
                echo $res;
            }
           
        } else {
            $res[] = 'Click Yes to create new Account';
            $res = $this->json->serialize($res);
            echo $res;
        }
    }
}
