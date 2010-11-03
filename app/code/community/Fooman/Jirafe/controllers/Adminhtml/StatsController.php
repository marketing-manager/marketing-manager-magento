<?php

class Fooman_Jirafe_Adminhtml_StatsController extends Mage_Adminhtml_Controller_Action {

    protected function _construct() {
        $this->setUsedModuleName('Fooman_Jirafe');
    }

    public function indexAction() 
    {

        $this->loadLayout();
        $this->_setActiveMenu('jirafestats');

        $this->_addContent(
                $this->getLayout()->createBlock('foomanjirafe/adminhtml_stats', 'jirafestats')
        );

        $this->renderLayout();
    }
}