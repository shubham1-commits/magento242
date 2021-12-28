<?php

namespace Searchanise\SearchAutocomplete\Model\Indexer;

class Queue implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    const INDEXER_ID = 'searchanise_queue';

    /**
     * @var  \Searchanise\SearchAutocomplete\Helper\ApiSe
     */
    private $apiSe;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Symfony\Component\Console\Output\ConsoleOutput
     */
    private $output;

    public function __construct(
        \Searchanise\SearchAutocomplete\Helper\ApiSe $apiSe,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Symfony\Component\Console\Output\ConsoleOutput $output
    ) {
        $this->apiSe = $apiSe;
        $this->messageManager = $messageManager;
        $this->output = $output;
    }

    public function execute($ids)
    {
		return $this;
    }
    
    public function executeFull()
    {
        if (!$this->apiSe->getIsIndexEnabled()) {
            // Indexing was not enabled, skipped
            return;
        }

        if (!$this->apiSe->checkParentPrivateKey()) {
            if (php_sapi_name() === 'cli') {
                $this->output->writeln("Searchanise was not registered yet.");
            }

            $this->messageManager->addErrorMessage($errorMessage);

            return;
        }

        $result = $this->apiSe->async();

        if (php_sapi_name() === 'cli') {
            $this->output->writeln("Searchanise queue index status: " . $result);
        }
    }

    public function executeList(array $ids)
    {
        return $this;
    }

    public function executeRow($id)
    {
        return $this;
    }
}
