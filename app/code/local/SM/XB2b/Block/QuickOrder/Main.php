<?php
class SM_XB2b_Block_QuickOrder_Main extends Mage_Core_Block_Template {

    public function getQuote() {
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        return $quote;
    }

    public function getFormKey() {
        return Mage::getSingleton('core/session')->getFormKey();
    }

    public function getUrlEncode() {
        return $this->helper('core/url')->getEncodedUrl();
    }

    private function _getCustomer() {
        return Mage::getSingleton('customer/session')->getCustomer();
    }

    private function _getCustomerAddress() {
        return Mage::getModel('customer/address');
    }

    private function _getCustomerDefaultBillingAddress() {
        $customerAddressId  = $this->_getCustomer()->getDefaultBilling();
        if ($customerAddressId){
            $address        = $this->_getCustomerAddress()->load($customerAddressId);
            return $address;
        }
        return false;
    }

    public function getQuoteBillingAddress() {
        $quote          = $this->getQuote();
        $billingAddress = $quote->getBillingAddress();
        return $billingAddress;
    }

    public function getDefaultShippingAddress($output = 'default') {
        $defaultBilling = $this->_getCustomerDefaultBillingAddress();
        if(!$defaultBilling) return false;
        if($output == 'json'){
            return Mage::helper('core')->jsonEncode($defaultBilling->getData());
        } else {
            return $defaultBilling;
        }
    }
}