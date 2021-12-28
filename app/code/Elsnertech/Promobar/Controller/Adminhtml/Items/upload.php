<?php
namespace Elsnertech\Promobar\Controller\Adminhtml\Items;

public function __construct(
       Context $context, 
       \Magenest\ImageUpload\Model\ImageUploader $imageUploader
   ){
       $this->imageUploader = $imageUploader;
       parent::__construct($context);
   }

   public function execute()
   {
       $imageId = $this->_request->getParam('param_name', 'image');

       try {
           $result = $this->imageUploader->saveFileToTmpDir($imageId);
       } catch (Exception $e) {
           $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
       }
       return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
   }
