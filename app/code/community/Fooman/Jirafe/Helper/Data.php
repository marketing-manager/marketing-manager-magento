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

class Fooman_Jirafe_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_FOOMANJIRAFE_SETTINGS = 'foomanjirafe/settings/';
    const JIRAFE_PIWIK_BASE_URL = 'stats.jirafe.com/';
    const JIRAFE_PURCHASE_GOAL_ID = 1;
    const DEBUG = true;

    /**
     * Return store config value for key
     *
     * @param   string $key
     * @return  string
     */
    public function getStoreConfig ($key, $storeId = Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)
    {
        $path = self::XML_PATH_FOOMANJIRAFE_SETTINGS . $key;
        return Mage::getStoreConfig($path, $storeId);
    }

    /**
     * Save store config value for key
     *
     * @param string $key
     * @param string $value
     * @return <type> 
     */
    public function setStoreConfig ($key, $value)
    {
        $path = self::XML_PATH_FOOMANJIRAFE_SETTINGS . $key;

        //save to db
        try {
            $configModel = Mage::getModel('core/config_data');
            if ($configModel->load($path,'path')->getValue() == null){
                $configModel
                    ->setPath($path)
                    ->setValue($value)
                    ->save();
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        //we also set it as a temporary item so we don't need to reload the config
        return Mage::app()->getStore()->setConfig($path, $value);          
    }

    public function isConfigured ($storeId = Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)
    {
        return ($this->getStoreConfig('isActive', $storeId));
    }

    public function debug($mesg)
    {
        if (self::DEBUG) {
            Mage::log($mesg,null,'jirafe.log');
        }
    }
}