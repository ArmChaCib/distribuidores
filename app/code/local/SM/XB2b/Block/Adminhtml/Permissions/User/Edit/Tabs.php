<?php
class SM_XB2b_Block_Adminhtml_Permissions_User_Edit_Tabs extends Mage_Adminhtml_Block_Permissions_User_Edit_Tabs {

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();
        $xb2bEnabled = Mage::getStoreConfig('xb2b/general/enabled');

        if($xb2bEnabled) {
            $this->addTab('user_xb2b_customer', array(
                'label'     => Mage::helper('adminhtml')->__('Customer assignment'),
                'title'     => Mage::helper('adminhtml')->__('Customer assignment'),
                'content'   => $this->getLayout()->createBlock('xb2b/adminhtml_permissions_customer_grid')->toHtml(),
            ));
            $this->addTab('user_xb2b_customergroup', array(
                'label'     => Mage::helper('adminhtml')->__('Group assignment'),
                'title'     => Mage::helper('adminhtml')->__('Group assignment'),
                'content'   => $this->getLayout()->createBlock('xb2b/adminhtml_permissions_group_grid')->toHtml(),
            ));
        }

        $grandParent = get_parent_class(get_parent_class($this));
        return $grandParent::_beforeToHtml();
    }

}