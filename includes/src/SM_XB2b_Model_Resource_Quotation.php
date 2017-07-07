<?php
class SM_XB2b_Model_Resource_Quotation extends Mage_Core_Model_Mysql4_Abstract{
    public function _construct(){
        $this->_init('xb2b/quotation','quotation_id');
    }
}