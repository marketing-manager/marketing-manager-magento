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
     * Track piwik goals for orders that have reached processing stage
     * TODO: this could be made configurable based on payment method used
     * 
     * @param $observer 
     */
    public function salesOrderSaveAfter ($observer)
    {
        $order = $observer->getEvent()->getOrder();
        if (!$order->getJirafeExportStatus() && $order->getState() == Mage_Sales_Model_Order::STATE_PROCESSING) {

            $piwikTracker = $this->_initPiwikTracker($order->getStoreId());            
            $piwikTracker->setCustomVariable('1', 'U', Fooman_Jirafe_Block_Js::VISITOR_CUSTOMER);
            $piwikTracker->setCustomVariable('5', 'orderId', $order->getIncrementId());            
            $piwikTracker->setIp($order->getRemoteIp());
            //$piwikTracker->setUrl();

            if ($order->getJirafeVisitorId()) {
                $piwikTracker->setVisitorId($order->getJirafeVisitorId());
            }

            if ($order->getJirafeAttributionData()) {
                $piwikTracker->setAttributionInfo($order->getJirafeAttributionData());
            }

            try {
                $checkoutGoalId = Mage::helper('foomanjirafe')->getStoreConfig('checkoutGoalId', $order->getStoreId());
                $piwikTracker->doTrackGoal($checkoutGoalId, $order->getBaseGrandTotal());
                $order->setJirafeExportStatus(Fooman_Jirafe_Model_Jirafe::STATUS_ORDER_EXPORTED);
            } catch (Exception $e) {
                Mage::logException($e);
                $order->setJirafeExportStatus(Fooman_Jirafe_Model_Jirafe::STATUS_ORDER_FAILED);
            }
        }
    }

    /**
     * sync a jirafe store when adding a new store or after saving
     * an existing store
     * checks local settings hash for settings before sync
     *
     * @param $observer
     */
    public function syncJirafeStore ($observer)
    {
        $jirafe = Mage::getModel('foomanjirafe/jirafe');
        $appId = $jirafe->checkAppId();
        if ($appId) {
            $store = $observer->getEvent()->getStore();
            $jirafe->checkSiteId($appId, $store);
        }
    }
    
    /**
     * sync a jirafe store after settings have been saved
     * checks local settings hash for settings before sync
     *
     * @param $observer
     */
    public function syncAfterSettingsSave ($observer)
    {
        Mage::helper('foomanjirafe')->debug('syncAfterSettingsSave');
        $settingsOfInterest = array(
            'web/unsecure/base_url',
            'general/locale/timezone',
            'currency/options/base'
        );
        Mage::helper('foomanjirafe')->debug($observer->getEvent()->getConfigData()->getPath());
        if (in_array($observer->getEvent()->getConfigData()->getPath(), $settingsOfInterest)) {
            $jirafe = Mage::getModel('foomanjirafe/jirafe');
            $appId = $jirafe->checkAppId();
            if ($appId) {
                $storeCollection = Mage::getModel('core/store')->getCollection();
                foreach ($storeCollection as $store) {
                    if (!Mage::registry('foomanjirafe_sync_has_run')) {
                        $jirafe->checkSiteId($appId, $store);
                    }
                }
            }
        }
    }

    /**
     * sync all stores and users with Jirafe
     * called after deleting a store
     *
     * @param $observer
     */
    public function fullSyncJirafeStore ($observer)
    {
        $jirafe = Mage::getModel('foomanjirafe/jirafe');
        $appId = $jirafe->checkAppId();
        if ($appId) {
            $jirafe->syncUsersAndStores();
        }
    }

    /**
     * sync all stores and users with Jirafe
     * called after saving a user
     *
     * @param $observer
     */
    public function fullSyncNewUser ($observer)
    {
        Mage::helper('foomanjirafe')->debug('fullSyncNewUser');
        if (!Mage::registry('foomanjirafe_single_user_sync_has_run')) {
            $jirafe = Mage::getModel('foomanjirafe/jirafe');
            $appId = $jirafe->checkAppId();
            if ($appId) {
                $jirafe->syncUsersAndStores();
            }
        }
    }

    /**
     * sync all stores and users with Jirafe
     * called after saving a user
     * use POST data to identify update to existing users
     * only call sync if Jirafe relevant data has changed
     *
     * @param $observer
     */
    public function saveJirafeStoreEmailMapping ($observer)
    {
        Mage::helper('foomanjirafe')->debug('saveJirafeStoreEmailMapping');
        $user = $observer->getEvent()->getObject();
        $jirafeEmailReportType = Mage::app()->getRequest()->getPost('jirafe_email_report_type');

        if ($jirafeEmailReportType) {
            //we have Jirafe POST data from the My Account Form = we are updating an existing user
            $jirafeEmailSuppress = (int) Mage::app()->getRequest()->getPost('jirafe_email_suppress');
            $jirafeAlsoSendTo = str_replace(array("\r", " "), "", str_replace("\n", ",", Mage::app()->getRequest()->getPost('jirafe_also_send_to')));

            $jirafeSettingsHaveChanged = false;
            /*
            $jirafeStoreIds = implode(',', Mage::app()->getRequest()->getPost('jirafe_send_email_for_store'));
            if ($jirafeStoreIds != $user->getJirafeSendEmailForStore()) {
                $user->setJirafeSendEmailForStore($jirafeStoreIds);
                $user->setDataChanges(true);
                $jirafeSettingsHaveChanged = true;
            }
             */
            if ($jirafeEmailReportType != $user->getJirafeEmailReportType()) {
                $user->setJirafeEmailReportType($jirafeEmailReportType);
                $user->setDataChanges(true);
                $jirafeSettingsHaveChanged = true;
            }
            if ($jirafeEmailSuppress != $user->getJirafeEmailSuppress()) {
                $user->setJirafeEmailSuppress($jirafeEmailSuppress);
                $user->setDataChanges(true);
                $jirafeSettingsHaveChanged = true;
            }
            if ($jirafeAlsoSendTo != $user->getJirafeAlsoSendTo()) {
                $user->setJirafeEmails($jirafeAlsoSendTo);
                $user->setDataChanges(true);
                $jirafeSettingsHaveChanged = true;
            }
            if($jirafeSettingsHaveChanged) {
                Mage::getModel('foomanjirafe/jirafe')->syncUsersAndStores();
            }
            Mage::register('foomanjirafe_single_user_sync_has_run', true);
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