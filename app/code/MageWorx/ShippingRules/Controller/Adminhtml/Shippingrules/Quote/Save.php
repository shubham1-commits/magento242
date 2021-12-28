<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Controller\Adminhtml\Shippingrules\Quote;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\InputFactory;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Magento\Store\Model\Store;
use MageWorx\ShippingRules\Api\RuleRepositoryInterface;
use MageWorx\ShippingRules\Controller\Adminhtml\Shippingrules\Quote as RuleParentController;
use Psr\Log\LoggerInterface;

/**
 * Class Save
 */
class Save extends RuleParentController
{
    /**
     * @var InputFactory
     */
    private $inputFilterFactory;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param FileFactory $fileFactory
     * @param Date $dateFilter
     * @param RuleRepositoryInterface $ruleRepository
     * @param LoggerInterface $logger
     * @param InputFactory $inputFilterFactory
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        FileFactory $fileFactory,
        Date $dateFilter,
        RuleRepositoryInterface $ruleRepository,
        LoggerInterface $logger,
        InputFactory $inputFilterFactory,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->inputFilterFactory = $inputFilterFactory;
        $this->dataObjectFactory  = $dataObjectFactory;
        parent::__construct($context, $coreRegistry, $fileFactory, $dateFilter, $ruleRepository, $logger);
    }

    /**
     * Shipping rule save action
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->getRequest()->getPostValue()) {
            $this->_redirect('mageworx_shippingrules/*/');
        }

        try {
            $id = (int)$this->getRequest()->getParam('rule_id');
            if ($id) {
                /** @var $model \MageWorx\ShippingRules\Model\Rule */
                $model = $this->ruleRepository->getById($id);
            } else {
                /** @var $model \MageWorx\ShippingRules\Model\Rule */
                $model = $this->ruleRepository->getEmptyEntity();
            }

            $this->_eventManager->dispatch(
                'adminhtml_controller_shippingrules_prepare_save',
                ['request' => $this->getRequest()]
            );
            $data = $this->getRequest()->getPostValue();

            $filterRules = [];
            if (!empty($data['from_date'])) {
                $filterRules['from_date'] = [$this->dateFilter];
            }
            if (!empty($data['to_date'])) {
                $filterRules['to_date'] = [$this->dateFilter];
            }

            /** @var \Magento\Framework\Filter\Input $inputFilter */
            $inputFilter = $this->inputFilterFactory->create();
            $inputFilter->addFilters($filterRules);
            $data = $inputFilter->filter($data);

            $validateResult = $model->validateData(
                $this->dataObjectFactory->create(
                    [
                        'data' => $data
                    ]
                )
            );
            if ($validateResult !== true) {
                foreach ($validateResult as $errorMessage) {
                    $this->messageManager->addErrorMessage($errorMessage);
                }
                $this->_session->setPageData($data);
                $this->_redirect('mageworx_shippingrules/*/edit', ['id' => $model->getId()]);

                return;
            }

            $data = $this->prepareData($data);
            $model->loadPost($data);
            $this->_session->setPageData($model->getData());

            $this->ruleRepository->save($model);
            $this->messageManager->addSuccessMessage(__('You saved the rule.'));
            $this->_session->setPageData(false);
            if ($this->getRequest()->getParam('back') == 'newAction') {
                $this->_redirect('mageworx_shippingrules/*/newAction');

                return;
            }
            if ($this->getRequest()->getParam('back')) {
                $this->_redirect('mageworx_shippingrules/*/edit', ['id' => $model->getId()]);

                return;
            }
            $this->_redirect('mageworx_shippingrules/*/');

            return;
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $id = (int)$this->getRequest()->getParam('rule_id');
            if (!empty($id)) {
                $this->_redirect('mageworx_shippingrules/*/edit', ['id' => $id]);
            } else {
                $this->_redirect('mageworx_shippingrules/*/new');
            }

            return;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('Something went wrong while saving the rule data. Please review the error log.')
            );
            $this->logger->critical($e);
            $data = !empty($data) ? $data : [];
            $this->_session->setPageData($data);
            $this->_redirect('mageworx_shippingrules/*/edit', ['id' => $this->getRequest()->getParam('rule_id')]);

            return;
        }
    }

    /**
     * Prepares specific data
     *
     * @param array $data
     * @return array
     */
    protected function prepareData($data)
    {
        if (isset($data['simple_action']) && !empty($data['simple_action'])) {
            $data['simple_action'] = implode(',', $data['simple_action']);
        }

        if (isset($data['days_of_week']) && !empty($data['days_of_week'])) {
            $data['days_of_week'] = implode(',', $data['days_of_week']);
        } else {
            $data['days_of_week'] = null;
        }

        if (isset($data['rule']['conditions'])) {
            $data['conditions'] = $data['rule']['conditions'];
        }

        if (isset($data['rule']['actions'])) {
            $data['actions'] = $data['rule']['actions'];
        }
        unset($data['rule']);

        if (array_search(Store::DEFAULT_STORE_ID, $data['store_ids']) !== false) {
            $data['store_ids'] = [Store::DEFAULT_STORE_ID];
        }

        if (!isset($data['use_time'])) {
            $data['use_time'] = 0;
        }

        if (!empty($data['display_error_message'])) {
            $data['display_error_message'] = 1;
        } else {
            $data['display_error_message'] = 0;
        }

        if (empty($data['display_all_methods_having_min_price'])) {
            $data['display_all_methods_having_min_price'] = 0;
        }

        unset($data['created_at']);
        unset($data['updated_at']);
        unset($data['changed_titles']['__empty']);

        return $data;
    }
}
