<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use MageWorx\ShippingRules\Api\QuoteSessionManagerInterface;

class RulesApplier
{
    const SORT_MULTIPLIER = 1000;

    /**
     * @var Session|\Magento\Backend\Model\Session\Quote
     */
    protected $session;

    /**
     * @var Utility
     */
    protected $validatorUtility;

    /**
     * @var Rule\Action\RateFactory
     */
    protected $rateFactory;

    /**
     * @var array
     */
    protected $shippingMethods = [];

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var QuoteAddress
     */
    protected $actualShippingAddress;

    /**
     * @var array
     */
    protected $shippingMethodsFilters = [];

    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @param Rule\Action\RateFactory $rateFactory
     * @param QuoteSessionManagerInterface $quoteSessionManager
     * @param Utility $utility
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        Rule\Action\RateFactory $rateFactory,
        QuoteSessionManagerInterface $quoteSessionManager,
        Utility $utility,
        ManagerInterface $eventManager
    ) {
        $this->rateFactory      = $rateFactory;
        $this->validatorUtility = $utility;
        $this->session          = $quoteSessionManager->getActualSession();
        $this->eventManager     = $eventManager;
    }

    /**
     * Apply rules to current order item
     *
     * @param Rate|Method $rate
     * @param array|ResourceModel\Rule\Collection $rules
     * @param QuoteAddress null $address
     * @return Rate|Method
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function applyRules($rate, array $rules, QuoteAddress $address = null)
    {
        /** @var string $currentRate */
        $currentRate = Rule::getMethodCode($rate);

        if ($address) {
            $this->setActualShippingAddress($address);
            if (!$this->getActualQuote()) {
                $this->setActualQuote($address->getQuote());
            }
        }

        $this->eventManager->dispatch(
            'mwx_start_applying_rules_process',
            [
                'log_type'       => 'startApplyingRulesProcess',
                'current_method' => $currentRate,
            ]
        );

        /** @var \MageWorx\ShippingRules\Model\Rule $rule */
        foreach ($rules as $rule) {
            // Do not apply one rule more then one time to the one rate
            $ruleId           = $rule->getId();
            $rateAppliedRules = is_array($rate->getAppliedRules()) ? $rate->getAppliedRules() : [];
            if (in_array($ruleId, $rateAppliedRules)) {
                continue;
            }

            $this->eventManager->dispatch(
                'mwx_start_applying_rule',
                [
                    'log_type' => 'startApplyingRule',
                    'rule'     => $rule,
                    'rate'     => $rate
                ]
            );

            // Process rules actions
            foreach ($rule->getActionType() as $actionType) {
                switch ($actionType) {
                    case Rule::ACTION_OVERWRITE_COST:
                        if (!in_array($currentRate, $rule->getShippingMethods())) {
                            break;
                        }
                        $this->overwriteCost($rule, $rate);

                        $this->eventManager->dispatch(
                            'mwx_rule_overwrite_rate_cost',
                            [
                                'log_type' => 'logRewrittenCost',
                                'rate'     => $rate
                            ]
                        );

                        break;
                    case Rule::ACTION_DISABLE_SM:
                        if (is_array($rule->getDisabledShippingMethods()) &&
                            in_array($currentRate, $rule->getDisabledShippingMethods())
                        ) {
                            $this->disableShippingMethod($rate, $rule);
                            $this->eventManager->dispatch(
                                'mwx_rule_method_disabled',
                                [
                                    'log_type' => 'logDisabledMethod',
                                    'rate'     => $rate
                                ]
                            );
                        }
                        break;
                    case Rule::ACTION_CHANGE_SM_DATA:
                        $storeId = $this->session->getStoreId();
                        $rule->changeShippingMethodData($rate, $storeId);
                        $this->eventManager->dispatch(
                            'mwx_rule_method_changed',
                            [
                                'log_type' => 'logChangeMethodData',
                                'rate'     => $rate
                            ]
                        );
                        break;
                    case Rule::ACTION_CHOOSE_SHIPPING_WITH_MIN_PRICE:
                        if (is_array($rule->getMinPriceShippingMethods()) &&
                            in_array($currentRate, $rule->getMinPriceShippingMethods())
                        ) {
                            $this->addShippingMethodToFilterByMinPrice($rate, $rule);
                            $this->eventManager->dispatch(
                                'mwx_rule_method_filter_by_min_price',
                                [
                                    'log_type' => 'logFilterMinPrice',
                                    'rate'     => $rate,
                                    'rule'     => $rule
                                ]
                            );
                        }
                        break;
                }
            }

            $this->updateShippingMethodsAvailability();

            // Update applied rules in the shipping method
            $appliedRules = array_merge($rateAppliedRules, [$ruleId]);
            $rate->setAppliedRules($appliedRules);

            $this->eventManager->dispatch(
                'mwx_end_applying_rule',
                [
                    'log_type' => 'endApplyingRule',
                    'rule'     => $rule
                ]
            );
        }

        $this->eventManager->dispatch(
            'mwx_all_rules_are_applied',
            [
                'log_type'       => 'allRulesAreApplied',
                'current_method' => $currentRate,
            ]
        );

        return $rate;
    }

    /**
     * Overwrite shipping method cost & price
     *
     * @param Rule $rule
     * @param Method $rate
     * @return Method
     */
    protected function overwriteCost(Rule $rule, $rate)
    {
        // Check what action is used in rule
        $actionsCommaSeparated = $rule->getSimpleAction();

        if (!$actionsCommaSeparated) {
            return $rate;
        }

        $actions       = explode(',', $actionsCommaSeparated);
        $sortedActions = $this->sortActions($actions, $rule);

        foreach ($sortedActions as $action) {
            // Do not change price for the free shipping method
            $code = Rule::getMethodCode($rate);
            if ($code === Rule::FREE_SHIPPING_CODE) {
                return $rate;
            }

            // Create calculator for actual action & Calculate result
            $calculator = $this->rateFactory->create($action);
            /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $rate */
            $rate = $calculator->calculate($rule, $rate, $this->getActualQuote(), $this->getActualShippingAddress());
            $this->eventManager->dispatch(
                'mwx_log_detailed_action',
                [
                    'log_type'   => 'logDetailedAction',
                    'calculator' => $calculator,
                    'action'     => $action
                ]
            );
        }

        return $rate;
    }

    /**
     * Sort the rule actions
     *
     * @param array $actions
     * @param Rule $rule
     * @return array
     */
    protected function sortActions(array $actions, Rule $rule)
    {
        $amounts       = $rule->getAmount();
        $sortedActions = [];

        foreach ($actions as $action) {
            // Do not sort not existing actions
            if (empty($amounts[$action])) {
                continue;
            }

            // Get original sort order
            $sortOrder = $amounts[$action]['sort'];

            /**
             * Update the sort order to prevent overwriting.
             * It's possible that exists more than one rule with the same sort order)
             */
            $updatedSort = (int)$sortOrder * self::SORT_MULTIPLIER;
            while (isset($sortedActions[$updatedSort])) {
                $updatedSort++;
            }

            // Save the action with the new sort order (numeric array key)
            $sortedActions[$updatedSort] = $action;
        }

        ksort($sortedActions);

        return $sortedActions;
    }

    /**
     * @return Quote|null
     */
    public function getActualQuote()
    {
        return $this->quote;
    }

    /**
     * @param Quote|null $quote
     * @return $this
     */
    public function setActualQuote(Quote $quote = null)
    {
        $this->quote = $quote;

        return $this;
    }

    /**
     * @return AddressInterface|QuoteAddress|null
     */
    public function getActualShippingAddress()
    {
        return $this->actualShippingAddress;
    }

    /**
     * @param AddressInterface $address
     * @return $this
     */
    public function setActualShippingAddress($address = null): RulesApplier
    {
        $this->actualShippingAddress = $address;

        return $this;
    }

    /**
     * Add current shipping method to array of disabled shipping methods
     *
     * @param Method $rate
     * @param Rule $rule
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function disableShippingMethod($rate, Rule $rule)
    {
        $rate->setIsDisabled(true);
        $storeId = $this->session->getStoreId();
        if ($rule->getDisplayErrorMessage()) {
            $errorMessage = $rule->getStoreSpecificErrorMessage($rate, $storeId);
            $rate->setShowError(true);
            $rate->setCustomErrorMessage($errorMessage);
        } else {
            // If method completely disabled in rule with max priority - do not show error message!
            $rate->setShowError(false);
        }

        $code                         = Rule::getMethodCode($rate);
        $this->shippingMethods[$code] = Rule::DISABLED;

        return $this;
    }

    /**
     * Add current shipping method to array of disabled shipping methods
     *
     * @param Method $rate
     * @param Rule $rule
     * @return $this
     */
    public function addShippingMethodToFilterByMinPrice($rate, Rule $rule)
    {
        $this->shippingMethodsFilters[$rule->getSortOrder()][$rule->getId()] = $rule;

        return $this;
    }

    /**
     * @return array
     */
    public function getShippingMethodsFilterRules()
    {
        return $this->shippingMethodsFilters;
    }

    /**
     * Save shipping methods availability in the checkout session
     *
     * @return $this
     */
    protected function updateShippingMethodsAvailability()
    {
        /** @var QuoteAddress $address */
        $address         = $this->session->getQuote()->getShippingAddress();
        $existingMethods = $address->getShippingRulesMethods();

        if (!$existingMethods) {
            $existingMethods = [];
        }

        $allMethods = array_merge($existingMethods, $this->shippingMethods);
        $address->setShippingRulesMethods($allMethods);

        return $this;
    }

    /**
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @param int[] $appliedRuleIds
     * @return $this
     */
    public function setAppliedShippingRuleIds($address, array $appliedRuleIds)
    {
        $address->setAppliedShippingRuleIds(
            $this->validatorUtility->mergeIds($address->getAppliedShippingRuleIds(), $appliedRuleIds)
        );

        return $this;
    }
}
