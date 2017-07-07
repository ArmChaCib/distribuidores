<?php

class SM_XB2b_Block_Adminhtml_Permissions_Customer_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    public function __construct() {
        parent::__construct();
        $this->setId('customerGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection() {
        $collection = Mage::getModel('customer/customer')->getCollection()
            ->addAttributeToSelect('firstname')
            ->addAttributeToSelect('lastname')
            ->addAttributeToSelect('xb2b_feature');

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {

        $this->addColumn('customer_id_z', array(
            'header' => Mage::helper('customer')->__('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'entity_id',
        ));

        $this->addColumn('firstname_z', array(
            'header' => Mage::helper('customer')->__('First Name'),
            'align' => 'left',
            'width' => '150px',
            'index' => 'firstname',
        ));

        $this->addColumn('lastname_z', array(
            'header' => Mage::helper('customer')->__('Last Name'),
            'align' => 'left',
            'width' => '150px',
            'index' => 'lastname',
        ));

        $this->addColumn('email_z', array(
            'header' => Mage::helper('customer')->__('Email'),
            'align' => 'left',
            'index' => 'email'
        ));

        $this->addColumn('xb2b_enabled', array(
            'header' => Mage::helper('customer')->__('XB2B enabled'),
            'align' => 'right',
            'width' => '10%',
            'index' => 'xb2b_enabled',
            'renderer'=>'xb2b/adminhtml_permissions_user_edit_render_b2benabled',
        ));

        $this->addColumn('assigned', array(
            'header' => Mage::helper('customer')->__('Assigned'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'check',
            'filter'    => false,
            'sortable'  => false,
            'renderer'=>'xb2b/adminhtml_permissions_user_edit_render_assigned',
        ));

        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/xassign/grid', array('_current'=>true));
    }

    public function getRowUrl($row) {
//        return Mage::helper("adminhtml")->getUrl("*/customer/edit/id/".$row->getId());
    }

    protected function _prepareMassaction() {

        $this->setMassactionIdField('customer_id');
        $this->getMassactionBlock()->setFormFieldName('customer');

        $userId = $this->getRequest()->getParam('user_id');

//        $modeOptions = Mage::getModel('index/process')->getModesOptions();

        $this->getMassactionBlock()->addItem('enable', array(
            'label'         => Mage::helper('index')->__('Assign'),
            'url'           => $this->getUrl('*/xassign/massAssign/user_id', array('user_id' => $userId)),
            'selected'      => true,
        ));
        $this->getMassactionBlock()->addItem('disable', array(
            'label'    => Mage::helper('index')->__('Unassign'),
            'url'      => $this->getUrl('*/xassign/massUnassign', array('user_id' => $userId)),
        ));

        return $this;
    }

}