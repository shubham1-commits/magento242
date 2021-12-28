<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Tax\Model\Config as TaxConfig;
use MageWorx\ShippingRules\Serializer\SerializeJson;

/**
 * Class Data
 */
class Data extends AbstractHelper
{
    const XML_PATH_POPUP_ENABLED             = 'mageworx_shippingrules/popup/enabled';
    const XML_PATH_POPUP_ONLY_ADDRESS_FIELDS = 'mageworx_shippingrules/popup/only_address';

    const XML_PATH_VALIDATION_POSTCODE_EXCESSIVE_VALID  =
        'mageworx_shippingrules/validation/postcode_validation_excessive_valid';
    const XML_PATH_ADVANCED_POSTCODE_VALIDATION_ENABLED =
        'mageworx_shippingrules/validation/advanced_postcode_validation_enabled';
    const XML_PATH_EXTENDED_COUNTRY_SELECT_ENABLED      =
        'mageworx_shippingrules/validation/extended_country_select_enabled';
    const XML_PATH_SINGLE_ADDRESS_ZONE_MODE             =
        'mageworx_shippingrules/validation/single_address_zone_mode';

    const XML_PATH_UK_POST_CONDITIONS_ENABLED    = 'mageworx_shippingrules/validation/uk_postcode_conditions';
    const XML_PATH_SHIPPING_METHODS_TITLES       = 'mageworx_shippingrules/shipping_methods/renaming';
    const XML_PATH_MAX_COUNTRIES_COUNT           = 'mageworx_shippingrules/shipping_methods/rates/max_countries';
    const XML_PATH_MAX_REGIONS_COUNT             = 'mageworx_shippingrules/shipping_methods/rates/max_regions';
    const XML_PATH_RATES_INCLUDE_TAX_IN_SUBTOTAL =
        'mageworx_shippingrules/shipping_methods/rates/include_tax_in_subtotal';

    const XML_PATH_DISPLAY_CHEAPEST_RATE_AT_TOP_ENABLED =
        'mageworx_shippingrules/shipping_methods/display_cheapest_rate_top';
    const XML_PATH_SORT_CARRIERS_ENABLED                =
        'mageworx_shippingrules/shipping_methods/sort_carriers';
    const XML_PATH_RESOLVE_PARAMETERS_FROM_API_REQUEST  =
        'mageworx_shippingrules/developer/resolve_parameters_from_api_request';
    const XML_PATH_IMPORT_EXPORT_USE_ID                 =
        'mageworx_shippingrules/import/use_id';

    const XML_PATH_ALLOWED_COUNTRIES    = 'general/country/allow';
    const XML_PATH_LOGGER_ENABLED       = 'mageworx_shippingrules/developer/logger_enabled';
    const XML_PATH_SHIPPING_PER_PRODUCT = 'mageworx_shippingrules/main/shipping_per_product';

    /**
     * @var array
     */
    protected $EuCountriesList = [];

    /**
     * @var array
     */
    protected $defaultEuCountriesList = [
        'BE',
        'BG',
        'CZ',
        'DK',
        'DE',
        'EE',
        'IE',
        'EL',
        'ES',
        'FR',
        'HR',
        'IT',
        'CY',
        'LV',
        'LT',
        'LU',
        'HU',
        'MT',
        'NL',
        'AT',
        'PL',
        'PT',
        'RO',
        'SI',
        'SK',
        'FI',
        'SE',
        'UK'
    ];

    /**
     * Array where parsed uk postcodes stored
     *
     * @var []
     */
    protected $ukPostCodesParsed;

    /**
     * @var []
     */
    protected $methodTitles;

    /**
     * @var SerializeJson
     */
    protected $serializer;

    /**
     * List of allowed countries for this website (current)
     *
     * @var array
     */
    protected $allowedCountries = [];

    /**
     * @var array
     */
    protected $codesByCountry = [];

    /**
     * @var TaxConfig
     */
    private $taxConfig;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \MageWorx\ShippingRules\Serializer\SerializeJson $serializer
     * @param TaxConfig $taxConfig
     */
    public function __construct(
        Context $context,
        SerializeJson $serializer,
        TaxConfig $taxConfig
    ) {
        parent::__construct($context);
        $this->serializer = $serializer;
        $this->taxConfig  = $taxConfig;
    }

