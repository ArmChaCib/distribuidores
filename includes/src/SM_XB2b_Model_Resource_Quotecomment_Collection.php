<?php

class SM_XB2b_Model_Resource_Quotecomment_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('xb2b/quotecomment');
    }

}