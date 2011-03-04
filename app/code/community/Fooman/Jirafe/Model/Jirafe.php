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
            try {
                $return = Mage::getModel('foomanjirafe/api_application')->create(Mage::app()->getStore($storeId)->getName(), Mage::getStoreConfig('web/unsecure/base_url', $defaultStoreId));
                if(empty($return['app_id']) || empty($return['token'])) {
                    throw new Exception ('Jirafe did not return a valid application Id or token.');
                }
            } catch (Exception $e) {
                Mage::logException($e);
                return false;
            }
            Mage::helper('foomanjirafe')->setStoreConfig('app_id', $return['app_id']);
            Mage::helper('foomanjirafe')->setStoreConfig('app_token', $return['token']);
            $appId = $return['app_id'];
            $changeHash = true;
        }

        //save updated hash
        if ($changeHash) {
            Mage::helper('foomanjirafe')->setStoreConfig('app_settings_hash', $currentHash);
        }
        return $appId;
    }

    /**
     * create a md5 hash of the the default store (admin) settings we store server side so we know when we need to update
     *
     * @param int $storeId
     * @return string
     */
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
        $siteId = Mage::helper('foomanjirafe')->getStoreConfig('site_id', $store->getId());
        $adminToken =  Mage::helper('foomanjirafe')->getStoreConfig('app_token');
        $currentHash = $this->_createSiteSettingsHash($store);

        //check if we haven't yet assigned a site id or if settings have changed
        if (!$siteId || $currentHash != Mage::helper('foomanjirafe')->getStoreConfig('site_settings_hash', $store->getId())) {
            $this->syncUsersAndStores();
            $siteId = Mage::helper('foomanjirafe')->getStoreConfig('site_id', $store->getId());
        }
        if(empty($siteId)){
            Mage::logException(new Exception('Jirafe site_id is empty.'));
            return false;
        }
        return $siteId;
    }

    /**
     * create a md5 hash of the the store settings we store server side for the site so we know when we need to update
     *
     * @param int $storeId
     * @return string
     */
    protected function _createSiteSettingsHash ($store)
    {
        return md5( $store->getFrontendName() .
                    $store->getName() .
                    $store->getConfig('web/unsecure/base_url') .
                    $store->getConfig('general/locale/timezone') .
                    $store->getConfig('currency/options/base')
                );
    }


    public function syncUsersAndStores ()
    {
        $jirafeHelper = Mage::helper('foomanjirafe');

        $appId =  $jirafeHelper->getStoreConfig('app_id');
        $adminToken =  $jirafeHelper->getStoreConfig('app_token');

        $userArray = array();
        $siteArray = array();

        foreach ($jirafeHelper->getStores() as $store) {
            $tmpStoreArray = array();
            $tmpStoreArray['id'] = $jirafeHelper->getStoreConfig('site_id', $store->getId());
            $tmpStoreArray['description'] = $jirafeHelper->getStoreDescription($store);
            $tmpStoreArray['url'] = $store->getConfig('web/unsecure/base_url');
            $tmpStoreArray['timezone'] = $store->getConfig('general/locale/timezone');
            $tmpStoreArray['currency'] = $store->getConfig('currency/options/base');
            $siteArray[] = $tmpStoreArray;
        }

        $adminUsers = Mage::getSingleton('admin/user')->getCollection();
        foreach ($adminUsers as $adminUser) {
            if ($adminUser->getIsActive()) {
                /* o token - Jirafe authentication token for the given user. If provided, the user informations will be updated. If not, the user will be created.
                  o username - Desired username
                  o email - Your email
                  o first_name - Your first name
                  o last_name - Your last name
                  o mobile_phone - Your mobile phone number
                 */
                $tmpUserArray = array();
                $tmpUserArray['token'] = $adminUser->getJirafeUserToken();
                //$tmpUserArray['username'] = $adminUser->getUserName();
                $tmpUserArray['email'] = $adminUser->getEmail();
                $tmpUserArray['first_name'] = $adminUser->getFirstname();
                $tmpUserArray['last_name'] = $adminUser->getLastname();
                //$tmpUserArray['mobile_phone'] = $adminUser->getMobilePhone();
                $userArray[] = $tmpUserArray;
                $emails[] = $adminUser->getEmail();
            }
        }
        try {
            $return = Mage::getModel('foomanjirafe/api_application')-> sync($appId, $adminToken, $userArray, $siteArray);
            foreach ($return['users'] as $jirafeUserInfo) {
                $adminUser = Mage::getModel('admin/user')->load($jirafeUserInfo['email'],'email');
                $adminUser->setJirafeUserToken($jirafeUserInfo['token'])->save();
            }

            //TODO: change $i to returned external_id
            $i=1;
            foreach ($return['store'] as $jirafeStoreInfo) {
                $store = Mage::app()->getStore($i);
                $jirafeHelper->setStoreConfig('site_id',$jirafeStoreInfo['site_id'], $store->getId());
                $jirafeHelper->setStoreConfig('site_settings_hash', $this->_createSiteSettingsHash($store), $store->getId());
                $i++;
            }

        } catch (Exception $e) {
            return false;
        }
        return true;
    }

}
