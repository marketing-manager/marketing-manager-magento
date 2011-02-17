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
class Fooman_Jirafe_Model_Jirafe
{

    /**
     * check if Magento instance has a jirafe application id, create one if none exists
     * update jirafe server if any parameters have changed
     *
     * @return string $appId
     */
    public function checkAppId ()
    {
        $defaultStoreId = Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID;
        //check if we already have a jirafe application id for this Magento instance
        $appId = Mage::helper('foomanjirafe')->getStoreConfig('app_id');
        $currentHash = $this->_createAppSettingsHash($defaultStoreId);
        $changeHash = false;

        if ($appId) {
            //check if settings have changed            
            if ($currentHash != Mage::helper('foomanjirafe')->getStoreConfig('app_settings_hash')) {
                $return = Mage::getModel('foomanjirafe/api_application')->update($appId, Mage::getStoreConfig('web/unsecure/base_url', $defaultStoreId));
                $changeHash = true;
            }
        } else {
            //retrieve new application id from jirafe server
            $appId = Mage::getModel('foomanjirafe/api_application')->create(Mage::getStoreConfig('web/unsecure/base_url', $defaultStoreId), Mage::app()->getStore($storeId)->getName());
            Mage::helper('foomanjirafe')->setStoreConfig('app_id', $appId);
            $changeHash = true;
        }

        //save updated hash
        if ($changeHash) {
            Mage::helper('foomanjirafe')->setStoreConfig('app_settings_hash', $currentHash);
        }
        return $appId;
    }

    protected function _createAppSettingsHash ($storeId)
    {
        return md5(Mage::getStoreConfig('web/unsecure/base_url', $storeId) . Mage::app()->getStore($storeId)->getName());
    }

    /**
     * check if Magento store has a jirafe site id, create one if none exists
     * update jirafe server if any parameters have changed
     *
     * @param string $appId
     * @param Mage_Core_Model_Store $store
     * @return string $siteId
     */
    public function checkSiteId ($appId, $store)
    {
        //check if we already have a jirafe store id for this Magento store
        $siteId = Mage::helper('foomanjirafe')->getStoreConfig('site_id');
        $currentHash = $this->_createStoreSettingsHash($store);

        $changeHash = false;
        if ($siteId) {
            //check if settings have changed            
            if ($currentHash != Mage::helper('foomanjirafe')->getStoreConfig('site_settings_hash')) {
                $return = Mage::getModel('foomanjirafe/api_site')->update(
                                $siteId,
                                $store->getConfig('general/locale/timezone')
                );
                $changeHash = true;
            }
        } else {
            //retrieve new site id from jirafe server
            $siteId = Mage::getModel('foomanjirafe/api_site')->create(
                            $appId,
                            $store->getFrontendName() . ' (' . $store->getName() . ')',
                            $store->getConfig('web/unsecure/base_url'),
                            $store->getConfig('general/locale/timezone'),
                            $store->getConfig('currency/options/base')
            );
            Mage::helper('foomanjirafe')->setStoreConfig('site_id', $siteId);
            $changeHash = true;
        }

        //save updated hash
        if ($changeHash) {
            Mage::helper('foomanjirafe')->setStoreConfig('site_settings_hash', $currentHash);
        }
        return $siteId;
    }

    protected function _createSiteSettingsHash ($store)
    {
        return md5( $store->getFrontendName() .
                    $store->getName() .
                    $store->getConfig('web/unsecure/base_url') .
                    $store->getConfig('general/locale/timezone') .
                    $store->getConfig('currency/options/base')
                );
    }

}
