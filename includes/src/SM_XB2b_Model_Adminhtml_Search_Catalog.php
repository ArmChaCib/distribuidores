<?php

class SM_Xb2b_Model_Adminhtml_Search_Catalog extends Mage_Adminhtml_Model_Search_Catalog
{

    /**
     * Load search results
     * Search product online
     * @return Mage_Adminhtml_Model_Search_Catalog
     */

    public function load()
    {
        $arr = array();

        if (!$this->hasStart() || !$this->hasLimit() || !$this->hasQuery()) {
            $this->setResults($arr);
            return $this;
        }
        $collection = Mage::helper('catalogsearch')->getQuery()->getSearchCollection()
            ->addAttributeToSelect('*')
            ->addTaxPercents()
            ->setCurPage($this->getStart())
            ->setPageSize($this->getLimit());

        $searchBy = Mage::getStoreConfig('xb2b/general/searching_by');
        if ($searchBy != '') {
            $result = array();
            $attributes = explode(",", $searchBy);
            foreach ($attributes as $attribute) {
                $result[] = array('attribute' => $attribute, 'like' => '%' . $this->getQuery() . '%');
            }
        } else {
            $result = array();
            $result[] = array('attribute' => 'entity_id', 'eq' => $this->getQuery());
        }

        if (Mage::getStoreConfig('xb2b/general/searching_status') == 1) {
            $collection->addAttributeToFilter('status', array('eq' => 1));
        }

        //if($product_types = Mage::getStoreConfig('xb2b/general/searching_product_types')){
            $product_types[] = 'simple';
            if(is_array($product_types) && count($product_types) > 0){
                $collection->addFieldToFilter('type_id',array('IN',$product_types));
            }

        //}

        $collection->addAttributeToFilter($result,null,'left');
        $collection->load();
        $_is_loaded = true;

        if ($collection->requireTaxPercent()) {
            $request = Mage::getSingleton('tax/calculation')->getRateRequest();
            foreach ($collection as $item) {
                if (null === $item->getTaxClassId()) {
                    $item->setTaxClassId($item->getMinimalTaxClassId());
                }
                if (!isset($classToRate[$item->getTaxClassId()])) {
                    $request->setProductClassId($item->getTaxClassId());
                    $classToRate[$item->getTaxClassId()] = Mage::getSingleton('tax/calculation')->getRate($request);
                }
                $item->setTaxPercent($classToRate[$item->getTaxClassId()]);
            }
        }
        $storeId = Mage::getStoreConfig('xb2b/general/storeid');
        $allowProduct = array();

        if (isset($storeId)) {
            $allowWebsiteId = Mage::getModel('core/store')->load($storeId)->getWebsite()->getId();
        } else {
            $allowWebsiteId = 0;
        }
        foreach ($collection as $product) {
            if (!in_array($allowWebsiteId, $product->getWebsiteIds())) {
                continue;
            }

            // Get the product's tax class' ID
            $taxClassId = $product->getData("tax_class_id");
            // Get the tax rates of each tax class in an associative array
            $taxClasses = Mage::helper("core")->jsonDecode(Mage::helper("tax")->getAllRatesByProductClass());
            // Extract the tax rate from the array
            if (isset($taxClasses["value_" . $taxClassId])) {
                $taxRate = $taxClasses["value_" . $taxClassId];
            } else {
                $taxRate = null;
            }

            $description = strip_tags($product->getDescription());

            $tmp = array(
                'id' => $product->getId(),
                'type' => $product->getTypeId(),
                'name' => $product->getName(),
                'tax' => $taxRate, //$product->getTaxPercent(),
                'sku' => $product->getSku(),
                'price' => $product->getFinalPrice(),
                'description' => Mage::helper('core/string')->substr($description, 0, 30),
                'producttype' => $product->getTypeId(),
            );

            $flag = true;
            if (Mage::getStoreConfig('xb2b/general/searching_instock') == 1) {
                //Qty <=0 or out of stock -> not return
                $_product = Mage::getModel('catalog/product')->load($product->getId());
                $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product);
//                if ($stock->getQty() <= 0 || $_product->getData('is_in_stock') != 1) {
                if ($_product->getData('is_in_stock') != 1) {
                    $flag = false;
                }
            }

            if ($flag) {
                $arr[] = $tmp;
            }

	        //$arr[] = $tmp;
        }

