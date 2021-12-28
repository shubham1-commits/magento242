<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Ui\Component\Listing\Columns;

use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class DateOnly
 */
class DateOnly extends Column
{
    /**
     * @var DateTimeFactory
     */
    protected $dateTimeFactory;

    /**
     * DateOnly constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param DateTimeFactory $dateTimeFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        DateTimeFactory $dateTimeFactory,
        array $components = [],
        array $data = []
    ) {
        $this->dateTimeFactory = $dateTimeFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$this->getData('name')])
                    && $item[$this->getData('name')] !== "0000-00-00 00:00:00"
                ) {
                    $date = $this->dateTimeFactory->create()->date(
                        'Y-m-d',
                        $item[$this->getData('name')]
                    );
                    $item[$this->getData('name')] = $date;
                }
            }
        }

        return $dataSource;
    }
}
