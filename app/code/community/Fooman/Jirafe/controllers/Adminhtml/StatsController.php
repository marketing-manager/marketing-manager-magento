<?php

class Fooman_Jirafe_Adminhtml_StatsController extends Mage_Adminhtml_Controller_Action {

    protected function _construct() {
        $this->setUsedModuleName('Fooman_Jirafe');
    }

    public function indexAction() 
    {

        if(!Mage::helper('foomanjirafe')->isConfigured()) {
            Mage::getSingleton('adminhtml/session')
                ->addError(
                        Mage::helper('foomanjirafe')->__('Jirafe is not yet configured - please go to System > Configuration > <a href="%s">Jirafe</a> to enter your details.',
                                Mage::helper('adminhtml')->getUrl('adminhtml/system_config/edit/section/foomanjirafe')
                                )
                        );
        }

        $this->loadLayout();
        $this->_setActiveMenu('jirafestats');

        $this->_addContent(
                $this->getLayout()->createBlock('foomanjirafe/adminhtml_stats', 'jirafestats')
        );

        $this->renderLayout();
    }
}