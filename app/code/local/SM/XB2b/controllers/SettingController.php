<?php
class SM_XB2b_SettingController extends Mage_Core_Controller_Front_Action {

    private $_xb2b = null;

    private function _grandAccess() {
        $this->_xb2b = Mage::helper('xb2b')->loadXB2BConfig();
        if(!$this->_xb2b['root_enable_b2b'] || !$this->_xb2b['customer_group_enabled_b2b']) {
            Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getBaseUrl());
        }
    }

    public function indexAction() {
        $this->_grandAccess();
        $this->loadLayout();
        $this->renderLayout();
    }

    private function _getCurrentCustomerId() {
        $customerSession        = Mage::getSingleton("customer/session");
        $customerIsLogged       = $customerSession->isLoggedIn();
        $customerId             = false;
        if($customerIsLogged) {
            $currentCustomer    = $customerSession->getCustomer();
            $customerId         = $currentCustomer->getId();
        }
        return $customerId;
    }

    public function addressAction() {
        $this->_grandAccess();

        if ($this->_validateFormKey() && $this->getRequest()->isPost()) {
            $params         = $this->getRequest()->getParams();
            $customerId     = $this->_getCurrentCustomerId();

            $shippingAddress = $params['default_shipping_address'];
            $billingAddress = $params['default_billing_address'];
            $settingModel   = Mage::getModel('xb2b/setting');
            $setting = $settingModel->getCollection()
                        ->addFieldToFilter('customer_id', $customerId)
                        ->addFieldToFilter('s_key', 'default_shipping')
                        ->getFirstItem();

            if(!$setting->getId()) {
                $setting->addData(array(
                    'customer_id'   => $customerId,
                    's_key'         => 'default_shipping',
                    's_value'       => $shippingAddress
                ));
                $setting->save();
            } else {
                $setting->addData(array('s_value' => $shippingAddress));
                $setting->save();
            }

            $setting = $settingModel->getCollection()
                        ->addFieldToFilter('customer_id', $customerId)
                        ->addFieldToFilter('s_key', 'default_billing')
                        ->getFirstItem();

            if(!$setting->getId()) {
                $setting->addData(array(
                    'customer_id'   => $customerId,
                    's_key'         => 'default_billing',
                    's_value'       => $billingAddress
                ));
                $setting->save();
            } else {
                $setting->addData(array('s_value' => $billingAddress));
                $setting->save();
            }
        }

        $this->loadLayout();
        $this->renderLayout();
    }

    public function paymentAction() {
        $this->_grandAccess();

        if ($this->_validateFormKey() && $this->getRequest()->isPost()) {
            $params         = $this->getRequest()->getParams();
            $customerId     = $this->_getCurrentCustomerId();
            $default_payment = $params['default_payment_method'];
            $settingModel   = Mage::getModel('xb2b/setting');

            $setting = $settingModel->getCollection()
                                    ->addFieldToFilter('customer_id', $customerId)
                                    ->addFieldToFilter('s_key', 'default_payment_method')
                                    ->getFirstItem();

            if(!$setting->getId()) {
                $setting->addData(array(
                    'customer_id'   => $customerId,
                    's_value'       => $default_payment,
                    's_key'         => 'default_payment_method'
                ));
                $setting->save();
            } else {
                $setting->addData(array('s_value' => $default_payment));
                $setting->save();
            }
        }
        $this->loadLayout();
        $this->renderLayout();
    }

}