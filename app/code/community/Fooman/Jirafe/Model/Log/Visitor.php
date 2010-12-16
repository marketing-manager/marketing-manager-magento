<?php

class Fooman_Jirafe_Model_Log_Visitor extends Mage_Log_Model_Visitor
{
    protected function _construct()
    {
        parent::_construct();
        $userAgent = Mage::helper('core/http')->getHttpUserAgent();
        if (!$this->_skipRequestLogging) {
            if (empty ($userAgent)){
                $this->_skipRequestLogging = true;
            }
            if(preg_match("/bot|spider|crawler|wordpress|tracker|monitor/i", $userAgent)) {
                $this->_skipRequestLogging = true;
            }
        }

        //ignore user agents was introduced in 1.4.0.0
        if(!$this->_skipRequestLogging && version_compare(Mage::getVersion(), '1.4.0.0')  < 0) {
            $ignoreAgents = Mage::getConfig()->getNode('global/ignore_user_agents');
            if ($ignoreAgents) {
                $ignoreAgents = $ignoreAgents->asArray();
                if (in_array($userAgent, $ignoreAgents)) {
                    $this->_skipRequestLogging = true;
                }
            }
        }
    }
}