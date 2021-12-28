<?php

namespace Searchanise\SearchAutocomplete\Controller\Adminhtml\Searchanise;

use \Magento\Backend\App\Action;
use \Magento\Backend\App\Action\Context;

class Options extends Action
{
    const PARAM_USE_FULL_FEED  = 'snize_use_full_feed';

    /**
     * @var \Searchanise\SearchAutocomplete\Model\Configuration
     */
    private $configuration;

    public function __construct(Context $context, \Searchanise\SearchAutocomplete\Model\Configuration $configuration)
    {
        $this->configuration = $configuration;

        parent::__construct($context);
    }

    public function execute()
    {
        $useFullFeed = $this->getRequest()->getParam(self::PARAM_USE_FULL_FEED);

        if ($useFullFeed != '') {
            $this->configuration->setUseFullFeed($useFullFeed == 'true');
        }
    }
}
