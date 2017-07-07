<?php

class SM_XB2b_Block_Adminhtml_Permissions_Group_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    public function __construct() {
        parent::__construct();
        $this->setId('groupGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection() {
        $collection = Mage::getModel('customer/group')->getCollection();

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {

        $this->addColumn('customer_group_id', array(
            'header' => Mage::helper('customer')->__('ID'),
            'align' => 'right',
            'width' => '10%',
            'index' => 'customer_group_id',
        ));

        $this->addColumn('customer_group_code', array(
            'header' => Mage::helper('customer')->__('Group Name'),
            'align' => 'left',
            'width' => '150px',
            'index' => 'customer_group_code',
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
            'width' => '10%',
            'index' => 'check',
            'filter'    => false,
            'sortable'  => false,
            'renderer'=>'xb2b/adminhtml_permissions_user_edit_render_groupassigned',
        ));

        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/xgroupassign/grid', array('_current'=>true));
    }

    public function getRowUrl($row) {
//        return Mage::helper("adminhtml")->getUrl("*/customer/edit/id/".$row->getId());
    }

    protected function _prepareMassaction() {

        $this->setMassactionIdField('customer_group_id');
        $this->getMassactionBlock()->setFormFieldName('group');

        $userId = $this->getRequest()->getParam('user_id');

//        $modeOptions = Mage::getModel('index/process')->getModesOptions();

        $this->getMassactionBlock()->addItem('enable', array(
            'label'         => Mage::helper('index')->__('Assign'),
            'url'           => $this->getUrl('*/xgroupassign/massAssign/user_id', array('user_id' => $userId)),
            'selected'      => true,
        ));
        $this->getMassactionBlock()->addItem('disable', array(
            'label'    => Mage::helper('index')->__('Unassign'),
            'url'      => $this->getUrl('*/xgroupassign/massUnassign', array('user_id' => $userId)),
        ));

        return $this;
    }

}