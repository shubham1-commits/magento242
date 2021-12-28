<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Controller\Adminhtml\Shippingrules\Region;

use Magento\Framework\Controller\ResultFactory;
use MageWorx\ShippingRules\Controller\Adminhtml\Shippingrules\Base\MassChangeStatusAbstract;
use MageWorx\ShippingRules\Model\Region as RegionModel;

/**
 * Class MassChangeStatus
 */
class MassChangeStatus extends MassChangeStatusAbstract
{
    /**
     * Update is active status
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        try {
            $collection   = $this->filter->getCollection($this->collectionFactory->create());
            $updatedCount = 0;
            $ids = $collection->getAllIds();
            if ($ids) {
                $table        = $collection->getTable(RegionModel::EXTENDED_REGIONS_TABLE_NAME);
                $updatedCount = $collection->getConnection()->update(
                    $table,
                    [
                        $this->activeFieldName => $this->getRequest()->getParam($this->activeRequestParamName)
                    ],
                    [$collection->getIdFieldName() . ' IN (?)' => $ids]
                );
            }

            if ($updatedCount) {
                $this->messageManager->addSuccessMessage(__('A total of %1 record(s) were updated.', $updatedCount));
            }

            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath($this->redirectUrl);

            return $resultRedirect;
        } catch (\Exception $e) {
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $this->messageManager->addErrorMessage($e->getMessage());
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

            return $resultRedirect->setPath($this->redirectUrl);
        }
    }
}
