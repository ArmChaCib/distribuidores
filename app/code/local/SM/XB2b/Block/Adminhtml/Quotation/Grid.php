<?php


class SM_XB2b_Block_Adminhtml_Quotation_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId('quotationGrid');
        $this->setDefaultSort('quotation_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }
    
    protected function _prepareCollection() {
        //$collection = Mage::getModel('sales/quote')->getCollection();
        $collection = Mage::getModel('xb2b/quotation')->getCollection();
        $collection->getSelect()->join(Mage::getConfig()->getTablePrefix().'sales_flat_quote', 'main_table.quote_id ='.Mage::getConfig()->getTablePrefix().'sales_flat_quote.entity_id',array('store_id','grand_total','customer_email','created_at','grand_total'));
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $this->addColumn('quotation_id', array(
            'header' => Mage::helper('sales')->__('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'quotation_id',
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'    => Mage::helper('sales')->__('Purchased From (Store)'),
                'index'     => 'store_id',
                'type'      => 'store',
                'store_view'=> true,
                'display_deleted' => true,
            ));
        }

        $this->addColumn('customer_email', array(
            'header' => Mage::helper('sales')->__('Customer Email'),
            'index' => 'customer_email',
        ));

        $this->addColumn('created_at', array(
            'header' => Mage::helper('sales')->__('Purchased On'),
            'index' => 'created_at',
            'type' => 'datetime',
            'width' => '100px',
        ));

        $this->addColumn('quotation_status', array(
            'header'    => Mage::helper('sales')->__('Status'),
            'width'     => '100px',
            'index'     => 'quotation_status',
            'type'      => 'options',
            'options'   => array(
                0   => 'Denied',
                1   => 'Pending',
                2   => 'Accepted',
                3   => 'Requested'
            ),
            'renderer'  => 'xb2b/adminhtml_quotation_render_status'
        ));

        $this->addColumn('order_id', array(
            'header' => Mage::helper('sales')->__('Order ID'),
            'width' => '100px',
            'index' => 'order_id',
            'renderer' => 'xb2b/adminhtml_quotation_render_orderid'
        ));

        $this->addColumn('grand_total', array(
            'header' => Mage::helper('sales')->__('G.T. (Purchased)'),
            'index' => 'grand_total',
            'type'  => 'currency',
            'currency_code' => (string) Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE),
        ));

        $this->addColumn('action',
            array(
                'header'    => Mage::helper('catalog')->__('Action'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('catalog')->__('View'),
                        'url'     => array(
                            'base'=>'*/*/edit',
                            'params'=>array('store'=>$this->getRequest()->getParam('store'))
                        ),
                        'field'   => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
            ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row) {
//        return $this->getUrl('*/*/edit', array('id' => $row->getquotation_id()));
    }

    protected function _prepareMassaction() {
        $this->setMassactionIdField('quotation_id');
        $this->getMassactionBlock()->setFormFieldName('quotation');

        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('xb2b')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('xb2b')->__('Are you sure?')
        ));

        return $this;
    }

}