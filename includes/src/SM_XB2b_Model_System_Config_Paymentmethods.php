<?php
class SM_Xb2b_Model_System_Config_Paymentmethods {
    public function toOptionArray() {
        $optons = array(
            array('value' => 'checkmo',         'label' => 'Check / Money order'),
            array('value' => 'banktransfer',    'label' => 'Bank Transfer Payment'),
            array('value' => 'cashondelivery',  'label' => 'Cash On Delivery'),
        );
        return $optons;
    }
}