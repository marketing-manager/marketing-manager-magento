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

class Fooman_Jirafe_Block_Adminhtml_Dashboard_Toggle extends Mage_Adminhtml_Block_Template
{

    public function __construct ()
    {
		if (Mage::helper('foomanjirafe')->getStoreConfig('isActive')) {
	        $this->setTemplate('fooman/jirafe/dashboard-toggle.phtml');
		}
    }

    public function getToggleLinkName()
    {
        if (Mage::helper('foomanjirafe/data')->isDashboardActive()) {
            return Mage::helper('foomanjirafe')->__('Click here to view the default Magento dashboard');
        } else {
            return Mage::helper('foomanjirafe')->__('Click here to view the enhanced Jirafe dashboard');
        }
    }

    public function getToggleUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/jirafe/toggleDashboard');
    }
}