        $this->setResults($arr);

        return $this;
    }

    /*
     * Load all product to store Local db.
     * @return Mage_Adminhtml_Model_Search_Catalog
     */
    public function loadAll($limit, $page)
    {
        $arr = array();
        $storeId = Mage::getStoreConfig('xb2b/general/storeid');
        $collection = //Mage::helper('catalogsearch')->getQuery()->getSearchCollection()
            Mage::getModel('catalog/product')
                ->getCollection()
                ->addAttributeToSelect('*')
                ->addTaxPercents();

        $collection = $collection->setCurPage($page)
            ->setPageSize($limit)->load();
        // not needed since we checked with JS
        $t = ceil($collection->getSize() / $limit);
        if ($page > $t)
            return null;

        if (Mage::getStoreConfig('xb2b/general/searching_status') == 1) {
            $collection->addAttributeToFilter('status', array('eq' => 1));
        }

        if($product_types = Mage::getStoreConfig('xb2b/general/searching_product_types')){
            $product_types = explode(',',$product_types);
            if(is_array($product_types) && count($product_types) > 0){
                $collection->addFieldToFilter('type_id',array('IN',$product_types));
            }
        }

        if($visibility = Mage::getStoreConfig('xb2b/general/searching_product_visibility')){
            $visibility = explode(',',$visibility);
            if(is_array($visibility) && count($visibility) > 0){
                $collection->addFieldToFilter('visibility',array('IN',$visibility));
            }
        }

        $collection->clear();
        $taxClasses = Mage::helper("core")->jsonDecode(Mage::helper("tax")->getAllRatesByProductClass());
        if (isset($storeId)) {
            $allowWebsiteId = Mage::getModel('core/store')->load($storeId)->getWebsite()->getId();
        } else {
            $allowWebsiteId = 0;
        }

        foreach ($collection as $product) {
            if (!in_array($allowWebsiteId, $product->getWebsiteIds())) {
                $arr[] = array('id' => 'allowProductFlag');
                continue;
            }

            $description = strip_tags($product->getDescription());
            // Get the product's tax class' ID
            $taxClassId = $product->getData("tax_class_id");
            // Get the tax rates of each tax class in an associative array
            // Extract the tax rate from the array
            $taxRate = !empty($taxClasses["value_" . $taxClassId]) ? $taxClasses["value_" . $taxClassId] : 0;

            $tmp = array(
                'id' => $product->getId(),
                'name' => $product->getName(),
                'tax' => $taxRate, //$product->getTaxPercent(),
                'sku' => $product->getSku(),
                'price' => $product->getFinalPrice(),
                'description' => Mage::helper('core/string')->substr($description, 0, 30),
                'producttype' => $product->getTypeId(),
            );

            $searchBy = Mage::getStoreConfig('xb2b/general/searching_by');
            if ($searchBy != '') {
                $attributes = explode(",", $searchBy);
                if (count($attributes) > 0) {
                    foreach ($attributes as $attribute) {
                        $label = $product->getResource()->getAttribute($attribute)->getFrontend()->getValue($product);
                        if ($label)
                            $tmp[$attribute] = $label;
                        else $tmp[$attribute] = '';
                    }
                }
            }

            //Check Config: enable search instock

            if (Mage::getStoreConfig('xb2b/general/searching_instock') == 1) {
                //Qty <=0 or out of stock -> not return
                $_product = Mage::getModel('catalog/product')->load($product->getId());
                $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product);
//                if ($stock->getQty() <= 0 || $_product->getData('is_in_stock') != 1) {
                if ($_product->getData('is_in_stock') != 1) {
                    $arr[] = array('id' => 'allowProductFlag');
                    continue;
                }
            }


            $arr[] = $tmp;
        }

        $this->setResults($arr);

        return $this;
    }

    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session_quote');
    }

}
