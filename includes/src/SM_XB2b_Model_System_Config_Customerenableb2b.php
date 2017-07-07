<?php
class SM_Xb2b_Model_System_Config_Customerenableb2b extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = array();
            $this->_options[] = array(
                'value' => 0,
                'label' => 'Disabled'
            );
            $this->_options[] = array(
                'value' => 1,
                'label' => 'Enabled'
            );
        }

        return $this->_options;
    }
}