<?php

namespace Searchanise\SearchAutocomplete\Model\Config\Source;

class SyncModes implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Option getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $arr = $this->toArray();
        $ret = [];

        foreach ($arr as $key => $value) {
            $ret[] = [
                'value' => $key,
                'label' => $value
            ];
        }

        return $ret;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $choose = [
            \Searchanise\SearchAutocomplete\Model\Configuration::SYNC_MODE_REALTIME => __('When catalog updates'),
            \Searchanise\SearchAutocomplete\Model\Configuration::SYNC_MODE_PERIODIC => __('Periodically via cron'),
            \Searchanise\SearchAutocomplete\Model\Configuration::SYNC_MODE_MANUAL=> __('Manual'),
        ];

        return $choose;
    }
}
