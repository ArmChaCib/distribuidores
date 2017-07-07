<?php

require_once Mage::getModuleDir('controllers', 'Mage_Checkout').DS.'OnepageController.php';

class SM_XB2b_OnepageController extends Mage_Checkout_OnepageController {

    /**
     * Save checkout billing address
     */
    public function saveBillingAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('billing', array());
            $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);

            if (isset($data['email'])) {
                $data['email'] = trim($data['email']);
            }
            $result = $this->getOnepage()->saveBilling($data, $customerAddressId);

            if (!isset($result['error'])) {
                if ($this->getOnepage()->getQuote()->isVirtual()) {
                    $result['goto_section'] = 'payment';
                    $result['update_section'] = array(
                        'name' => 'payment-method',
                        'html' => $this->_getPaymentMethodsHtml()
                    );
                } elseif (isset($data['use_for_shipping']) && $data['use_for_shipping'] == 1) {
                    $result['goto_section'] = 'shipping_method';
                    $result['update_section'] = array(
                        'name' => 'shipping-method',
                        'html' => $this->_getShippingMethodsHtml()
                    );

                    $result['allow_sections'] = array('shipping');
                    $result['duplicateBillingInfo'] = 'true';
                } else {
                    $result['goto_section'] = 'shipping';
                }
            }

            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

    protected function _getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }

    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    protected function _getQuote()
    {
        return $this->_getCart()->getQuote();
    }

    private function _getCustomer() {
        return Mage::getSingleton('customer/session')->getCustomer();
    }

    private function _getCustomerAddress() {
        return Mage::getModel('customer/address');
    }
    private function _getCustomerDefaultShippingAddress() {
        $customerAddressId  = $this->_getCustomer()->getDefaultShipping();
        if ($customerAddressId){
            $address        = $this->_getCustomerAddress()->load($customerAddressId);
            return $address;
        }
        return false;
    }

    private function _getCustomerDefaultBillingAddress() {
        $customerAddressId  = $this->_getCustomer()->getDefaultBilling();
        if ($customerAddressId){
            $address        = $this->_getCustomerAddress()->load($customerAddressId);
            return $address;
        }
        return false;
    }

    private function _getQuoteBillingAddress() {
        $quote          = $this->_getQuote();
        $billingAddress = $quote->getBillingAddress();
        return $billingAddress;
    }

    private function _getQuoteShippingAddress() {
        $quote          = $this->_getQuote();
        $shippingAddress = $quote->getShippingAddress();
        return $shippingAddress;
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

    private function _getB2BBillingAddress() {
        $settingModel   = Mage::getModel('xb2b/setting');
        $addressModel   = Mage::getModel('customer/address');
        $customerId     = $this->_getCurrentCustomerId();

        $setting = $settingModel->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('s_key', 'default_billing')
            ->getFirstItem();

        $value = $setting->getS_value();
        if(empty($value)) return false;

        $address = $addressModel->load($value);
        if($address->getId()) {
            return $address;
        } else {
            return false;
        }
    }

    private function _getB2BShippingAddress() {
        $settingModel   = Mage::getModel('xb2b/setting');
        $addressModel   = Mage::getModel('customer/address');
        $customerId     = $this->_getCurrentCustomerId();

        $setting = $settingModel->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('s_key', 'default_shipping')
            ->getFirstItem();

        $value = $setting->getS_value();
        if(empty($value)) return false;

        $address = $addressModel->load($value);
        if($address->getId()) {
            return $address;
        } else {
            return false;
        }
    }

    private function _getB2BPaymentMethod() {
        $method = Mage::getStoreConfig('xb2b/general/payment_method');
        return $method;
    }

    private function _stopProcess($data) {
        echo $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($data));
        exit();
    }

    public function placeOrderAction() {
        $paramQuoteId = $this->getRequest()->getParam('quote_id', false);
        $placeOrderResult = array('error' => 0, 'msg' => '');
        if (!$this->_validateFormKey()) {
            $placeOrderResult['error'] = 1;
            $placeOrderResult['msg'] = 'Invalid Form key';
        }

        // Save billing address
        $quoteBillAddr          = $this->_getQuoteBillingAddress();
        $customerBillAddr       = $this->_getB2BBillingAddress();
        if($customerBillAddr == false) $customerBillAddr = $this->_getCustomerDefaultBillingAddress();
        if(!$customerBillAddr) {
            $placeOrderResult['error']  = 1;
            $placeOrderResult['msg']    = 'Please select default addresses for billing and shipping before place order';
            $this->_stopProcess($placeOrderResult);
        }

        $billData = array(
            'billing_address_id'    => $customerBillAddr->getId(),
            'billing'               => array(
                                        'address_id'    => $quoteBillAddr->getId(),
                                        'firstname'     => $customerBillAddr->getFirstname(),
                                        'lastname'      => $customerBillAddr->getLastname(),
                                        'company'       => $customerBillAddr->getCompany(),
                                        'street'        => $customerBillAddr->getStreet(),
                                        'city'          => $customerBillAddr->getCity(),
                                        'region_id'     => $customerBillAddr->getRegion_id(),
                                        'postcode'      => $customerBillAddr->getPostcode(),
                                        'country_id'    => $customerBillAddr->getCountry_id(),
                                        'telephone'     => $customerBillAddr->getTelephone(),
                                        'fax'           => $customerBillAddr->getFax(),
                                        'use_for_shipping' => 1
                                    )
        );
        $customerBillAddressId = $customerBillAddr->getId();

        if (isset($billData['email'])) {
            $billData['email'] = trim($billData['email']);
        }

        $result = $this->getOnepage()->saveBilling($billData, $customerBillAddressId);

        if (!isset($result['error'])) {
            if ($this->getOnepage()->getQuote()->isVirtual()) {
                $result['goto_section'] = 'payment';
                $result['update_section'] = array(
                    'name' => 'payment-method',
                    'html' => $this->_getPaymentMethodsHtml()
                );
            } elseif (isset($data['use_for_shipping']) && $data['use_for_shipping'] == 1) {
                $result['goto_section'] = 'shipping_method';
                $result['update_section'] = array(
                    'name' => 'shipping-method',
                    'html' => $this->_getShippingMethodsHtml()
                );

                $result['allow_sections'] = array('shipping');
                $result['duplicateBillingInfo'] = 'true';
            } else {
                $result['goto_section'] = 'shipping';
            }
        }

        // Save shipping address
        $quoteShipAddr          = $this->_getQuoteShippingAddress();
        $customerShipAddr       = $this->_getB2BShippingAddress();
        if($customerShipAddr == false) $customerShipAddr = $this->_getCustomerDefaultShippingAddress();
        if(!$customerShipAddr) {
            $placeOrderResult['error']  = 1;
            $placeOrderResult['msg']    = 'Please select default addresses for billing and shipping before place order';
            $this->_stopProcess($placeOrderResult);
        }
        $shipData = array(
                'shipping_address_id'   => $customerShipAddr->getId(),
                    'shipping'          => array(
                        'address_id'    => $quoteShipAddr->getId(),
                        'firstname'     => $customerShipAddr->getFirstname(),
                        'lastname'      => $customerShipAddr->getLastname(),
                        'company'       => $customerShipAddr->getCompany(),
                        'street'        => $customerShipAddr->getStreet(),
                        'city'          => $customerShipAddr->getCity(),
                        'region_id'     => $customerShipAddr->getRegion_id(),
                        'postcode'      => $customerShipAddr->getPostcode(),
                        'country_id'    => $customerShipAddr->getCountry_id(),
                        'telephone'     => $customerShipAddr->getTelephone(),
                        'fax'           => $customerShipAddr->getFax(),
                        'use_for_shipping' => 1
                    )
        );

        if (isset($shipData['email'])) {
            $shipData['email'] = trim($shipData['email']);
        }

        $customerShipAddressId = $customerShipAddr->getId();
        $result = $this->getOnepage()->saveShipping($shipData, $customerShipAddressId);
        if (!isset($result['error'])) {
            $result['goto_section'] = 'shipping_method';
            $result['update_section'] = array(
                'name' => 'shipping-method',
                'html' => $this->_getShippingMethodsHtml()
            );
        }

        // Save shipping method
        $result = $this->getOnepage()->saveShippingMethod('freeshipping_freeshipping');
        // $result will contain error data if shipping method is empty
        if (!$result) {
            Mage::dispatchEvent(
                'checkout_controller_onepage_save_shipping_method',
                array(
                    'request' => $this->getRequest(),
                    'quote'   => $this->getOnepage()->getQuote()));
            $this->getOnepage()->getQuote()->collectTotals();

            $result['goto_section'] = 'payment';
            $result['update_section'] = array(
                'name' => 'payment-method',
                'html' => $this->_getPaymentMethodsHtml()
            );
        }
        $this->getOnepage()->getQuote()->collectTotals()->save();

        try {
            // Save payment
            $cfPaymentMethod = $this->_getB2BPaymentMethod();
            if($cfPaymentMethod == false) {
                $cfPaymentMethod = 'cashondelivery';
            }

            $data = array('method' => $cfPaymentMethod);
            $this->getOnepage()->savePayment($data);

            $data = array('method' => $cfPaymentMethod);
            if ($data) {
                $data['checks'] = Mage_Payment_Model_Method_Abstract::CHECK_USE_CHECKOUT
                    | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_COUNTRY
                    | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_CURRENCY
                    | Mage_Payment_Model_Method_Abstract::CHECK_ORDER_TOTAL_MIN_MAX
                    | Mage_Payment_Model_Method_Abstract::CHECK_ZERO_TOTAL;
                $this->getOnepage()->getQuote()->getPayment()->importData($data);
            }

            //Save order
            $this->getOnepage()->saveOrder();
            $redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();

            $this->getOnepage()->getQuote()->save();

            $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();

            $orderComment = $this->getRequest()->getParam('ord_comment');

            $needUpdateOrder = (!empty($orderComment) || $paramQuoteId !== false) ? true : false;

            if($needUpdateOrder) {
                $order = Mage::getModel('sales/order')->load($orderId);
            }

            if(!empty($orderComment)) {
                $order->addStatusToHistory($order->getStatus(), $orderComment, false);
            }

            if($paramQuoteId) {
                $order->setQuoteId($paramQuoteId);
            }

            if($needUpdateOrder) {
                $order->save();
            }

            if($paramQuoteId !== false) {
                $quotations = Mage::getModel('xb2b/quotation')->getCollection()
                    ->addFieldToFilter('quote_id', $paramQuoteId);
                if($quotations->getSize() > 0) {
                    foreach($quotations as $qtn) {
                        $qtn->setOrderId($order->getId())->save();
                    }
                }
            }

            $placeOrderResult['data'] = array(
                'order_code' => $this->getOnepage()->getLastOrderId(),
                'order_id'   => $orderId
            );

            $this->_stopProcess($placeOrderResult);
        } catch (Exception $e) {
            $placeOrderResult['error'] = 1;
            $placeOrderResult['msg'] = $e->getMessage();
            $this->_stopProcess($placeOrderResult);
        }
    }
}