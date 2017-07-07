<?php
class SM_Xb2b_Model_System_Config_Shippingmethods {
    public function toOptionArray() {
        $optons = array(
            array('value' => 'freeshipping_freeshipping', 'label' => 'Free shipping'),
        );
        return $optons;
    }
}