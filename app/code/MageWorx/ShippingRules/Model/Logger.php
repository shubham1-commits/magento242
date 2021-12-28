<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\DataObject;

/**
 * Class Logger
 */
class Logger extends DataObject
{

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var array
     */
    protected $story = [];

    /**
     * @var DataObject[]
     */
    protected $info;

    /**
     * @var int
     */
    protected $currentId;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * @param CheckoutSession $checkoutSession
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param array $data
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        $data = []
    ) {
        parent::__construct($data);
        $this->checkoutSession = $checkoutSession;
        $this->jsonSerializer  = $jsonSerializer;
        if ($this->checkoutSession->getData('story')) {
            try {
                $this->story = $this->jsonSerializer->unserialize($this->checkoutSession->getData('story'));
            } catch (\InvalidArgumentException $argumentException) {
                $this->story = [];
            }
        }
    }

    /**
     * Get story for the current quote
     *
     * @return array
     */
    public function getStory()
    {
        return $this->story;
    }

    /**
     * Get last info from story
     *
     * @return array
     */
    public function getInfo()
    {
        return isset($this->story[count($this->story) - 1]) ? $this->story[count($this->story) - 1] : null;
    }

    /**
     * @return DataObject
     */
    public function createNewInfo($methodCode)
    {
        if (!$this->currentId) {
            if (!count($this->story)) {
                $this->currentId = 0;
            } else {
                $this->currentId = count($this->story);
            }
        }


        if (empty($this->story[$this->currentId][$methodCode])) {
            $this->story[$this->currentId][$methodCode] = [];
        }

        return $this->story[$this->currentId][$methodCode];
    }

    /**
     * Save info in the session
     *
     * @param $methodCode
     * @param $info
     */
    public function saveInfo($methodCode, $info)
    {
        $this->story[$this->currentId][$methodCode] = $info;
        $this->checkoutSession->setData('story', $this->jsonSerializer->serialize($this->story));
    }
}
