<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Model\ResourceModel\Rate\Grid;

use Magento\Backend\Model\Session;
use Magento\Framework\DB\Sql\ExpressionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageWorx\ShippingRules\Helper\Data;
use MageWorx\ShippingRules\Model\ResourceModel\Rate\Collection as RateCollection;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\DB\Select;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;

/**
 * Class Collection
 */
class Collection extends RateCollection implements SearchResultInterface
{
    /**
     * Aggregations
     *
     * @var \Magento\Framework\Api\Search\AggregationInterface
     */
    protected $aggregations;

    /**
     * @var int|null
     */
    protected $currentMethodCode;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * constructor
     *
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param TimezoneInterface $date
     * @param StoreManagerInterface $storeManager
     * @param Data $helper
     * @param ExpressionFactory $expressionFactory
     * @param Session $session
     * @param RequestInterface $requestInterface
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|mixed $mainTable
     * @param AbstractDb $eventPrefix
     * @param string $eventObject
     * @param string $resourceModel
     * @param string $model
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
     */
    public function __construct(
        EntityFactory $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        TimezoneInterface $date,
        StoreManagerInterface $storeManager,
        Data $helper,
        ExpressionFactory $expressionFactory,
        Session $session,
        RequestInterface $requestInterface,
        $mainTable,
        $eventPrefix,
        $eventObject,
        $resourceModel,
        $model = 'Magento\Framework\View\Element\UiComponent\DataProvider\Document',
        $connection = null,
        AbstractDb $resource = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $date,
            $storeManager,
            $helper,
            $expressionFactory,
            $connection,
            $resource
        );
        $this->_eventPrefix = $eventPrefix;
        $this->_eventObject = $eventObject;
        $this->_init($model, $resourceModel);
        $this->setMainTable($mainTable);
        $this->_setIdFieldName('rate_id');
        $this->session = $session;
        $this->request = $requestInterface;
    }

    /**
     * @return \Magento\Framework\Api\Search\AggregationInterface
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @param \Magento\Framework\Api\Search\AggregationInterface $aggregations
     * @return $this
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;

        return $this;
    }

    /**
     * Retrieve all ids for collection
     * Backward compatibility with EAV collection
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAllIds($limit = null, $offset = null)
    {
        return $this->getConnection()->fetchCol($this->_getAllIdsSelect($limit, $offset), $this->_bindParams);
    }

    /**
     * Create all ids retrieving select with limitation
     *
     * @param int $limit
     * @param int $offset
     * @return Select
     */
    protected function _getAllIdsSelect($limit = null, $offset = null)
    {
        $idsSelect = clone $this->getSelect();
        $idsSelect->reset(Select::ORDER);
        $idsSelect->reset(Select::LIMIT_COUNT);
        $idsSelect->reset(Select::LIMIT_OFFSET);
        $idsSelect->reset(Select::COLUMNS);
        $idsSelect->columns($this->getResource()->getIdFieldName(), 'main_table');
        $idsSelect->limit($limit, $offset);

        return $idsSelect;
    }

    /**
     * Get search criteria.
     *
     * @return \Magento\Framework\Api\SearchCriteriaInterface|null
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * Set search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return $this
     */
    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    /**
     * Get total count.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * Set total count.
     *
     * @param int $totalCount
     * @return $this
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * Set items list.
     *
     * @param \Magento\Framework\Api\ExtensibleDataInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null)
    {
        return $this;
    }

    /**
     * Filter Rates by a Method Code
     *
     * @return RateCollection|void
     */
    protected function _beforeLoad()
    {
        $sessionMethodCode = $this->session->getData('mw_current_sMethod_code');
        $requestMethodCode = $this->request->getParam('method_code');
        if ($this->currentMethodCode) {
            $this->addFieldToFilter('method_code', $this->currentMethodCode);
        } elseif ($requestMethodCode) {
            $this->currentMethodCode = $requestMethodCode;
            $this->addFieldToFilter('method_code', $this->currentMethodCode);
        } elseif ($sessionMethodCode) {
            $this->currentMethodCode = $sessionMethodCode;
            $this->addFieldToFilter('method_code', $this->currentMethodCode);
        } else {
            $this->currentMethodCode = null;
        }
        parent::_beforeLoad();
    }

    /**
     * Add carrier to the collection
     */
    protected function _renderFiltersBefore()
    {
        $joinTable = $this->getTable(\MageWorx\ShippingRules\Model\Carrier::METHOD_TABLE_NAME);
        $columns   = ['carrier_code' => 'carrier_code'];
        $this->getSelect()->joinLeft(
            ['cmt' => $joinTable],
            'main_table.method_code = cmt.code',
            $columns
        );

        $this->addCountryIdColumn();
        $this->addRegionColumn();
        $this->addRegionIdColumn();
        $this->addZipsColumns();

        parent::_renderFiltersBefore();
    }
}
