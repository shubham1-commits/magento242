<?php

namespace Amasty\ShopbyBase\Api;

interface UrlBuilderInterface
{
    /**
     * @param null $routePath
     * @param null $routeParams
     * @return string
     */
    public function getUrl($routePath = null, $routeParams = null);

    /**
     * @param bool $modified = true
     * @return string
     */
    public function getCurrentUrl($modified = true);

    /**
     * @param array $params
     * @return string|null
     */
    public function getBaseUrl($params = []);
}
