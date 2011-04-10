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
                try {
                    $baseUrl = Mage::helper('foomanjirafe')->getUnifiedStoreBaseUrl(Mage::getStoreConfig('web/unsecure/base_url', $defaultStoreId));
                    $return = Mage::getModel('foomanjirafe/api_application')->update($appId, $baseUrl);
                    $changeHash = true;
                } catch (Exception $e) {
                    Mage::logException($e);
                    Mage::helper('foomanjirafe')->setStoreConfig('last_status_message', $e->getMessage());
                    Mage::helper('foomanjirafe')->setStoreConfig('last_status', Fooman_Jirafe_Helper_Data::JIRAFE_STATUS_ERROR);
                    return false;
                }
            }
        } else {
            //retrieve new application id from jirafe server
            try {
                $baseUrl = Mage::helper('foomanjirafe')->getUnifiedStoreBaseUrl(Mage::getStoreConfig('web/unsecure/base_url', $defaultStoreId));
                $return = Mage::getModel('foomanjirafe/api_application')->create(Mage::helper('foomanjirafe')->getStoreDescription(Mage::app()->getStore($defaultStoreId)), $baseUrl);
                if(empty($return['app_id']) || empty($return['token'])) {
                    throw new Exception ('Jirafe did not return a valid application Id or token.');
                }
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::helper('foomanjirafe')->setStoreConfig('last_status_message', $e->getMessage());
                Mage::helper('foomanjirafe')->setStoreConfig('last_status', Fooman_Jirafe_Helper_Data::JIRAFE_STATUS_ERROR);
                return false;
            }
            Mage::helper('foomanjirafe')->setStoreConfig('app_id', $return['app_id']);
            Mage::helper('foomanjirafe')->setStoreConfig('app_token', $return['token']);
            $appId = $return['app_id'];
            $changeHash = true;
            Mage::helper('foomanjirafe')->setStoreConfig('last_status_message', Mage::helper('foomanjirafe')->__('Application successfully set up'));
            Mage::helper('foomanjirafe')->setStoreConfig('last_status', Fooman_Jirafe_Helper_Data::JIRAFE_STATUS_APP_TOKEN_RECEIVED);
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
        $baseUrl = Mage::helper('foomanjirafe')->getUnifiedStoreBaseUrl(Mage::getStoreConfig('web/unsecure/base_url', $storeId));
        return md5($baseUrl . Mage::app()->getStore($storeId)->getName());
    }

    public function getAdminUsers()
    {
        $adminUserArray = array();
        $adminUsers = Mage::getSingleton('admin/user')->getCollection();
        foreach ($adminUsers as $adminUser) {
            if ($adminUser->getIsActive() &&  $adminUser->getEmail()) {
                $tmpUser = array();
                if( $adminUser->getJirafeUserToken()) {
                    $tmpUser['token'] = $adminUser->getJirafeUserToken();
                }
                $tmpUser['username'] = Mage::helper('foomanjirafe')->createJirafeUserId($adminUser);
                $tmpUser['email'] = Mage::helper('foomanjirafe')->createJirafeUserEmail($adminUser);
                $tmpUser['first_name'] = $adminUser->getFirstname();
                $tmpUser['last_name'] = $adminUser->getLastname();
                //$tmpUser['mobile_phone'] = $adminUser->getMobilePhone();
                $adminUserArray[] = $tmpUser;
            }
        }
        
        return $adminUserArray;
    }
    
    public function getStores()
    {
        Mage::app()->getConfig()->removeCache();
        $storeArray = array();
        $stores = Mage::helper('foomanjirafe')->getStores();
        foreach ($stores as $storeId => $store) {
            $tmpStore = array();
            $tmpStore['site_id'] = Mage::helper('foomanjirafe')->getStoreConfig('site_id', $store->getId());
            $tmpStore['external_id'] = $storeId;
            $tmpStore['description'] = Mage::helper('foomanjirafe')->getStoreDescription($store);
            //newly created stores don't fall back on global config values
            $tmpStore['url'] = Mage::helper('foomanjirafe')->getUnifiedStoreBaseUrl($store->getConfig('web/unsecure/base_url') ? $store->getConfig('web/unsecure/base_url') : Mage::getStoreConfig('web/unsecure/base_url'));
            $tmpStore['timezone'] = $store->getConfig('general/locale/timezone') ? $store->getConfig('general/locale/timezone') : Mage::getStoreConfig('general/locale/timezone');
            $tmpStore['currency'] = $store->getConfig('currency/options/base') ? $store->getConfig('currency/options/base') : Mage::getStoreConfig('currency/options/base');
            $tmpStore['checkout_goal_id'] = Mage::helper('foomanjirafe')->getStoreConfig('checkoutGoalId', $store->getId());
            $storeArray[] = $tmpStore;
        }
        
        return $storeArray;
    }
    
    /**
     * Save user info that has come back from the Jirafe sync process.  Only save information that changed, so that we do not
     * kick off another sync process.
     */
    public function saveUserInfo($jirafeUsers)
    {
        if(!empty($jirafeUsers)) {
            foreach ($jirafeUsers as $jirafeUser) {
                $email = Mage::helper('foomanjirafe')->getUserEmail($jirafeUser['email']);
                $adminUser = Mage::getModel('admin/user')->load($email,'email');
                if ($adminUser->getId()) {
                    $changed = false;
                    if ($jirafeUser['email'] != $adminUser->getJirafeUserID()) {
                        $adminUser->setJirafeUserId($jirafeUser['email']);
                        $changed = true;
                    }
                    if ($jirafeUser['token'] != $adminUser->getJirafeUserToken()) {
                        $adminUser->setJirafeUserToken($jirafeUser['token']);
                        $changed = true;
                    }
                    if ($changed) {
                        $adminUser->save();
                    }
                }
            }
        }
    }
    /**
     * Save store info that has come back from the Jirafe sync process.  Only save information that changed, so that we do not
     * kick off another sync process.
     */
    public function saveStoreInfo($jirafeSites)
    {
        if(!empty($jirafeSites)) {
            foreach ($jirafeSites as $jirafeSite) {
                $store = Mage::app()->getStore($jirafeSite['external_id'])->load($jirafeSite['external_id']);
                // Site ID
                $siteId = Mage::helper('foomanjirafe')->getStoreConfig('site_id', $store->getId());
                if ($siteId != $jirafeSite['site_id']) {
                    Mage::helper('foomanjirafe')->setStoreConfig('site_id', $jirafeSite['site_id'], $store->getId());
                }
                // Checkout Goal ID
                $goalId = Mage::helper('foomanjirafe')->getStoreConfig('checkoutGoalId', $store->getId());
                if ($goalId != $jirafeSite['checkout_goal_id']) {
                    Mage::helper('foomanjirafe')->setStoreConfig('checkoutGoalId', $jirafeSite['checkout_goal_id'], $store->getId());
                }
            }
        }
    }
    
    public function syncUsersStores()
    {
        if (!Mage::registry('foomanjirafe_sync_run')) {
            Mage::register('foomanjirafe_sync_run', true);
            
            $appId = Mage::helper('foomanjirafe')->getStoreConfig('app_id');
            $adminToken = Mage::helper('foomanjirafe')->getStoreConfig('app_token');
            
            if (empty($appId)) {
                $appId = $this->checkAppId();
            }
            
            $userArray = $this->getAdminUsers();
            $storeArray = $this->getStores();

            try {
                $return = Mage::getModel('foomanjirafe/api_resource')->sync($appId, $adminToken, $userArray, $storeArray);
                $this->saveUserInfo($return['users']);
                $this->saveStoreInfo($return['sites']);
            } catch (Exception $e) {
                Mage::helper('foomanjirafe')->setStoreConfig('last_status_message', $e->getMessage());
                Mage::helper('foomanjirafe')->setStoreConfig('last_status', Fooman_Jirafe_Helper_Data::JIRAFE_STATUS_ERROR);
                return false;
            }
            Mage::helper('foomanjirafe')->setStoreConfig('last_status_message', Mage::helper('foomanjirafe')->__('Jirafe sync completed successfully'));
            Mage::helper('foomanjirafe')->setStoreConfig('last_status', Fooman_Jirafe_Helper_Data::JIRAFE_STATUS_SYNC_COMPLETED);

            return true;
        }
    }

    public function sendLogUpdate ($data)
    {
        return Mage::getModel('foomanjirafe/api_log')->sendLog(Mage::helper('foomanjirafe')->getStoreConfig('app_token'), $data);
    }

}
