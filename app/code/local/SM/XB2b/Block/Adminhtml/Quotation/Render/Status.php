<?php
class SM_XB2b_Block_Adminhtml_Quotation_Render_Status extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row){
        $status = $row->getData('quotation_status');
        switch($status){
            case 0: return "Denied";
            case 1: return "Pending";
            case 2: return "Accepted";
            case 3: return "Requested";
            default: return "None";
        }
    }

}

?>