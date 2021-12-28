<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Model;

use MageWorx\ShippingRules\Api\QuoteSessionManagerInterface;

/**
 * Class QuoteSessionManager
 *
 * Detects actual session (frontend or backend)
 */
class QuoteSessionManager implements QuoteSessionManagerInterface
{
    /**
     * @var \Magento\Checkout\Model\Session|\Magento\Backend\Model\Session\Quote
     */
    protected $session;

    /**
     * QuoteSessionManager constructor.
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Backend\Model\Session\Quote $backendQuoteSession
     * @param \Magento\Framework\App\State $appState
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Backend\Model\Session\Quote $backendQuoteSession,
        \Magento\Framework\App\State $appState
    ) {
        if ($appState->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
            $this->session = $backendQuoteSession;
        } else {
            $this->session = $checkoutSession;
        }
    }

    /**
     * @return \Magento\Checkout\Model\Session|\Magento\Backend\Model\Session\Quote
     */
    public function getActualSession()
    {
        return $this->session;
    }
}
