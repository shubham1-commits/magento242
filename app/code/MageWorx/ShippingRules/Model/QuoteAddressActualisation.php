<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\AddressInterface as QuoteAddress;
use Psr\Log\LoggerInterface;

/**
 * Class QuoteAddressActualisation
 */
class QuoteAddressActualisation
{
    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var RulesApplier
     */
    protected $rulesApplier;

    /**
     * Quote repository.
     *
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * ShippingMethodManagement constructor.
     *
     * @param Validator $validator
     * @param RulesApplier $rulesApplier
     * @param CartRepositoryInterface $quoteRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Validator $validator,
        RulesApplier $rulesApplier,
        CartRepositoryInterface $quoteRepository,
        LoggerInterface $logger
    ) {
        $this->validator          = $validator;
        $this->rulesApplier       = $rulesApplier;
        $this->quoteRepository    = $quoteRepository;
        $this->logger             = $logger;
    }

    /**
     * @param CartInterface|Quote $quote
     * @param QuoteAddress|null $shippingAddress
     */
    public function storeData(CartInterface $quote, $shippingAddress = null)
    {
        if ($shippingAddress === null) {
            $shippingAddress = $quote->getShippingAddress();
        } else {
            $quoteShippingAddress = $quote->getShippingAddress();
            $quoteShippingAddress->addData($shippingAddress->getData());

            $shippingAddress = $quoteShippingAddress;
        }

        $this->validator->setQuote($quote);
        $this->validator->setActualShippingAddress($shippingAddress);

        $this->rulesApplier->setActualQuote($quote);
        $this->rulesApplier->setActualShippingAddress($shippingAddress);
    }

    /**
     * Store data using regular cart id
     *
     * @param $cartId
     */
    public function processWithCartId($cartId)
    {
        try {
            $quote = $this->quoteRepository->getActive($cartId);
        } catch (LocalizedException $localizedException) {
            $this->logger->critical($localizedException->getLogMessage());

            return;
        }

        $this->storeData($quote);
    }
}
