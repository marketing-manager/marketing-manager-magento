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
        return "_paq.push(['trackPageView']);";
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
                $js .= "_paq.push(['trackGoal',".Fooman_Jirafe_Helper_Data::JIRAFE_PURCHASE_GOAL_ID.",'".$quote->getBaseGrandTotal()."', ".json_encode($items)."]);";
            }
        }
        return $js;
    }

    /**
     * load the quote belonging to the last successful order
     * 
     * @return Mage_Sales_Model_Quote|bool
     */
    public function _getLastQuote()
    {
        $orderIncrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        if ($orderIncrementId) {
            $order = Mage::getModel('sales/order')->load($orderIncrementId, 'increment_id');
            if ($order->getId()) {
                $quoteId = $order->getQuoteId();
            }
        } else {
            $quoteId = Mage::getSingleton('checkout/session')->getLastQuoteId();
        }
        
        if ($quoteId) {
             $quote = Mage::getModel('sales/quote')->load($quoteId);
             if ($quote->getId()) {
                 return $quote;
             }
        }
        return false;
    }

    public function getSiteId ()
    {
        return Mage::helper('foomanjirafe')->getStoreConfig('site_id', Mage::app()->getStore()->getId());
    }

    public function getPiwikBaseURL ($secure = false)
    {
        //TODO: decide if we want to distribute a local fallback piwik.js
        $protocol =  $secure?"https://":"http://";
        return $protocol. Fooman_Jirafe_Helper_Data::JIRAFE_PIWIK_BASE_URL;
    }

}
