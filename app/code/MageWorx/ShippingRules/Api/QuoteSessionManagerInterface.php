<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Api;

/**
 * Interface QuoteSessionManager
 *
 * Detects actual session (frontend or backend)
 *
 */
interface QuoteSessionManagerInterface
{
    /**
     * @return \Magento\Checkout\Model\Session|\Magento\Backend\Model\Session\Quote
     */
    public function getActualSession();
}
