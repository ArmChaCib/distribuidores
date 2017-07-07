<?php
class SM_XB2b_Model_Product extends Mage_Catalog_Model_Product {

    public function getStockQuantity() {
        $qty = Mage::getModel('cataloginventory/stock_item')->loadByProduct($this)->getQty();
        return (int)$qty;
    }

    public function searchProduct($by, $value) {
        $collection = Mage::getModel('catalog/product')->getCollection();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter($by, array('like' => "%$value%"));
        $collection->addAttributeToFilter('type_id', 'simple');
//        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
        Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
        $collection->setPageSize(5);

        $return = array();

        foreach ($collection as $_product) {
            $return[] = array(
                'id'    => $_product->getId(),
                'sku'   => $_product->getSku(),
                'name'  => $_product->getName(),
                'image' => $_product->getImageUrl()
            );
        }

        return $return;
    }
}