<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Block\Zone;

use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use MageWorx\GeoIP\Model\Geoip;
use MageWorx\ShippingRules\Model\ResourceModel\ExtendedZone\CollectionFactory as ExtendedZoneCollectionFactory;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory as CountryCollectionFactory;
use MageWorx\ShippingRules\Helper\Data as Helper;
use MageWorx\ShippingRules\Helper\Image as ImageHelper;

/**
 * Class Selector
 */
class Selector extends Template
{

    /**
     * @var Session|\Magento\Backend\Model\Session\Quote
     */
    protected $session;

    /**
     * @var Geoip
     */
    protected $geoIp;

    /**
     * @var ExtendedZoneCollectionFactory
     */
    protected $ezCollectionFactory;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var CountryCollectionFactory
     */
    protected $countryCollectionFactory;

    /**
     * @var ImageHelper
     */
    protected $imageHelper;

    /**
     * @param Template\Context $context
     * @param Session $checkoutSession
     * @param CustomerSession $customerSession
     * @param Geoip $geoip
     * @param ExtendedZoneCollectionFactory $ezCollectionFactory
     * @param Helper $helper
     * @param ImageHelper $imageHelper
     * @param Json $jsonSerializer
     * @param CountryCollectionFactory $countryCollectionFactory
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Session $checkoutSession,
        CustomerSession $customerSession,
        Geoip $geoip,
        ExtendedZoneCollectionFactory $ezCollectionFactory,
        Helper $helper,
        ImageHelper $imageHelper,
        Json $jsonSerializer,
        CountryCollectionFactory $countryCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->session         = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->geoIp           = $geoip;
        $this->helper          = $helper;
        $this->imageHelper     = $imageHelper;
        $this->jsonSerializer  = $jsonSerializer;

        $this->ezCollectionFactory      = $ezCollectionFactory;
        $this->countryCollectionFactory = $countryCollectionFactory;
    }

    /**
     * JSON Data to create modal component
     *
     * @return string
     */
    public function getDataJson()
    {
        return $this->jsonSerializer->serialize(
            [
                'html'         => $this->getContent(),
                'country'      => $this->getShippingAddress()->getCountryModel()->getName(),
                'country_code' => $this->getShippingAddress()->getCountryId(),
                'country_list' => $this->getStoreCountryListAsOptionArray(),
                'save_url'     => $this->getUrl('mageworx_shippingrules/zone/change')
            ]
        );
    }

    /**
     * @return string
     */
    public function getContent()
    {
        $content = '<div id="shipping-zone-modal-public-content"></div>';

        return $content;
    }

    /**
     * Returns current customers shipping address from the quote
     *
     * @return \Magento\Quote\Model\Quote\Address
     */
    public function getShippingAddress()
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->session->getQuote();

        /** @var \Magento\Quote\Model\Quote\Address $shippingAddress */
        $shippingAddress = $quote->getShippingAddress();
        if (!$shippingAddress->getCountryId()) {
            $storedData = $this->customerSession->getData('customer_location');
            if (!empty($storedData) && is_array($storedData)) {
                $shippingAddress->addData($storedData);
            }
        }

        return $shippingAddress;
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getLabel()
    {
        $currentCountry = $this->getCurrentCountry();
        if (!$currentCountry) {
            $customerData = $this->geoIp->getCurrentLocation();
            if ($customerData->getCode()) {
                $currentCountry = $this->getShippingAddress()
                                       ->getCountryModel()
                                       ->loadByCode($customerData->getCode())
                                       ->getName();
            }
        }

        if (!$currentCountry) {
            $label = __('Please, select you shipping region.');
        } else {
            $label = __('Your Shipping Country: %1', $currentCountry);
        }

        return $label;
    }

    /**
     * Get current country name
     *
     * @return string
     */
    public function getCurrentCountry()
    {
        return $this->getShippingAddress()->getCountryModel()->getName();
    }

    /**
     * Retrieve serialized JS layout configuration ready to use in template
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getJsLayout()
    {
        $additionalData = [
            'components' => [
                'location' => [
                    'data' => [
                        'loc_test'             => 1,
                        'html'                 => $this->getContent(),
                        'country'              => $this->getShippingAddress()->getCountryModel()->getName(),
                        'country_code'         => $this->getShippingAddress()->getCountryId(),
                        'country_list'         => $this->getStoreCountryListAsOptionArray(),
                        'region'               => $this->getShippingAddress()->getRegion(),
                        'region_code'          => $this->getShippingAddress()->getRegionCode(),
                        'region_id'            => $this->getShippingAddress()->getRegionId(),
                        'save_url'             => $this->getUrl('mageworx_shippingrules/zone/change'),
                        'extended_zones'       => $this->getExtendedZones(),
                        'display_address_only' => $this->helper->isOnlyAddressFieldsShouldBeShown()
                    ],
                ],
            ],
        ];

        $this->jsLayout = array_merge_recursive($this->jsLayout, $additionalData);

        return $this->jsonSerializer->serialize($this->jsLayout);
    }

    /**
     * @return array|\MageWorx\ShippingRules\Model\ResourceModel\ExtendedZone[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getExtendedZones()
    {
        $outputItems = [];
        /** @var \MageWorx\ShippingRules\Model\ResourceModel\ExtendedZone\Collection $collection */
        $collection = $this->ezCollectionFactory->create();
        $collection->addIsActiveFilter();
        $collection->addStoreFilter($this->_storeManager->getStore()->getId());
        $collection->setOrder('priority');
        $items = $collection->getItems();
        /** @var \MageWorx\ShippingRules\Model\ExtendedZone $item */
        foreach ($items as $item) {
            $outputItems[] = [
                'id'          => $item->getId(),
                'image'       => $this->imageHelper->getImageUrl($item->getImage(), ImageHelper::IMAGE_TYPE_FRONTEND_PREVIEW),
                'name'        => $item->getLabel($this->_storeManager->getStore()->getId()),
                'description' => $item->getDescription(),
                'countries'   => $item->getCountriesId(),
            ];
        }

        return $outputItems;
    }

    /**
     * Return store country list as an option array
     *
     * @return array
     */
    private function getStoreCountryListAsOptionArray()
    {
        $countryCollection = $this->countryCollectionFactory->create();
        $countryList       = $countryCollection->loadByStore()
                                               ->toOptionArray();

        return $countryList;
    }
}
