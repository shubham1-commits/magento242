<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Plugin\XmlSitemap\Model;

use Amasty\XmlSitemap\Model\Sitemap as AmastySiteMap;

class Sitemap
{
    /**
     * @var \Amasty\ShopbyBrand\Model\XmlSitemap
     */
    private $xmlSitemap;

    public function __construct(
        \Amasty\ShopbyBrand\Model\XmlSitemap $xmlSitemap
    ) {
        $this->xmlSitemap = $xmlSitemap;
    }

    /**
     * @param AmastySiteMap $subject
     * @param \Closure $proceed
     * @param $storeId
     *
     * @return array
     */
    public function aroundGetBrandCollection(AmastySiteMap $subject, \Closure $proceed, $storeId)
    {
        return $this->xmlSitemap->getBrandUrls($storeId);
    }
}
