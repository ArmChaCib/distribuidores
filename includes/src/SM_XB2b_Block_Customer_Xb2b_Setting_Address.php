<?php
class SM_XB2b_Block_Customer_Xb2b_Setting_Address extends Mage_Core_Block_Template {

    public function getAllAddresses() {
        $customerSession        = Mage::getSingleton("customer/session");
        $customerIsLogged       = $customerSession->isLoggedIn();
        $customerAddresses      = array();
        if($customerIsLogged) {
            $currentCustomer    = $customerSession->getCustomer();
            $customerAddresses  = $currentCustomer->getAddresses();
        }
        return $customerAddresses;
    }

    public function getFormKey() {
        return Mage::getSingleton('core/session')->getFormKey();
    }

    public function getCustomerB2BSetting() {
        $xb2bSettingModel = Mage::getModel('xb2b/setting');
        $settings = $xb2bSettingModel->getCollection();

        $customerSession        = Mage::getSingleton("customer/session");
        $customerIsLogged       = $customerSession->isLoggedIn();
        if($customerIsLogged) {
            $currentCustomer        = $customerSession->getCustomer();
            $settings   = $settings->addFieldToFilter('customer_id',$currentCustomer->getId());
            $tmp        = array();
            foreach ($settings->getData() as $st) {
                $tmp[$st['s_key']] = $st['s_value'];
            }

            return $tmp;
        }
        return array();
    }

}