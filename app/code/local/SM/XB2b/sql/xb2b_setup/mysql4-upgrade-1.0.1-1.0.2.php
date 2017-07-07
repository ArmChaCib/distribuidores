<?php

$installer = $this;
$installer->startSetup();
$setup = Mage::getModel('customer/entity_setup', 'core_setup');
$setup->addAttribute(
    'customer', 'xb2b_feature',
    array(
        'type' => 'int',
        'input' => 'select',
        'label' => 'XB2B features',
        'global' => 1,
        'visible' => 1,
        'required' => 0,
        'user_defined' => 1,
        'default' => '0',
        'visible_on_front' => 1,
        'source'=> 'xb2b/system_config_customerenableb2b',
    )
);

if (version_compare(Mage::getVersion(), '1.6.0', '<='))
{
    $customer = Mage::getModel('customer/customer');
    $attrSetId = $customer->getResource()->getEntityType()->getDefaultAttributeSetId();
    $setup->addAttributeToSet('customer', $attrSetId, 'XB2B', 'xb2b_feature');
}
if (version_compare(Mage::getVersion(), '1.4.2', '>='))
{
    Mage::getSingleton('eav/config')
        ->getAttribute('customer', 'xb2b_feature')
        ->setData('used_in_forms', array('adminhtml_customer','customer_account_create',
                                         'customer_account_edit'))
        ->save();
}

// Create table to store the assignment settings
$xb2b_assignment_table = $this->getTable('xb2b/assignment');
$installer->run("CREATE TABLE IF NOT EXISTS `$xb2b_assignment_table` (
                  `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
                  `user_id` int(11) NOT NULL,
                  `customer_id` int(11) NOT NULL,
                  `action_owner` int(11) NOT NULL,
                  `assign_date` varchar(30) NOT NULL,
                  PRIMARY KEY (`assignment_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

$xb2b_groupassignment_table = $this->getTable('xb2b/groupassignment');
$installer->run("CREATE TABLE IF NOT EXISTS `$xb2b_groupassignment_table` (
                  `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
                  `user_id` int(11) NOT NULL,
                  `group_id` int(11) NOT NULL,
                  `action_owner` int(11) NOT NULL,
                  `assign_date` varchar(30) NOT NULL,
                  PRIMARY KEY (`assignment_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

$installer->endSetup();