    /**
     * Check is popup (frontend) enabled
     *
     * @param null $storeId
     *
     * @return boolean
     */
    public function isEnabledPopup($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_POPUP_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Setting: show only address field without zones selection
     *
     * @param null $storeId
     * @return bool
     */
    public function isOnlyAddressFieldsShouldBeShown($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_POPUP_ONLY_ADDRESS_FIELDS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Is invalid postcode when there is excessive data entered by an user
     *
     * @param null $storeId
     *
     * @return bool
     */
    public function getPostcodeExcessiveValid($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_VALIDATION_POSTCODE_EXCESSIVE_VALID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Is advanced (primarily for the UK) validation enabled
     *
     * @param null $storeId
     *
     * @return bool
     */
    public function isAdvancedPostCodeValidationEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ADVANCED_POSTCODE_VALIDATION_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check is advanced conditions enabled for the UK post code validation  (parts)
     *
     * @param null $storeId
     * @return bool
     */
    public function isUKSpecificPostcodeConditionsEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_UK_POST_CONDITIONS_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Is need to display chipest shipping rate at top
     *
     * @param null $storeId
     *
     * @return bool
     */
    public function displayCheapestRateAtTop($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DISPLAY_CHEAPEST_RATE_AT_TOP_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Is need to sort carriers
     *
     * @param null $storeId
     *
     * @return bool
     */
    public function isNeedToSortCarriers($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SORT_CARRIERS_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Resolve or not the address parameters from the API request during rates collection
     *
     * @param null $storeId
     * @return bool
     * @see \MageWorx\ShippingRules\Model\Rule\Condition\Address::validate()
     * @see \MageWorx\ShippingRules\Model\Rule\Condition\Address::resolveParametersFromApiRequest()
     *
     */
    public function isNeedToResolveParametersFromApiRequest($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_RESOLVE_PARAMETERS_FROM_API_REQUEST,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check is single zone mode enabled for the address validation
     *
     * @param null $storeId
     * @return bool
     */
    public function isSingleAddressZoneMode($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SINGLE_ADDRESS_ZONE_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check is extended country select enabled (used in the address validation
     *
     * @param null $storeId
     * @return bool
     */
    public function isExtendedCountrySelectEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_EXTENDED_COUNTRY_SELECT_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check is ids field should be used to detect entities relation (old -> new)
     *
     * @param null $storeId
     * @return bool
     */
    public function isIdsUsedDuringImport($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_IMPORT_EXPORT_USE_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check is shipping rules logger enabled
     *
     * @param null $storeId
     * @return bool
     */
    public function isLoggerEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_LOGGER_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check is country in the list of allowed on the website
     *
     * @param string $code
     * @param string $scope
     * @param null $scopeCode
     * @return bool
     */
    public function isCountryAllowed(
        $code,
        $scope = ScopeInterface::SCOPE_WEBSITE,
        $scopeCode = null
    ) {
        $allowedCountries = $this->getAllowedCountries($scope, $scopeCode);
        if (in_array($code, $allowedCountries)) {
            return true;
        }

        return false;
    }

    /**
     * Get list of allowed countries for the current website
     *
     * @param string $scope
     * @param null $scopeCode
     * @return array
     */
    public function getAllowedCountries(
        $scope = ScopeInterface::SCOPE_WEBSITE,
        $scopeCode = null
    ) {
        if (!empty($this->allowedCountries)) {
            return $this->allowedCountries;
        }

        $this->allowedCountries = $this->getCountriesFromConfig($scope, $scopeCode);

        return $this->allowedCountries;
    }

    /**
     * Takes countries from Countries Config data
     *
     * @param string $scope
     * @param int $scopeCode
     *
     * @return array
     */
    public function getCountriesFromConfig($scope, $scopeCode)
    {
        return explode(
            ',',
            (string)$this->scopeConfig->getValue(
                self::XML_PATH_ALLOWED_COUNTRIES,
                $scope,
                $scopeCode
            )
        );
    }

    /**
     * Get maximum count of countries displayed in the rates listing
     *
     * @param null $storeId
     * @return int
     */
    public function getMaxCountriesCount($storeId = null)
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_MAX_COUNTRIES_COUNT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get maximum count of regions displayed in the rates listing
     *
     * @param null $storeId
     * @return int
     */
    public function getMaxRegionsCount($storeId = null)
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_MAX_REGIONS_COUNT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Is shipping per product enabled.
     * Shipping per product adds a restriction to the shipping methods using corresponding product attribute.
     *
     * @param null $storeId
     * @return bool
     */
    public function getShippingPerProduct($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHIPPING_PER_PRODUCT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Is tax included in subtotal
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isTaxIncludedInSubtotal(int $storeId = null)
    {
        return $this->taxConfig->priceIncludesTax($storeId);
    }

    /**
     * Get specific method title
     *
     * @param string $code
     * @param null $storeId
     *
     * @return null
     * @throws \Exception
     */
    public function getMethodTitle($code, $storeId = null)
    {
        $methodTitles = $this->getMethodsTitles($storeId);
        if (empty($methodTitles[$code])) {
            return null;
        }

        return $methodTitles[$code];
    }

    /**
     * Get renamed method titles (all)
     *
     * @param null $storeId
     *
     * @param bool $raw
     *
     * @return array|string|null
     * @throws \Exception
     */
    public function getMethodsTitles($storeId = null, $raw = false)
    {
        if (!empty($this->methodTitles) && !$raw) {
            return $this->methodTitles;
        }

        $value = $this->scopeConfig->getValue(
            self::XML_PATH_SHIPPING_METHODS_TITLES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($raw) {
            return $value;
        }

        if (!$value) {
            $this->methodTitles = [];

            return $this->methodTitles;
        }

        $this->methodTitles = $this->unserializeValue($value);

        return $this->methodTitles;
    }

    /**
     * Create a value from a storable representation
     *
     * @param int|float|string $value
     * @return array
     * @throws \Exception
     */
    public function unserializeValue($value)
    {
        if (is_string($value) && !empty($value)) {
            return $this->serializer->unserialize($value);
        } else {
            return [];
        }
    }

    /**
     * Returns array of the EU country codes
     *
     * @return array
     */
    public function getEuCountries()
    {
        if (!empty($this->EuCountriesList)) {
            return $this->EuCountriesList;
        }

        $euCountries = $this->scopeConfig->getValue('general/country/eu_countries');
        if (!$euCountries) {
            $this->EuCountriesList = $this->defaultEuCountriesList;

            return $this->EuCountriesList;
        }

        $this->EuCountriesList = explode(',', $euCountries);

        return $this->EuCountriesList;
    }

    /**
     * Returns array of selected countries for the specified region
     *
     * @param string|int $threeDigitCode
     *
     * @return array
     */
    public function resolveCountriesByDigitCode($threeDigitCode)
    {
        $countries      = $this->scopeConfig->getValue('mageworx_shippingrules/countries/country_' . $threeDigitCode);
        $countriesArray = explode(',', $countries);

        return $countriesArray;
    }

    /**
     * Get all available digit-codes (geo region codes) for the country
     *
     * @param string $countryCode
     * @return array
     */
    public function getDigitCodesForCountry($countryCode)
    {
        if (!empty($this->codesByCountry[$countryCode])) {
            return $this->codesByCountry[$countryCode];
        }

        $codes                   = [];
        $countriesByRegion       = $this->scopeConfig->getValue('mageworx_shippingrules/countries');
        $countriesByRegion['EU'] = $this->scopeConfig->getValue('general/country/eu_countries');
        foreach ($countriesByRegion as $vagueCode => $countryList) {
            $code             = str_ireplace('country_', '', $vagueCode);
            $countryListArray = explode(',', $countryList);
            if (in_array($countryCode, $countryListArray)) {
                $codes[] = $code;
            }
        }

        $this->codesByCountry[$countryCode] = $codes;

        return $this->codesByCountry[$countryCode];
    }

    /**
     * Parse float from the money string
     * Thanks to the author: @mcuadros
     *
     * @link https://stackoverflow.com/questions/5139793/php-unformat-money
     * @link https://github.com/mcuadros/currency-detector
     *
     * @param string $money
     *
     * @return float
     */
    public function getAmount($money)
    {
        $cleanString             = preg_replace('/([^0-9\.,])/i', '', $money);
        $cleanStringWithDotsOnly = preg_replace('/([,])/i', '.', $cleanString);
        $parts                   = explode('.', $cleanStringWithDotsOnly);
        if (count($parts) > 1) {
            $rightPart    = array_pop($parts);
            $leftPart     = !empty($parts) ? implode('', $parts) : '0';
            $resultString = $leftPart . '.' . $rightPart;
        } else {
            $resultString = $cleanStringWithDotsOnly;
        }

        return (float)$resultString;
    }

    /**
     * Generate a storable representation of a value
     *
     * @param int|float|string|array $value
     * @return string
     * @throws \Exception
     */
    public function serializeValue($value)
    {
        if (is_array($value)) {
            $data = [];
            foreach ($value as $methodId => $title) {
                if (!array_key_exists($methodId, $data)) {
                    $data[$methodId] = $title;
                }
            }

            return $this->serializer->serialize($data);
        } else {
            return '';
        }
    }

    /**
     * Parse UK postcode
     * Returns it by parts:
     *  'area'
     *  'district'
     *  'sector'
     *  'outcode'
     *  'incode'
     *  'formatted'
     *
     * @param string $postcode
     * @return array
     */
    public function parseUkPostCode($postcode)
    {
        if (!empty($this->ukPostCodesParsed[$postcode])) {
            return $this->ukPostCodesParsed[$postcode];
        }

        if (!$postcode) {
            return [];
        }

        // Get in-code and out-code
        if (mb_stripos($postcode, ' ') !== false) {
            $twoParts = explode(' ', $postcode);
            $outcode  = !empty($twoParts[0]) ? $twoParts[0] : null;
            $incode   = !empty($twoParts[1]) ? $twoParts[1] : null;
        } else {
            preg_match(
                '/^([A-Za-z]{1,2}([\d]{2}|[\d]{1}[A-Za-z]{1}|[\d]{1}){1})[\s]?([\d]{1}[A-Za-z]{2})?$/',
                $postcode,
                $match
            );
            $outcode = !empty($match[1]) ? $match[1] : '';
            $incode  = !empty($match[3]) ? $match[3] : '';
        }

        // Get other parts
        $chunksOne = $this->explodeStringByAlphaDigits(
            $outcode
        ); // [A-Za-z]{1,2}([\d]{2}|[\d]{1}[A-Za-z]{1}){1}[\s]?[\d]{1}[A-Za-z]{2}
        $chunksTwo = $this->explodeStringByAlphaDigits($incode);
        $chunks    = array_merge($chunksOne, $chunksTwo);

        $area     = !empty($chunks[0]) ? $chunks[0] : null;
        $district = !empty($outcode) && !empty($area) ? str_ireplace($area, '', $outcode) : null;
        $sector   = !empty($incode) ? mb_substr($incode, 0, 1) : null;
        $unit     = !empty($incode) && !empty($sector) ? str_ireplace($sector, '', $incode) : null;

        $this->ukPostCodesParsed[$postcode] = [
            'uk_area'      => $area,
            'uk_district'  => $district,
            'uk_sector'    => $sector,
            'uk_unit'      => $unit,
            'uk_outcode'   => $outcode,
            'uk_incode'    => $incode,
            'uk_full_code' => $postcode
        ];

        foreach ($this->ukPostCodesParsed[$postcode] as &$part) {
            $part = mb_strtoupper($part);
        }

        return $this->ukPostCodesParsed[$postcode];
    }

    /**
     * Explode string by digits and letters part
     *
     * @param string $string
     *
     * @return array
     */
    public function explodeStringByAlphaDigits($string)
    {
        if (preg_match_all('~[a-zA-Z]+|\d+|[^\da-zA-Z]+~', $string, $chunks)) {
            return $chunks[0];
        }

        return [];
    }

    /**
     * When enabled the tax will be added to the subtotal for the rate validation.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isIncludeTaxInSubtotalForRatesValidation(int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_RATES_INCLUDE_TAX_IN_SUBTOTAL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
