<?php
class SM_XB2b_Block_Customer_Chat extends Mage_Core_Block_Template {

    public function getFormKey() {
        return Mage::getSingleton('core/session')->getFormKey();
    }

    public function getCurrentCustomer() {
        return Mage::getSingleton('customer/session')->getCustomer();
    }

    public function getCustomerName() {
        $customer = $this->getCurrentCustomer();
        return $customer->getFirstname() . ' ' . $customer->getLastname();
    }

    public function getSupporters() {
        $customer = $this->getCurrentCustomer();
        $supporterGroup = Mage::getModel('xb2b/groupassignment')
                            ->getCollection()
                            ->addFilter('group_id', $customer->getGroupId());

        $supporterSpecial = Mage::getModel('xb2b/assignment')
                            ->getCollection()
                            ->addFilter('customer_id', $customer->getId());

        $supporters = array();

        foreach($supporterGroup as $sg) {
            if(!isset($supporters[$sg->getUserId()])) {
                $supporters[$sg->getUserId()] = Mage::getModel('admin/user')->load($sg->getUserId());
            }
        }

        foreach($supporterSpecial as $ss) {
            if(!isset($supporters[$ss->getUserId()])) {
                $supporters[$ss->getUserId()] = Mage::getModel('admin/user')->load($ss->getUserId());
            }
        }

        return $supporters;
    }
}