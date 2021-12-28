<?php

namespace Searchanise\SearchAutocomplete\Cron;

class CronJob
{
    /**
     * @var \Searchanise\SearchAutocomplete\Helper\ApiSe
     */
    private $apiSeHelper;

    /**
     * @var \Searchanise\SearchAutocomplete\Model\Configuration
     */
    private $configuration;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\Logger
     */
    private $loggerHelper;

    public function __construct(
        \Searchanise\SearchAutocomplete\Helper\ApiSe $apiSeHelper,
        \Searchanise\SearchAutocomplete\Model\Configuration $configuration,
        \Searchanise\SearchAutocomplete\Helper\Logger $logger
    ) {
        $this->apiSeHelper = $apiSeHelper;
        $this->configuration = $configuration;
        $this->loggerHelper = $logger;
    }

    public function indexer()
    {
        $this->loggerHelper->log(
            __('Cron: Starting indexer'),
            \Searchanise\SearchAutocomplete\Helper\Logger::TYPE_INFO
        );

        if ($this->apiSeHelper->checkCronAsync() && !$this->apiSeHelper->getIsIndexEnabled()) {
            $this->apiSeHelper->async();
        }
    }

    public function reimporter()
    {
        $this->loggerHelper->log(
            __('Cron: Starting reimporter'),
            \Searchanise\SearchAutocomplete\Helper\Logger::TYPE_INFO
        );

        if ($this->configuration->getIsPeriodicSyncMode()) {
            $this->apiSeHelper->queueImport();
        }
    }
}
