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

    const STATUS_ORDER_NOT_EXPORTED = 0;
    const STATUS_ORDER_EXPORTED = 1;
    const STATUS_ORDER_FAILED = 2;

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
        //Mage::app()->getConfig()->reinit();

        //check if we already have a jirafe store id for this Magento store
        $siteId = Mage::helper('foomanjirafe')->getStoreConfig('site_id', $store->getStoreId());
        $adminToken = Mage::helper('foomanjirafe')->getStoreConfig('app_token');
        $currentHash = $this->_createSiteSettingsHash($store);

        $dbHash = Mage::helper('foomanjirafe')->getStoreConfigDirect('site_settings_hash', $store->getStoreId());

        //check if we haven't yet assigned a site id or if settings have changed
        if (!$siteId || $currentHash != $dbHash) {
            $this->syncUsersAndStores();
            $siteId = Mage::helper('foomanjirafe')->getStoreConfig('site_id', $store->getStoreId());
        }
        if(empty($siteId)){
            Mage::helper('foomanjirafe')->setStoreConfig('last_status_message', Mage::helper('foomanjirafe')->__('Jirafe site_id is empty.'));
            Mage::helper('foomanjirafe')->setStoreConfig('last_status', Fooman_Jirafe_Helper_Data::JIRAFE_STATUS_ERROR);
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
        $baseUrl = Mage::helper('foomanjirafe')->getUnifiedStoreBaseUrl(Mage::getStoreConfig('web/unsecure/base_url', $store->getId()));
        return  md5(Mage::helper('foomanjirafe')->getStoreDescription($store) .
                    $baseUrl .
                    $store->getConfig('general/locale/timezone') .
                    $store->getConfig('currency/options/base')
                );
    }


    public function syncUsersAndStores ()
    {

        if (!Mage::registry('foomanjirafe_sync_running')) {
            Mage::register('foomanjirafe_sync_running', true);
            $jirafeHelper = Mage::helper('foomanjirafe');

            $appId =  $jirafeHelper->getStoreConfig('app_id');
            $adminToken =  $jirafeHelper->getStoreConfig('app_token');

            $userArray = array();
            $siteArray = array();

            foreach ($jirafeHelper->getStores() as $storeId => $store) {
                Mage::app()->getConfig()->removeCache();
                $tmpStoreArray = array();
                $siteId = $store->getConfig(Fooman_Jirafe_Helper_Data::XML_PATH_FOOMANJIRAFE_SETTINGS.'site_id');
                if ($siteId){
                    $tmpStoreArray['site_id'] = $siteId;
                }
                $tmpStoreArray['external_id'] = $storeId;
                $tmpStoreArray['description'] = $jirafeHelper->getStoreDescription($store);
                //newly created stores don't fall back on global config values
                $tmpStoreArray['url'] = Mage::helper('foomanjirafe')->getUnifiedStoreBaseUrl($store->getConfig('web/unsecure/base_url')?$store->getConfig('web/unsecure/base_url'):Mage::getStoreConfig('web/unsecure/base_url'));
                $tmpStoreArray['timezone'] = $store->getConfig('general/locale/timezone')?$store->getConfig('general/locale/timezone'):Mage::getStoreConfig('general/locale/timezone');
                $tmpStoreArray['currency'] = $store->getConfig('currency/options/base')?$store->getConfig('currency/options/base'):Mage::getStoreConfig('currency/options/base');
                $siteArray[] = $tmpStoreArray;
            }

            $i = 0;
            $adminUsers = Mage::getSingleton('admin/user')->getCollection();
            foreach ($adminUsers as $adminUser) {
                if ($adminUser->getIsActive() &&  $adminUser->getEmail()) {
                    $tmpUserArray = array();
                    if( $adminUser->getJirafeUserToken()) {
                        $tmpUserArray['token'] = $adminUser->getJirafeUserToken();
                    }
                    $tmpUserArray['username'] = $jirafeHelper->createJirafeUserId($adminUser);
                    $tmpUserArray['email'] = $jirafeHelper->createJirafeUserEmail($adminUser);
                    $tmpUserArray['first_name'] = $adminUser->getFirstname();
                    $tmpUserArray['last_name'] = $adminUser->getLastname();
                    //$tmpUserArray['mobile_phone'] = $adminUser->getMobilePhone();
                    $userArray[$i++] = $tmpUserArray;
                    $emails[] = $adminUser->getEmail();
                }
            }
            try {
                $return = Mage::getModel('foomanjirafe/api_resource')-> sync($appId, $adminToken, $userArray, $siteArray);
                if(isset($return['users']) && !empty($return['users'])) {
                    foreach ($return['users'] as $jirafeUserInfo) {
                        $adminEmail = $jirafeHelper->getUserEmail($jirafeUserInfo['email']);
                        $adminUser = Mage::getModel('admin/user')->load($adminEmail,'email');
                        if($adminUser->getId()) {
                            $adminUser->setJirafeUserId($jirafeUserInfo['email']);
                            $adminUser->setJirafeUserToken($jirafeUserInfo['token'])->save();
                        }
                    }
                }

                if(isset($return['sites']) && !empty($return['sites'])) {
                    foreach ($return['sites'] as $jirafeStoreInfo) {
                        $store = Mage::app()->getStore($jirafeStoreInfo['external_id'])->load($jirafeStoreInfo['external_id']);
                        $jirafeHelper->setStoreConfig('site_id',$jirafeStoreInfo['site_id'], $store->getId());
                        $jirafeHelper->setStoreConfig('site_settings_hash', $this->_createSiteSettingsHash($store), $store->getId());
                        if(isset($jirafeStoreInfo['checkout_goal_id'])) {
                            $jirafeHelper->setStoreConfig('checkoutGoalId',$jirafeStoreInfo['checkout_goal_id'], $store->getId());
                        }
                    }
                }

            } catch (Exception $e) {
                Mage::helper('foomanjirafe')->setStoreConfig('last_status_message', $e->getMessage());
                Mage::helper('foomanjirafe')->setStoreConfig('last_status', Fooman_Jirafe_Helper_Data::JIRAFE_STATUS_ERROR);
                Mage::unregister('foomanjirafe_sync_running');
                return false;
            }
            Mage::helper('foomanjirafe')->setStoreConfig('last_status_message', Mage::helper('foomanjirafe')->__('Jirafe sync completed successfully'));
            Mage::helper('foomanjirafe')->setStoreConfig('last_status', Fooman_Jirafe_Helper_Data::JIRAFE_STATUS_SYNC_COMPLETED);
            Mage::unregister('foomanjirafe_sync_running');
            Mage::register('foomanjirafe_sync_has_run', true);
            return true;
        }
    }

    public function sendLogUpdate ($data)
    {
        return Mage::getModel('foomanjirafe/api_log')->sendLog(Mage::helper('foomanjirafe')->getStoreConfig('app_token'), $data);
    }

}
