<?php
class Fooman_Jirafe_Model_Transmit extends Mage_Core_Model_Abstract
{
    protected $_helper = '';


    protected function _construct ()
    {
        $this->_init('foomanjirafe/transmit');
        $this->_helper = Mage::helper('foomanjirafe');
    }

    public function cron ()
    {
        $this->_helper->debug('starting jirafe transmit cron');
        //check if any reports need to be sent
        $this->_helper->debug('finished jirafe transmit cron');
    }

}