<?php
class SM_XB2b_Model_Observer {

    static $customerGroupAfterSave = false;

    public function getCurrentCustomer() {
        return Mage::getSingleton('customer/session')->getCustomer();
    }

    public function insertBlock($observer)
    {
        $app            = Mage::app();
        $request        = $app->getRequest();
        $currContrlr    = $request->getControllerName();
        $currActn       = $request->getActionName();
        $currRouter     = $request->getRouteName();

        $_block         = $observer->getBlock();
        $_type          = $_block->getType();

        if(in_array($_type, array(
                'catalog/product_price',
                'bundle/catalog_product_price',
                'catalog/product_list_toolbar',
                'customer/account_navigation'
            ))) {
            $XB2B = Mage::helper('xb2b')->loadXB2BConfig();
        }

        switch($_type) {
            case 'catalog/product_price':
                if(!$XB2B['root_enable_b2b'] || !$XB2B['bulkorder_enabled'] || !$XB2B['customer_group_enabled_b2b']) {
                    break;
                }
                if(($currRouter == 'catalog' && $currContrlr == 'category' && $currActn == 'view')||
                    ($currRouter == 'catalogsearch' && $currContrlr == 'result' && $currActn == 'index')) {
                    $_child = clone $_block;
                    $_product = $_child->getProduct();
                    $_child->setType('xb2b_catalog/product_price');
                    $_block->setChild('child_'.$_product->getId(), $_child);
                    $_block->setTemplate('sm/xb2b/catalog/product/after_price.phtml');
                }
                break;
            case 'bundle/catalog_product_price':
                if(!$XB2B['root_enable_b2b'] || !$XB2B['bulkorder_enabled'] || !$XB2B['customer_group_enabled_b2b']) {
                    break;
                }
                if(($currRouter == 'catalog' && $currContrlr == 'category' && $currActn == 'view')||
                    ($currRouter == 'catalogsearch' && $currContrlr == 'result' && $currActn == 'index')) {
                    $_child = clone $_block;
                    $_product = $_child->getProduct();
                    $_child->setType('xb2b_catalog/product_price');
                    $_block->setChild('child_'.$_product->getId(), $_child);
                    $_block->setTemplate('sm/xb2b/catalog/product/after_price.phtml');
                }
                break;
            case 'catalog/product_list_toolbar':
                if(!$XB2B['root_enable_b2b'] || !$XB2B['customer_group_enabled_b2b'] || (!$XB2B['bulkorder_enabled'] && !$XB2B['enable_quickorder'])) {
                    break;
                }
                if(($currRouter == 'catalog' && $currContrlr == 'category' && $currActn == 'view')||
                    ($currRouter == 'catalogsearch' && $currContrlr == 'result' && $currActn == 'index')) {
                    $_child = clone $_block;
                    $_child->setType('xb2b_catalog/product_list_toolbar');
                    $_block->setChild('toolbar_child', $_child);
                    $_block->setTemplate('sm/xb2b/catalog/product/list/after_toolbar.phtml');
                }
                break;
            case 'customer/account_navigation':
                if(!$XB2B['root_enable_b2b'] || !$XB2B['customer_group_enabled_b2b'] || !$XB2B['enable_one_click']) {
                    break;
                }
//                if($currRouter == 'customer' && $currContrlr == 'account' && $currActn == 'index') {
                    $_child = clone $_block;
                    $_child->setType('xb2b_customer/account_navigation');
                    $_block->setChild('nav_child', $_child);
                    $_block->setTemplate('sm/xb2b/customer/account/navigation_after.phtml');
//                }
                break;
        }
    }

    public function customerGroupSaveAfter($observer) {
        $b2b_enabled = Mage::app()->getRequest()->getParam('xb2b_enabled');
        if(!self::$customerGroupAfterSave){
            self::$customerGroupAfterSave = true;
            $customer_group = $observer->getObject();
            $customer_group_id = $customer_group->getData('customer_group_id');
            $customer_group->setData('xb2b_enabled',$b2b_enabled);

            $collection_customer = Mage::getModel('customer/customer')
                ->getCollection()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('group_id', $customer_group_id);

            foreach($collection_customer as $item){
                $customer_id = $item->getData('entity_id');
                $customer = Mage::getModel('customer/customer')->load($customer_id);
                $customer->setData('xb2b_feature',$b2b_enabled);
                $customer->save();
            }
            $customer_group->save();
        }

    }

    public function beforeLoadLayout(Varien_Event_Observer $observer) {
        $XB2B       = Mage::helper('xb2b')->loadXB2BConfig();
        $customer   = $this->getCurrentCustomer();
        $customerEnableFeature = $customer->getXb2bFeature();

        if($XB2B['root_enable_b2b']
            && ($XB2B['customer_group_enabled_b2b'] || $customerEnableFeature)) {
            
            # If magento using default design package then add this handler
            $designPackage = Mage::getSingleton('core/design_package')
                                   ->getPackageName();
            
            if ($designPackage == 'default') {
                $update = Mage::getSingleton('core/layout')->getUpdate();
                $update->addHandle('xb2b_default_design_package');
            } else if($designPackage == 'enterprise') {
                $update = Mage::getSingleton('core/layout')->getUpdate();
                $update->addHandle('xb2b_enterprise_design_package');
            }
        }
        
        if($XB2B['root_enable_b2b']
            && ($XB2B['customer_group_enabled_b2b'] || $customerEnableFeature)
            && $XB2B['enable_quickorder']) {
            $update = Mage::getSingleton('core/layout')->getUpdate();
            $update->addHandle('xb2b_enable_quickorder');
        }

        if($XB2B['root_enable_b2b'] 
            && $XB2B['customer_group_enabled_b2b'] 
            && $XB2B['enable_chat']) {
            $update = Mage::getSingleton('core/layout')->getUpdate();
            $update->addHandle('xb2b_enable_customer_chat');
            
        }
    }

    public function adminBeforeLoadLayout(Varien_Event_Observer $observer) {
        $enableB2B  = Mage::getStoreConfig('xb2b/general/enabled');
        $enableChat = Mage::getStoreConfig('xb2b/general/enable_chat');

        if($enableB2B) {
            $update = Mage::getSingleton('core/layout')->getUpdate();
            $update->addHandle('xb2b_enable');
        }

        if($enableB2B && $enableChat) {
            $update = Mage::getSingleton('core/layout')->getUpdate();
            $update->addHandle('xb2b_enable_chat');
        }
    }

    public function checkQuotationExpired($schedule){
        Mage::log('chay roi', null, 'mylogfile.log');
        $model = Mage::getModel('xb2b/quotation');
        $collection = $model->getCollection()
        ->addFieldToFilter('quotation_status',1)
        ->addFieldToFilter('expired_date',array('lt'=>time()));

        foreach($collection as $item){
            $item->setData("quotation_status",0);
            $item->save();
        }

        return $this;
    }
    
    public function whenCartChanged($observer) {
        $hasQuotation = Mage::getSingleton('core/session')->getHasQuotation();
        if($hasQuotation) {
            $currentQuote = Mage::getModel('checkout/cart')->getQuote();
            $currentQuote->setCouponCode('');
            $currentQuote->collectTotals()->save();
        }
    }

}