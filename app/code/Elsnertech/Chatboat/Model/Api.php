<?php
namespace Elsnertech\Chatboat\Model;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Api extends \Magento\Framework\Model\AbstractModel
{

    public function __construct(
        \Elsnertech\Chatboat\Model\CustomerchatFactory $customerchatFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Customer\Model\Session $customerSession
        )
    {
        $this->_customerchatFactory = $customerchatFactory;
        $this->date = $date;
        $this->json = $json;
        $this->_customerSession = $customerSession;
    }

    public function chatStore($sender,$receiver)
    {
        // try {
        //     $customer = $this->_customerSession;
        //     $customerId = $customer->getId();
        //     $cdate = $this->date->gmtDate();
        //     $data = $this->_customerchatFactory->create();
        //     $a  = $data->getCollection()->addFilter('customerid',['in' => $customerId]);
        //     foreach ($a as $key) {
        //         $id = $key->getid();
        //         $oldermessage = $key->getmessage();
        //     }
        //     if(isset($id)){
        //         $data->load($id);
        //     }
        //     if(!empty($oldermessage)){
        //         $oldermessage = json_decode($oldermessage,true);
        //         if($sender!=" "){
        //             $sender = "sender.".$sender;
        //             array_push($oldermessage,$sender,$cdate);
        //         }
        //         $receiver = "receiver.".$receiver;
        //         $c = "sender.reveiver.".$cdate;
        //         array_push($oldermessage,$receiver,$cdate);
        //         $oldermessage = json_encode($oldermessage);
        //         $data->setcustomerid($customerId);
        //         $data->setmessage($oldermessage);
        //         $data->setcreated_at($c);
        //         $data->save();
        //     } else {
        //         $receiver = ["receiver.".$receiver];
        //         $a = json_encode($receiver);
        //         $data->setcustomerid($customerId);
        //         $data->setmessage($a);
        //         $data->save();
        //     }


        // } catch (\Exception $e) {
        //     // $this->logger->critical($e->getMessage());
        // }
    }
}
    