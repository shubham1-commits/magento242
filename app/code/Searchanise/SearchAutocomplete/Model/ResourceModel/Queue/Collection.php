<?php

namespace Searchanise\SearchAutocomplete\Model\ResourceModel\Queue;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var \Searchanise\SearchAutocomplete\Model\QueueFactory
     */
    private $searchaniseQueueFactory;

    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Searchanise\SearchAutocomplete\Model\QueueFactory $searchaniseQueueFactory,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Searchanise\SearchAutocomplete\Model\ResourceModel\Queue $resource = null
    ) {
        $this->searchaniseQueueFactory = $searchaniseQueueFactory;

        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * {@inheritDoc}
     *
     * @see \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection::_construct()
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(
            'Searchanise\SearchAutocomplete\Model\Mysql4\Queue',
            'Searchanise\SearchAutocomplete\Model\ResourceModel\Queue'
        );
    }

    public function delete()
    {
        $delete_collection = $this->toArray();

        if (!empty($delete_collection['items'])) {
            $queue_ids = array_map(
                function ($v) {
                    return $v['queue_id'];
                },
                $delete_collection['items']
            );

            $queueCollection = $this
                ->searchaniseQueueFactory
                ->create()
                ->getCollection()
                ->addFieldToFilter('queue_id', ['in' => $queue_ids])
                ->load();

            $queueCollection->walk('delete');
        }
    }
}
