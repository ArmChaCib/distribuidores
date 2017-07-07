<?php
class SM_Xb2b_Model_System_Config_Requestspeed {
    public function toOptionArray() {
        $optons = array(
            array('value' => 2000,  'label' => 'High'),
            array('value' => 3000,  'label' => 'Normal'),
            array('value' => 4000,  'label' => 'Low'),
        );
        return $optons;
    }
}