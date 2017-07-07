<?php
class SM_XB2b_Model_Setting extends Mage_Core_Model_Abstract {

    public function _construct(){
        parent::_construct();
        $this->_init('xb2b/setting');
    }

    public function getType(){
        return $this->getData('type');
    }
}