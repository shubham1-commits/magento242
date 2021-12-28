<?php
namespace Elsnertech\Promobar\Model;
 
use Elsnertech\Promobar\Model\ResourceModel\Promobar\CollectionFactory;
 
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{

    protected $loadedData;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $CollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $CollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }
    
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();

        foreach ($items as $blog) {
            $this->loadedData[$blog->getId()] = $blog->getData();
            if (isset($data['image'])) {
                $name = $data['image'];
                unset($data['image']);
                $data['image'][0] = [
                'name' => $name,
                'url' => $mediaUrl.'Elsnertech/feature/'.$name
                ];
            }

        }
        return $this->loadedData;
    }
}
