<?php
/**
 * User: Hieunt
 * Date: 3/8/13
 * Time: 3:13 PM
 */

class SM_Xb2b_Model_System_Config_Searchby {
    public function toOptionArray() {
        $result = null;
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
            ->addVisibleFilter();
        if ($attributes != null && $attributes->count() > 0):
            $result[] = array('value' => 'entity_id' ,'label' => 'ID');
            foreach ($attributes as $item):
                 $result[] = array('value' => $item->getAttributeCode(), 'label' => Mage::helper('xb2b')->__($item->getFrontendLabel()));
            endforeach;
        endif;
        return $result;
    }
}