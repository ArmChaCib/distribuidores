<?php

class SM_XB2b_Helper_Data extends Mage_Core_Helper_Abstract {
    public function isEnable() {
        return Mage::getStoreConfig('xb2b/general/enabled');
    }

    public function columnExist($tableName,$columnName) {
        $resource = Mage::getSingleton('core/resource');
        $writeAdapter = $resource->getConnection('core_write');

        Zend_Db_Table::setDefaultAdapter($writeAdapter);
        $table = new Zend_Db_Table($tableName);
        if (!in_array($columnName,$table->info('cols'))) {
            return false;
        } return true;
    }

    public function tableExist($tableName) {
        $exists = (boolean) Mage::getSingleton('core/resource')
                                ->getConnection('core_write')
                                ->showTableStatus(trim($tableName,'`'));
        return $exists;
    }

    public function loadXB2BConfig() {
        $rootEnableB2B          = Mage::getStoreConfig('xb2b/general/enabled');
        $enabledBulkorder       = Mage::getStoreConfig('xb2b/general/enable_bulk_order');
        $enabledOneClick        = Mage::getStoreConfig('xb2b/general/enable_one_click');
        $enabledLuckySearch     = Mage::getStoreConfig('xb2b/general/lucky_search');
        $enabledQuickOrder      = Mage::getStoreConfig('xb2b/general/enable_quickorder');
        $showQtyInStock         = Mage::getStoreConfig('xb2b/general/show_qty_stock');
        $enableBackOrder        = Mage::getStoreConfig('cataloginventory/item_options/backorders');
        $maxSaleQty             = Mage::getStoreConfig('cataloginventory/item_options/max_sale_qty');
        $enableQuotation        = Mage::getStoreConfig('xb2b/general/enable_quotation');
        $enableChat             = Mage::getStoreConfig('xb2b/general/enable_chat');

        $customerGroupEnabledB2B = 0;

        $customerSession        = Mage::getSingleton("customer/session");
        $customerIsLogged       = $customerSession->isLoggedIn();
        if($customerIsLogged) {
            $currentCustomer            = $customerSession->getCustomer();
            $customerGroup              = Mage::getModel('customer/group')
                ->load($currentCustomer->getData('group_id'));

            $customerGroupEnabledB2B    = $customerGroup->getData('xb2b_enabled');

            if(!$customerGroupEnabledB2B){
                $customerData = Mage::getModel('customer/customer')->load($currentCustomer->getId());
                $customer_xb2b_feature = $customerData->getResource()->getAttribute('xb2b_feature')->getFrontend()->getValue($customerData);
                if($customer_xb2b_feature == 'Enabled'){
                    $customerGroupEnabledB2B = 1;
                }
            }

        } else {
            return false;
        }

        return array(
            'root_enable_b2b'           => $rootEnableB2B,
            'bulkorder_enabled'         => $enabledBulkorder,
            'customer_group_enabled_b2b' => $customerGroupEnabledB2B,
            'enable_one_click'          => $enabledOneClick,
            'lucky_search'              => $enabledLuckySearch,
            'enable_quickorder'         => $enabledQuickOrder,
            'show_qty_stock'            => $showQtyInStock,
            'enable_backorder'          => $enableBackOrder,
            'max_sale_qty'              => $maxSaleQty,
            'enable_quotation'          => $enableQuotation,
            'enable_chat'               => $enableChat
        );
    }

    public function getQuickAddUrl() {
        return Mage::getBaseUrl().'xb2b/QuickOrder';
    }
}
