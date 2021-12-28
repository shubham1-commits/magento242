<?php
namespace Elsnertech\Zohointegration\Block\Adminhtml;

class Syncall extends \Magento\Backend\Block\Widget\Grid\Container
{
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        array $data = []
    ) {        
        parent::__construct($context, $data);
    }   protected function _construct()
    {
        $urlData = $this->getRequest()->getParams();       
        $this->_controller = 'adminhtml_listproduct';
        $this->_blockGroup = 'Company_Module';
        $this->_headerText = __('Manage products');
        $this->_addButtonLabel = __('Save');
        $this->removeButton('add');
    }    

    public function _prepareLayout()
    {
        $this->_addButtonLabel->add(
                'nameofbutton',
                [
                    'label' => __('Add Selected Products'),
                    'onclick' => 'setLocation(\'' . $this->getUrl('Zohoinventory/sync/customerselected') . '\')',
                    'class' => 'add primary'
                ],
                0
        );
        return parent::_prepareLayout();
    }
}