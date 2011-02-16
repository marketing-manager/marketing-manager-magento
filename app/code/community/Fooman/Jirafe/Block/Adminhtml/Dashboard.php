<?php
class Fooman_Jirafe_Block_Adminhtml_Dashboard extends Mage_Adminhtml_Block_Dashboard
{

    public function __construct()
    {
        parent::__construct();
        if (Mage::helper('foomanjirafe')->getStoreConfig('isDashboardActive')) {
            $this->setTemplate('fooman/jirafe/dashboard.phtml');
        }
    }
	
    public function getHeaderWidth()
    {
        return 'width:50%;';
    }
	
	public function getHeaderText()
	{
		return Mage::helper('foomanjirafe')->__('Dashboard');
	}
	
	public function getHeaderHtml()
	{
        return '<h3 class="' . $this->getHeaderCssClass() . '">' . $this->getHeaderText() . '</h3>';
	}
}