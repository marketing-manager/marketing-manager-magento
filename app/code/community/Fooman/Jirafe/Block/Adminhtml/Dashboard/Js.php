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

class Fooman_Jirafe_Block_Adminhtml_Dashboard_Js extends Mage_Core_Block_Template
{

    public function __construct ()
    {
        parent::__construct();
        if ($this->isJirafeDashboardActive()) {
            $this->setTemplate('fooman/jirafe/dashboard-head.phtml');
        }
    }

    public function isJirafeDashboardActive ()
    {
        return Mage::helper('foomanjirafe')->getStoreConfig('isDashboardActive');
    }

}
