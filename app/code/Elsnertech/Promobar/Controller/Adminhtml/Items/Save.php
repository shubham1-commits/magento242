<?php

namespace Elsnertech\Promobar\Controller\Adminhtml\Items;

class Save extends \Elsnertech\Promobar\Controller\Adminhtml\Items
{
    public function execute()
    {

        if ($this->getRequest()->getPostValue()) {

            try {
                $model = $this->_objectManager->create('Elsnertech\Promobar\Model\Promobar');
                $data = $this->getRequest()->getPostValue();
                if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != '') {
                    try {
                        $uploaderFactory = $this->uploaderFactory->create(['fileId' => 'image']);
                        $uploaderFactory->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
                        $imageAdapter = $this->adapterFactory->create();
                        $uploaderFactory->addValidateCallback('image', $imageAdapter, 'validateUploadFile');
                        $uploaderFactory->setAllowRenameFiles(true);
                        $uploaderFactory->setFilesDispersion(true);
                        $mediaDirectory = $this->filesystem->getDirectoryRead($this->directoryList::MEDIA);
                        $destinationPath = $mediaDirectory->getAbsolutePath('elsnertech/promobar');
                        $result = $uploaderFactory->save($destinationPath);
                        if (!$result) {
                            throw new LocalizedException(
                                __('File cannot be saved to path: $1', $destinationPath)
                            );
                        }
                        
                        $imagePath = 'elsnertech/promobar'.$result['file'];
                        $data['image'] = $imagePath;
                    } catch (\Exception $e) {
                    }
                }
                $category = $this->getRequest()->getPostValue('data');
                foreach ($category as $value) {
                    $str = implode(',', $value);
                }
                $image = $this->getRequest()->getFiles();
                $store_id = implode(',', $this->getRequest()->getPostValue('store_id'));
                $data['category'] = $str;
                $data['store_id'] = $store_id;
                // $data['image'] = $image['image']['name'];
                
                $inputFilter = new \Zend_Filter_Input(
                    [],
                    [],
                    $data
                );
                $data = $inputFilter->getUnescaped();

                $id = $this->getRequest()->getParam('id');
                if ($id) {
                    $model->load($id);
                    if ($id != $model->getId()) {
                        throw new \Magento\Framework\Exception\LocalizedException(__('The wrong item is specified.'));
                    }
                }
                // echo "<pre>";
                // print_r($data);
                // die();
                $model->setData($data);
                $session = $this->_objectManager->get('Magento\Backend\Model\Session');
                $session->setPageData($model->getData());
                $model->save();
                $this->messageManager->addSuccess(__('You saved the item.'));
                $session->setPageData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('elsnertech_promobar/*/edit', ['id' => $model->getId()]);
                    return;
                }
                $this->_redirect('elsnertech_promobar/*/');
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $id = (int)$this->getRequest()->getParam('id');
                if (!empty($id)) {
                    $this->_redirect('elsnertech_promobar/*/edit', ['id' => $id]);
                } else {
                    $this->_redirect('elsnertech_promobar/*/new');
                }
                return;
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('Something went wrong while saving the item data. Please review the error log.')
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_objectManager->get('Magento\Backend\Model\Session')->setPageData($data);
                $this->_redirect('elsnertech_promobar/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        }
        $this->_redirect('elsnertech_promobar/*/');
    }
}


