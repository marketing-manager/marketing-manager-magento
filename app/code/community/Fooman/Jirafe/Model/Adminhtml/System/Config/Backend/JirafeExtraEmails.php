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

class Fooman_Jirafe_Model_Adminhtml_System_Config_Backend_JirafeExtraEmails extends Mage_Core_Model_Config_Data
{

    protected function _beforeSave()
    {
        $this->setValue(str_replace(array("\r", " "), "", str_replace("\n", ",", $this->getValue())));
        return parent::_beforeSave();
    }

    protected function _afterLoad()
    {
        $this->setValue(str_replace(",", "\n",$this->getValue()));
        return parent::_afterLoad();
    }
}