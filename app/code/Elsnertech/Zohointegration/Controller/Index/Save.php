<?php
namespace Elsnertech\Zohointegration\Controller\Index;

class Save extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;
	protected $_curl;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\HTTP\Client\Curl $curl,
		 \Magento\Framework\App\RequestInterface $request,
		\Magento\Framework\View\Result\PageFactory $pageFactory)
	{
		$this->_pageFactory = $pageFactory;
		$this->_curl = $curl;
		$this->request = $request;
		return parent::__construct($context);
	}

	public function execute()
	{

		try{
			$code = $this->request->getParam('code');
			$client_id = $this->request->getParam('client_id');
			$client_secret = $this->request->getParam('client_secret');
			$redirect_uri = $this->request->getParam('redirect_uri');

			$url="https://accounts.zoho.com/oauth/v2/token?code=".$code."&client_id=".$client_id."&client_secret=".$client_secret."&redirect_uri=".$redirect_uri."&grant_type=authorization_code";

	        $this->_curl->post($url,"");
	        $response = $this->_curl->getBody();
	        $response = json_decode($response, true);
	        print_r($response['refresh_token']);	
		} catch (\Exception $e) { 
			echo "Please Enter valid Information";		
		}
	
	}
}	