<?php

class SM_XB2b_Block_Adminhtml_Permissions_User_Edit_Render_B2benabled extends  Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract{
    public function render(Varien_Object $row)
    {
        $html = '';
        if($row->getXb2bEnabled() == 1 || $row->getXb2bFeature() == 1) {
            $html = '<span style="color:#FFF;font-weight:bold;background:cornflowerblue;border-radius:8px;width:70px; text-align: center; display: block; ">yes</span>';
        }
        return $html;
    }
}