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

class Fooman_Jirafe_Block_Adminhtml_Status extends Mage_Adminhtml_Block_Template
{

    public function __construct ()
    {
        $this->setTemplate('fooman/jirafe/status.phtml');
    }

    public function isConfigured ()
    {
        return Mage::helper('foomanjirafe')->isConfigured();
    }

    public function isOk ()
    {
        return Mage::helper('foomanjirafe')->isOk();
    }

    public function getStatus ()
    {
        return Mage::helper('foomanjirafe')->getStatus();
    }

    public function getStatusMessage ()
    {
        return Mage::helper('foomanjirafe')->getStoreConfig('last_status_message');
    }

    public function getSyncUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/jirafe/sync');
    }

    public function getReportUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/jirafe/report');
    }

    public function isDebug()
    {
        return Mage::helper('foomanjirafe')->isDebug();
    }

    public function isConfig()
    {
        return Mage::app()->getRequest()->getParam('section') == 'foomanjirafe';
    }

}