<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @package     Fooman_Jirafe
 * @copyright   Copyright (c) 2010 Jirafe Inc (http://www.jirafe.com)
 * @copyright   Copyright (c) 2010 Fooman Limited (http://www.fooman.co.nz)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

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