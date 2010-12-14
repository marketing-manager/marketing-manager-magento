<?php

class Fooman_Jirafe_Model_Log_Visitor extends Mage_Log_Model_Visitor
{
    protected function _construct()
    {
        parent::_construct();
        if (!$this->_skipRequestLogging) {
            $userAgent = Mage::helper('core/http')->getHttpUserAgent();
            if (empty ($userAgent)){
                $this->_skipRequestLogging = true;
            }
            if(preg_match("/bot|spider|crawler/", $userAgent)) {
                $this->_skipRequestLogging = true;
            }
        }
    }
}