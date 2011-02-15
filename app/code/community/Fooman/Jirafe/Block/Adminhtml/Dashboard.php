<?php
class Fooman_Jirafe_Block_Adminhtml_Dashboard extends Mage_Adminhtml_Block_Dashboard
{

    public function __construct()
    {
        parent::__construct();
        if (Mage::helper('foomanjirafe')->getStoreConfig('display_dashboard')) {
            $this->setTemplate('fooman/jirafe/dashboard.phtml');
        }

    }
}