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

class Fooman_Jirafe_Block_Adminhtml_Dashboard_Js extends Mage_Adminhtml_Block_Template
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct();

        if (Mage::helper('foomanjirafe/data')->isDashboardActive()) {
            $this->setTemplate('fooman/jirafe/dashboard-head.phtml');
        }
    }

    /**
     * Returns the URL of the asset
     *
     * @param  string $filename The filename of the asset
     *
     * @return string
     */
    public function getAssetUrl($filename)
    {
        return Mage::getModel('foomanjirafe/api')->getAssetUrl($filename);
    }
}
