<?php
class SM_XB2b_Block_Adminhtml_Quotation_Render_Orderid extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row){
        $order_id   = $row->getData('order_id');
        $order = Mage::getModel('sales/order')->load($order_id);
        $Incrementid = $order->getIncrementId();
        if($order_id != 0) {
            $url = Mage::helper("adminhtml")->getUrl("*/sales_order/view/order_id/".$order_id);
            $returnStr = '<a href="'.$url.'" target="_blank">#'.$Incrementid.'</a>';
            return $returnStr;
        }
        return '';
    }

}

?>