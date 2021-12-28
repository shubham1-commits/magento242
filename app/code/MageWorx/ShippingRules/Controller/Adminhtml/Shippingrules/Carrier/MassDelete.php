<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Controller\Adminhtml\Shippingrules\Carrier;

use MageWorx\ShippingRules\Controller\Adminhtml\Shippingrules\Base\MassDeleteAbstract as BaseMassDelete;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Class MassDelete
 */
class MassDelete extends BaseMassDelete
{
    /**
     * MassDelete constructor.
     *
     * @param Context $context
     * @param Filter $filter
     * @param \MageWorx\ShippingRules\Model\ResourceModel\Carrier\CollectionFactory $collectionFactory
     * @param string $aclResourceName
     */
    public function __construct(
        Context $context,
        Filter $filter,
        \MageWorx\ShippingRules\Model\ResourceModel\Carrier\CollectionFactory $collectionFactory,
        $aclResourceName = 'MageWorx_ShippingRules::carrier'
    ) {
        parent::__construct($context, $filter, $collectionFactory, $aclResourceName);
    }
}
