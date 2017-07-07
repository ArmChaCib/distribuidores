<?php

class SM_XB2b_Block_Adminhtml_Quotation_Quotation extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
      parent::__construct();
        $this->_controller = 'adminhtml_quotation';
        $this->_blockGroup = 'xb2b';
        $this->_headerText = Mage::helper('xb2b')->__('Quotation List');
        $this->_addButtonLabel = Mage::helper('xb2b')->__('Add quotation');
  }
}