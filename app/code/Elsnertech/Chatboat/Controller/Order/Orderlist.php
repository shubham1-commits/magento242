<?php
namespace Elsnertech\Chatboat\Controller\Order;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class Orderlist extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    protected $orderfactory;


    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $logincustomer,
        CollectionFactory $orderCollectionFactory,
        \Elsnertech\Chatboat\Model\Api $api,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Sales\Model\OrderFactory $orderfactory
    ) {
        $this->logincustomer = $logincustomer;
        $this->request = $request;
        $this->_json = $json;
        $this->_api = $api;
        $this->_orderfactory = $orderfactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $post = $this->request->getParam('numone');
        $order = $this->_orderfactory->create();
        $loggin = $this->logincustomer;
        if ($loggin->isLoggedIn()) {
              $customerId = $loggin->getId();
            $customerOrder = $this->orderCollectionFactory->create()->addFieldToFilter('customer_id', $customerId);
            $jsonArray = [];
            if (empty($customerOrder->getdata())) {
                $jsonArray[] = 'Order is not avalible';
                $res = $this->_json->serialize($jsonArray);
                echo $res;
            } else {
                foreach ($customerOrder as $key) {
                    $id =  $key->getincrement_id();
                    $this->_api->chatStore($post,$id);
                    $jsonArray[] = ["increment_id"=>$id];                 
                }
                $res = $this->_json->serialize($jsonArray);
                echo $res;
            }
        }
        else{
            $jsonArray[] = 'Click Yes to create new Account';
            $res = $this->_json->serialize($jsonArray);
            echo $res;
        }
    }
}
