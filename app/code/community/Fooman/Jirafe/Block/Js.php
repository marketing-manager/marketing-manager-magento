<?php
class Fooman_Jirafe_Block_Js extends Mage_Core_Block_Template
{

    protected $_isCheckoutSuccess = false;

    /**
     * Set default template
     *
     */
    protected function _construct()
    {
        $this->setTemplate('fooman/jirafe/js.phtml');
    }

    public function setIsCheckoutSuccess($flag)
    {
        $this->_isCheckoutSuccess = $flag;
    }

    public function getIsCheckoutSuccess()
    {
        return $this->_isCheckoutSuccess;
    }
   
    public function getTrackingInfo()
    {
        $js = "";
        $js .= $this->_getPageTrackingInfo()."\n";
        //$js .= $this->_getCartTrackingInfo()."\n";
        $js .= $this->_getPurchaseTrackingInfo()."\n";

        Mage::log($js);
        return $js;
    }

    
    public function getAdditionalTrackingInfo()
    {
        return "";
    }

    public function _getPageTrackingInfo()
    {
        return "piwikTracker.trackPageView();";
    }

    public function _getCartTrackingInfo()
    {
        return "";
    }

    public function _getPurchaseTrackingInfo()
    {
        $js = "";
        if ($this->getIsCheckoutSuccess()) {
            $items = array();
            $quote = $this->_getLastQuote();
            if ($quote) {
                foreach ($quote->getAllItems() as $quoteItem) {
                    $items[] = array('sku'=>$quoteItem->getSku(),'price'=>$quoteItem->getBasePrice());
                }
                $js .= "piwikTracker.trackGoal(".Fooman_Jirafe_Helper_Data::JIRAFE_PURCHASE_GOAL_ID.",'".$quote->getBaseGrandTotal()."', ".json_encode($items).");";
            }
        }
        return $js;
    }


    public function _getLastQuote()
    {
        $quoteId = Mage::getSingleton('checkout/session')->getLastQuoteId();
        if ($quoteId) {
             $quote = Mage::getModel('sales/quote')->load($quoteId);
             if ($quote->getId()) {
                 return $quote;
             }
        }
        return false;
    }
}
