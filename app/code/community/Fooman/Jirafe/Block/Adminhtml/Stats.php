<?php

class Fooman_Jirafe_Block_Adminhtml_Stats extends Mage_Adminhtml_Block_Widget_Container
{

    protected function _construct() {
        $this->setTemplate('fooman/jirafe/stats.phtml');
    }

    public function __construct()
    {
        $this->_controller = 'adminhtml_stats';
        $this->_blockGroup = 'foomanjirafe';
        $this->_headerText = Mage::helper('foomanjirafe')->__('Jirafe');

        parent::__construct();
        $this->_removeButton('add');
    }

}