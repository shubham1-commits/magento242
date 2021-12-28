<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Model\Plugin\SetActualAddress;

use Magento\Quote\Api\CartTotalRepositoryInterface;
use MageWorx\ShippingRules\Model\QuoteAddressActualisation;

/**
 * Class TotalsRequest
 */
class TotalsRequest
{
    /**
     * @var QuoteAddressActualisation
     */
    private $quoteAddressActualisation;

    /**
     * TotalsRequest constructor.
     *
     * @param QuoteAddressActualisation $quoteAddressActualisation
     */
    public function __construct(
        QuoteAddressActualisation $quoteAddressActualisation
    ) {
        $this->quoteAddressActualisation = $quoteAddressActualisation;
    }

    /**
     * @param CartTotalRepositoryInterface $subject
     * @param $cartId
     * @return array
     */
    public function beforeGet(
        CartTotalRepositoryInterface $subject,
        $cartId
    ): array {
        $this->quoteAddressActualisation->processWithCartId($cartId);

        return [$cartId];
    }
}
