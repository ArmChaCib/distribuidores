<?php

class SM_XB2b_Model_Quotation extends Mage_Core_Model_Abstract{
    public function _construct(){
        parent::_construct();
        $this->_init('xb2b/quotation');
    }

    public function getType(){
        return $this->getData('type');
    }
}