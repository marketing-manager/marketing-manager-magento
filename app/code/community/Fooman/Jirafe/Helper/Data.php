<?php

class Fooman_Jirafe_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_FOOMANJIRAFE_SETTINGS = 'foomanjirafe/settings/';

    const JIRAFE_PURCHASE_GOAL_ID = 1;

    /**
     * Return store config value for key
     *
     * @param   string $key
     * @return  string
     */
    public function getStoreConfig ($key)
    {

        $path = self::XML_PATH_FOOMANJIRAFE_SETTINGS . $key;
        return Mage::getStoreConfig($path);
    }

    public function isConfigured ($storeId=Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)
    {
        return ($this->getStoreConfig('tokenAuth') && $this->getStoreConfig('pkBaseURL') && $this->getStoreConfig('pkBaseURLSecure'));
    }

}