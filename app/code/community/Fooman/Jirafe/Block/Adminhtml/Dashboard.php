<?php
class Fooman_Jirafe_Block_Adminhtml_Dashboard extends Mage_Adminhtml_Block_Widget_Container
{
    protected $_addButtonLabel = 'Add New';
    protected $_backButtonLabel = 'Back';
    protected $_blockGroup = 'adminhtml';
    
    
    public function __construct()
    {
        parent::__construct();
        
        $this->_controller = 'Jirafe';
        $this->_headerText = Mage::helper('foomanjirafe')->__('Dashboard');
        //$this->setTemplate('fooman/jirafe/dashboard.phtml');
		$this->setTemplate('widget/grid/container.phtml');
    }
    
    protected function _prepareLayout()
    {
       $this->setChild('store_switcher',
            $this->getLayout()->createBlock('adminhtml/store_switcher')
                ->setUseConfirm(false)
                ->setSwitchUrl($this->getUrl('*/*/*', array('store'=>null)))
                ->setTemplate('foomanjirafe/switcher.phtml')
        );
	   
        return parent::_prepareLayout();
    }
	
    public function getStoreSwitcherHtml()
    {
        return $this->getChildHtml('store_switcher');
    }
	
    public function getHeaderWidth()
    {
        return 'width:50%;';
    }
	
    public function getHeaderHtml()
    {
//        return 'THIS IS MY HEADER HTML';
        $storeHtml = $this->getChildHtml('store_switcher');
        return '<h3 class="' . $this->getHeaderCssClass() . '">' . $this->getHeaderText() . '</h3>' . $storeHtml;
    }
	
	public function getButtonsHtml()
    {
        $html = "THIS IS MY BUTTONS HTML";
        return $html;
    }
	
	public function getGridHtml()
    {
        $html = "THIS IS MY HTML";
        return $html;
    }
	
/*
    public function getHeaderCssClass()
    {
        return 'icon-head ' . parent::getHeaderCssClass();
    }
*/
}

