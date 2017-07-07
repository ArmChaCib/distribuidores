<?php

$installer = $this;
$installer->startSetup();

$xb2bHelper = Mage::helper("xb2b");

if (!$xb2bHelper->columnExist($this->getTable('customer/customer_group'), 'xb2b_enabled')) {
    $installer->run("ALTER TABLE {$this->getTable('customer/customer_group')}
                     ADD `xb2b_enabled` int( 2 ) unsigned NULL DEFAULT 0;");
}

$xb2b_quotation_table = $this->getTable('xb2b/quotation');
$installer->run("CREATE TABLE IF NOT EXISTS `$xb2b_quotation_table` (
                      `quotation_id` int(11) NOT NULL AUTO_INCREMENT,
                      `quote_id` int(11) NOT NULL,
                      `quotation_status` tinyint(1) DEFAULT '0',
                      `customer_id` int(11) DEFAULT '0',
                      `expired_date` int(11) DEFAULT NULL,
                      `create_date` int(11) DEFAULT NULL,
                      `update_date` int(11) DEFAULT NULL,
                      `order_id` int(11) DEFAULT NULL,
                      PRIMARY KEY (`quotation_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");


$xb2b_customer_settings_table = $this->getTable('xb2b/setting');
    $installer->run("CREATE TABLE IF NOT EXISTS `$xb2b_customer_settings_table` (
                      `setting_id` int(11) NOT NULL AUTO_INCREMENT,
                      `customer_id` int(11) NOT NULL,
                      `s_key` varchar(255) NOT NULL,
                      `s_value` varchar(255) DEFAULT NULL,
                      PRIMARY KEY (`setting_id`),
                      UNIQUE KEY `idx_uniq` (`customer_id`,`s_key`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

$xb2b_quotecomments_table = $this->getTable('xb2b/quotecomment');
$installer->run("CREATE TABLE IF NOT EXISTS `$xb2b_quotecomments_table` (
                  `quote_comment_id` int(11) NOT NULL AUTO_INCREMENT,
                  `owner_id` int(11) NOT NULL,
                  `owner_type` tinyint(4) NOT NULL,
                  `quote_id` int(11) NOT NULL,
                  `quotation_id` int(11) NOT NULL,
                  `content` text,
                  `date` int(11) NOT NULL,
                  PRIMARY KEY (`quote_comment_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

$installer->endSetup();