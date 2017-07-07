<?php
class SM_XB2b_Model_Resource_Assignment extends Mage_Core_Model_Mysql4_Abstract{
    public function _construct(){
        $this->_init('xb2b/assignment','assignment_id');
    }
}