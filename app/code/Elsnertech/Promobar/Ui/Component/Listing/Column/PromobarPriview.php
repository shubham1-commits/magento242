<?php
namespace Elsnertech\Promobar\Ui\Component\Listing\Column;

use Magento\Catalog\Helper\Image;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class PromobarPriview extends Column
{
    const ALT_FIELD = 'title';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Image $imageHelper
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $storeManager
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Image $imageHelper,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        array $components = [],
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->imageHelper = $imageHelper;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if(isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            // print_r($fieldName);die;
            // echo "<pre>";
            
            foreach($dataSource['data']['items'] as & $item) {
                $url = '';
                
                    $url = $this->storeManager->getStore()->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                    ).$item['image'];
                
                $content =  '';
                    $content = $item['content'];

                $urltext = '';
                    $urltext = $item['urltext'];

                $urllink = '';
                    $urllink = $item['url_link'];  

                $contentcolor = '';
                    $contentcolor = $item['conent_color'];

                $bgcolor = '';
                    $bgcolor = $item['bg_color'];

                $urltextcolor = '';
                    $urltextcolor = $item['url_text_color'];


                   
                $item[$fieldName . '_src'] = $url;

                $item[$fieldName . '_alt'] = $this->getAlt($item) ?: '';

                $item[$fieldName . '_link'] = $this->urlBuilder->getUrl(
                    'promobar/image/edit',
                    ['image' => $item['image']]
                );

                //$item[$fieldName . '_orig_src'] = $url;

                $promobar ='' ;

                // $promobar = $promobar."<h1>".$content."</h1>"."<img src = ".$url.">";

                // print_r($promobar);die;
                $item[$fieldName . '_orig_src'] = $url;


 $check = "<div id='promo_bg_color' style='background-color:$bgcolor;background:url($url);text-align: center;' width='100%' height='50' >
     <div id='promo_content_color' class='ownclass' style='color:$contentcolor'>
 $content 
            
     </div>
     <a id='promo_link_color' 
               style='color:$urltext' 
               href= '$urllink'>
              $urltext 
            </a>
 </div>";

$item['image'] = $check;
 // print_r($check);die;

                

            }
        }

        return $dataSource;
    }

    /**
     * @param array $row
     *
     * @return null|string
     */
    protected function getAlt($row)
    {
        $altField = $this->getData('config/altField') ?: self::ALT_FIELD;
        return isset($row[$altField]) ? $row[$altField] : null;
    }
}    