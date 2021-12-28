<?php
namespace Elsnertech\Chatboat\Controller\Address;

class Address extends \Magento\Framework\App\Action\Action
{

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Customer\Model\Session $customer,
        \Elsnertech\Chatboat\Model\Api $api,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Customer\Model\CustomerFactory $customerFactory
    ) {
        $this->customer = $customer;
        $this->json = $json;
        $this->_api = $api;
        $this->request = $request;
        $this->customerFactory = $customerFactory;
        return parent::__construct($context);
    }

    public function execute()
    {
        $customer = $this->customer;
        $post = $this->request->getParam('numone');
        if ($customer->isLoggedIn()) {
            $customerId = $customer->getId();
              $address = $this->customerFactory->create();
              $address = $address->load($customerId);
              $addresses = $address->getAddresses();
            if (!empty($addresses)) {
                foreach ($addresses as $key) {
                    $postcode = $key->getpostcode();
                    $city = $key->getcity();
                    $company = $key->getcompany();
                    $region = $key->getregion();
                    $country = $key->getcountry();
                    $phone = $key->gettelephone();
                    
                    $res = [
                        "postcode"=>$postcode,
                        "city" => $city,
                        "company"=>$company,
                        "country"=>$country,
                        "phone"=>$phone,
                        "region"=>$region
                    ];
                    $address = 'company name is'.$company.'city is'." ".$city."country is".$country."phone is"." ".$phone." "."region is"." ".$region;
                    $this->_api->chatStore($post,$address);
                    $res = $this->json->serialize($res);
                    echo $res;
                }
            } else {
                $res = 'Address is not set';
                $res = $this->json->serialize($res);
                echo $res;
            }
        } else {
            $res = 'Click Yes to create new Account';
            $res = $this->json->serialize($res);
            echo $res;
        }
    }
}
