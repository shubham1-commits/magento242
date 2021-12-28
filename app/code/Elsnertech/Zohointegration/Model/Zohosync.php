<?php
namespace Elsnertech\Zohointegration\Model;

/**
 * Class Zohosync
 *
 * @package Elsnertech\Zohointegration\Model
 */
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Zohosync extends \Magento\Framework\Model\AbstractModel
{
    protected $_curl;
    private $scopeConfig;
    protected $_helper;
    protected $_productloader;
    protected $orderFactory;

    public function __construct(
        \Elsnertech\Zohointegration\Helper\Data $helper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Catalog\Model\ProductFactory $_productloader,
        \Magento\Framework\HTTP\Client\Curl $curl,
        ScopeConfigInterface $scopeConfig,
        \Elsnertech\Zohointegration\Model\Api $api,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Customer\Model\CustomerFactory $_customerloader
    ) {
        $this->_helper = $helper;
        $this->_productloader = $_productloader;
        $this->_orderFactory = $orderFactory;
        $this->_Api = $api;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->_customerloader = $_customerloader;
        $this->_curl = $curl;
        $this->scopeConfig = $scopeConfig;
    }

    public function zohoCustomercron($post)
    {
        $shipping_address=" ";
        $billing_address=" ";
        $bphone = " ";
        $sphone = " ";

        foreach ($post as $id) {
            $customer = $this->_customerloader->create();
            $customer = $customer->load($id);
            $status = $customer->getlisted_status();

            $email = $customer->getemail();
            $billingaddress = $customer->getDefaultBillingAddress();
            $shippingaddress = $customer->getDefaultShippingAddress();
            if (!empty($billingaddress)) {

                $billing_address = [
                        "attention"=> $customer->getname(),
                        "address"=>$billingaddress->getData('street') ,
                        "street2"=> $billingaddress->getData('street'),
                        "city"=>$billingaddress->getData('city'),
                        "state"=> $billingaddress->getData('region'),
                        "company"=> $shippingaddress->getData('company'),
                        "zip"=>$billingaddress->getData('postcode'),
                        "country"=> $billingaddress->getData('country_id')
                    ];

            }

            if (!empty($shippingaddress)) {
                $sphone = $shippingaddress->getData('telephone');
                $bphone = $billingaddress->getData('telephone');
                $shipping_address = [
                    "attention" => $customer->getname(),
                    "address"=> $shippingaddress->getData(' street') ,
                    "street2"=> $shippingaddress->getData('street'),
                    "city"=>$shippingaddress->getData('city'),
                    "state"=> $shippingaddress->getData('region'),
                    "company"=> $shippingaddress->getData('company'),
                    "zip"=>$shippingaddress->getData('postcode'),
                    "country"=>$shippingaddress->getData('country_id')
                ];
            }
           
           $a = [[
                   "first_name"=> $customer->getFirstname(),
                   "last_name"=> $customer->getLastname(),
                   "email"=> $email,
                   "phone"=> $bphone,
                   "mobile"=> $sphone,
                   "is_primary_contact"=> true
               ]];

           $data = [
               'first_name' => $customer->getFirstname(),
               'last_name' => $customer->getLastname(),
               'contact_name' => $customer->getname(),
               'contact_persons' =>$a
           ];
           if ($status =="Non Listed") {
               $this->_curl->setHeaders($this->_helper->getHeaders());
               $this->_curl->post($this->_helper->getCustomerApi(), json_encode($data));
               $response = $this->_curl->getBody();
               $response = json_decode($response, true);
               if (isset($response['contact']['contact_id'])) {
                   $rid = (int)$response['contact']['contact_id'];
                   $this->_Api->ZohoId($email, $rid);
               }
           }
        }
    } 

    public function zohoProductcron($id)
    { 
        $product = $this->_productloader->create();

        foreach ($id as $key) {
            $product = $product->load($key);
            $producttype = $product->gettypeid();
            $zoholistedstatus = $product->getlisted_status();

            if ($zoholistedstatus!="Listed") {

                if ($producttype=="simple") {
                    $this->_Api->simpleProduct($key,"TRUE");
                } elseif ($producttype=="configurable") {
                    $this->_Api->ItemGroup($key,"TRUE");
                } elseif ($producttype=="virtual") {
                    $this->_Api->Virtualproduct($key,"TRUE");
                } elseif ($producttype=="downloadable") {
                    $this->_Api->Virtualproduct($key,"TRUE");
                } elseif ($producttype=="grouped") {
                    $this->_Api->compositeProduct($key,"TRUE");
                } else {
                    $this->_Api->bundleProduct($key,"TRUE");
                }

            }  
        }
    }    
}
  