<?php

class SM_XB2b_Block_Adminhtml_Permissions_User_Edit_Render_Assigned extends  Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract{
    public function render(Varien_Object $row)
    {
        $edit_user_id   = Mage::app()->getRequest()->getParam('user_id');
        $customer_id    = $row->getId();
        $html = '';

        if(isset($edit_user_id)){
            $assignment = Mage::getModel('xb2b/assignment')
                                ->getCollection()
                                ->addFilter('user_id', $edit_user_id)
                                ->addFilter('customer_id', $customer_id);

            if($assignment->getSize() > 0) {
                $html = '<span style="color:#FFF;font-weight:bold;background:#F55804;border-radius:8px;width:70px; text-align: center; display: block; ">yes</span>';
            }
        }
        return $html;
    }
}