<?php

/**
 * Used in creating options for Custom Report Type config value selection
 *
 */
class Fooman_Jirafe_System_Config_Source_Reporttype
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'simple', 'label'=>Mage::helper('adminhtml')->__('Simple')),
            array('value' => 'detail', 'label'=>Mage::helper('adminhtml')->__('Detailed')),
        );
    }

}
