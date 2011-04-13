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

class Fooman_Jirafe_Model_Observer
{

    protected function _initPiwikTracker ($storeId)
    {
        $siteId = Mage::helper('foomanjirafe')->getStoreConfig('site_id', $storeId);
        $appToken = Mage::helper('foomanjirafe')->getStoreConfig('app_token');
        $siteId = Mage::helper('foomanjirafe')->getStoreConfig('site_id', $storeId);

        $jirafePiwikUrl = 'http://' . Fooman_Jirafe_Model_Api::JIRAFE_PIWIK_BASE_URL;
        $piwikTracker = new Fooman_Jirafe_Model_JirafeTracker($siteId, $jirafePiwikUrl);
        $piwikTracker->setTokenAuth($appToken);
        $piwikTracker->disableCookieSupport();

        return $piwikTracker;
    }

    /**
     * save Piwik visitorId and attributionInfo to order db table
     * for later use
     *
     * @param $observer
     */
    public function savePiwikData ($observer)
    {
        $order = $observer->getEvent()->getOrder();
        $piwikTracker = $this->_initPiwikTracker($order->getStoreId());
        if (Mage::getDesign()->getArea() == 'frontend') {
            $order->setJirafeVisitorId($piwikTracker->getVisitorId());
            $order->setJirafeAttributionData($piwikTracker->getAttributionInfo());
            $order->setJirafePlacedFromFrontend(true);
        }
    }
    
    /**
     * Check fields in the user object to see if we should run sync
     * use POST data to identify update to existing users
     * only call sync if relevant data has changed
     *
     * @param $observer
     */
    public function adminUserSaveBefore($observer)
    {
        Mage::helper('foomanjirafe')->debug('adminUserSaveBefore');
        $user = $observer->getEvent()->getObject();
        
        $jirafeUserId = $user->getJirafeUserId();
        $jirafeToken = $user->getJirafeUserToken();
        
        // Check to see if some user fields have changed
        if ($user->isObjectNew() ||
            $user->dataHasChangedFor('firstname') ||
            $user->dataHasChangedFor('username') ||
            $user->dataHasChangedFor('email') ||
            empty($jirafeUserId) ||
            empty($jirafeToken)) {
            Mage::register('foomanjirafe_sync', true);
        }
    }

    /**
     * Check to see if we need to sync.  If so, do it.
     *
     * @param $observer
     */
    public function adminUserSaveAfter($observer)
    {
        Mage::helper('foomanjirafe')->debug('adminUserSaveAfter');
        if (Mage::registry('foomanjirafe_sync')) {
            Mage::getModel('foomanjirafe/jirafe')->syncUsersStores();
        }
    }

    /**
     * We need to sync every time after we delete a user
     *
     * @param $observer
     */
    public function adminUserDeleteAfter($observer)
    {
        Mage::helper('foomanjirafe')->debug('adminUserDeleteAfter');
        Mage::getModel('foomanjirafe/jirafe')->syncUsersStores();
    }

    /**
     * Check fields in the store object to see if we should run sync
     * only call sync if relevant data has changed
     *
     * @param $observer
     */
    public function storeSaveBefore($observer)
    {
        Mage::helper('foomanjirafe')->debug('storeSaveBefore');
        $store = $observer->getEvent()->getDataObject();
        // If the object is new, or has any data changes, sync
        if ($store->isObjectNew() || $store->hasDataChanges()) {
            Mage::register('foomanjirafe_sync', true);
        }
    }
    
    /**
     * Check to see if we need to sync.  If so, do it.
     *
     * @param $observer
     */
    public function storeSaveAfter($observer)
    {
        Mage::helper('foomanjirafe')->debug('storeSaveAfter');
        if (Mage::registry('foomanjirafe_sync')) {
            Mage::getModel('foomanjirafe/jirafe')->syncUsersStores();
        }
    }

    /**
     * We need to sync every time after we delete a store
     *
     * @param $observer
     */
    public function storeDeleteAfter($observer)
    {
        Mage::helper('foomanjirafe')->debug('storeDeleteAfter');
        Mage::getModel('foomanjirafe/jirafe')->syncUsersStores();
    }

    /**
     * Check fields in the store group object to see if we should run sync
     * only call sync if relevant data has changed
     *
     * @param $observer
     */
    public function storeGroupSaveBefore($observer)
    {
        Mage::helper('foomanjirafe')->debug('storeGroupSaveBefore');
        $storeGroup = $observer->getEvent()->getDataObject();
        // If the object is new, or has any data changes, sync
        if ($storeGroup->isObjectNew() || $storeGroup->hasDataChanges()) {
            Mage::register('foomanjirafe_sync', true);
        }
    }
    
    /**
     * Check to see if we need to sync.  If so, do it.
     *
     * @param $observer
     */
    public function storeGroupSaveAfter($observer)
    {
        Mage::helper('foomanjirafe')->debug('storeGroupSaveAfter');
        if (Mage::registry('foomanjirafe_sync')) {
            Mage::getModel('foomanjirafe/jirafe')->syncUsersStores();
        }
    }

    /**
     * We need to sync every time after we delete a store group
     *
     * @param $observer
     */
    public function storeGroupDeleteAfter($observer)
    {
        Mage::helper('foomanjirafe')->debug('storeGroupDeleteAfter');
        Mage::getModel('foomanjirafe/jirafe')->syncUsersStores();
    }

    /**
     * sync a jirafe store after settings have been saved
     * checks local settings hash for settings before sync
     *
     * @param $observer
     */
    public function configSaveAfter($observer)
    {
        Mage::helper('foomanjirafe')->debug('syncAfterSettingsSave');
        $path = $observer->getEvent()->getConfigData()->getPath();
        $keys = array('web/unsecure/base_url', 'general/locale/timezone', 'currency/options/base');
        if (in_array($path, $keys)) {
            Mage::getModel('foomanjirafe/jirafe')->syncUsersStores();
        }
    }

    /**
     * we can't add external javascript via normal Magento means
     * adding child elements to the head block or dashboard are also not automatically rendered
     * add foomanjirafe_dashboard_head via this observer
     * add foomanjirafe_dashboard_toggle via this observer
     *
     * @param $observer
     */
    public function appendToAdminBlocks($observer)
    {
        $block = $observer->getEvent()->getBlock();
        $transport = $observer->getEvent()->getTransport();
        if ($block instanceof Mage_Adminhtml_Block_Page_Head) {
            $transport->setHtml($transport->getHtml().$block->getChildHtml('foomanjirafe_dashboard_head'));
        }
        if ($block instanceof Mage_Adminhtml_Block_Dashboard) {
            $transport->setHtml($transport->getHtml().$block->getChildHtml('foomanjirafe_dashboard_toggle'));
        }
    }

    public function readyToBuy ($observer)
    {
        Mage::getSingleton('customer/session')->setPiwikVisitorType(Fooman_Jirafe_Block_Js::VISITOR_READY2BUY);
    }
}