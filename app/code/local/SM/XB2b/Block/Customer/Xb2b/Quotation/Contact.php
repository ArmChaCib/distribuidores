<?php
class SM_XB2b_Block_Customer_Xb2b_Quotation_Contact extends Mage_Core_Block_Template {

    private $itemPerPage = 10;

    public function getFormKey() {
        return Mage::getSingleton('core/session')->getFormKey();
    }

}