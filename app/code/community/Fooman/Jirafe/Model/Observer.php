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
        if (!$order->getJirafeExportStatus()) {

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
        if (Mage::registry('foomanjirafe_sync') || Mage::registry('foomanjirafe_upgrade')) {
            //to prevent a password change unset it here for pre 1.4.0.0
            if (version_compare(Mage::getVersion(), '1.4.0.0') < 0) {
                $user->unsPassword();
            }
            return;
        }

        $jirafeUserId = $user->getJirafeUserId();
        $jirafeToken = $user->getJirafeUserToken();

        $jirafeSendEmail = Mage::app()->getRequest()->getPost('jirafe_send_email');
        $jirafeEmailReportType = Mage::app()->getRequest()->getPost('jirafe_email_report_type');
        $jirafeEmailSuppress = Mage::app()->getRequest()->getPost('jirafe_email_suppress');
        $jirafeAlsoSendTo = str_replace(array("\r", " "), "", str_replace("\n", ",", Mage::app()->getRequest()->getPost('jirafe_also_send_to')));
        
        // Check to see if some user fields have changed
        if (!$user->getId() ||
            $user->dataHasChangedFor('firstname') ||
            $user->dataHasChangedFor('username') ||
            $user->dataHasChangedFor('email') ||
            empty($jirafeUserId) ||
            empty($jirafeToken)) {
            Mage::register('foomanjirafe_sync', true);
        }
        
        if ($jirafeSendEmail != $user->getJirafeSendEmail()) {
            $user->setJirafeSendEmail($jirafeSendEmail);
            $user->setDataChanges(true);
        }
        if ($jirafeEmailReportType != $user->getJirafeEmailReportType()) {
            $user->setJirafeEmailReportType($jirafeEmailReportType);
            $user->setDataChanges(true);
        }
        if ($jirafeEmailSuppress != $user->getJirafeEmailSuppress()) {
            $user->setJirafeEmailSuppress($jirafeEmailSuppress);
            $user->setDataChanges(true);
        }
        if ($jirafeAlsoSendTo != $user->getJirafeAlsoSendTo()) {
            $user->setJirafeEmails($jirafeAlsoSendTo);
            $user->setDataChanges(true);
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
        $store = $observer->getEvent()->getStore();
        // If the object is new, or has any data changes, sync
        if (!$store->getId() || $store->hasDataChanges()) {
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
        $storeGroup = $observer->getEvent()->getStoreGroup();
        // If the object is new, or has any data changes, sync
        if (!$storeGroup->getId() || $storeGroup->hasDataChanges()) {
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
        $configData = $observer->getEvent()->getObject();
        if($configData instanceof Mage_Core_Model_Config_Data) {
            $path = $configData->getPath();
            $keys = array('web/unsecure/base_url', 'general/locale/timezone', 'currency/options/base');
            if (in_array($path, $keys)) {
                Mage::getModel('foomanjirafe/jirafe')->syncUsersStores();
            }
        }
    }

    /**
     * we can't add external javascript via normal Magento means
     * adding child elements to the head block or dashboard are also not automatically rendered
     * add foomanjirafe_dashboard_head via this observer
     * add foomanjirafe_dashboard_toggle via this observer
     * add foomanjirafe_adminhtml_permissions_user_edit_tab_jirafe via this observer
     *
     * @param $observer
     */
    public function coreBlockAbstractToHtmlBefore($observer)
    {
        $block = $observer->getEvent()->getBlock();
        $params = array('_relative'=>true);
        if ($area = $block->getArea()) {
            $params['_area'] = $area;
        }
        if ($block instanceof Mage_Adminhtml_Block_Permissions_User_Edit_Tabs) {
            $block->addTab('jirafe_section', array(
                'label'     => Mage::helper('foomanjirafe')->__('Jirafe Analytics'),
                'title'     => Mage::helper('foomanjirafe')->__('Jirafe Analytics'),
                'content'   => $block->getLayout()->createBlock('foomanjirafe/adminhtml_permissions_user_edit_tab_jirafe')->toHtml(),
                'after'     => 'roles_section'
            ));
        }
        if ($block instanceof Mage_Adminhtml_Block_Page_Head) {
            $block->setOrigTemplate(Mage::getBaseDir('design').DS.Mage::getDesign()->getTemplateFilename($block->getTemplate(), $params));
            $block->setTemplate('fooman/jirafe/dashboard-head.phtml');
            $block->setFoomanBlock($block->getLayout()->createBlock('foomanjirafe/adminhtml_dashboard_js'));
        }
        if ($block instanceof Mage_Adminhtml_Block_Dashboard) {
            $block->setOrigTemplate(Mage::getBaseDir('design').DS.Mage::getDesign()->getTemplateFilename($block->getTemplate(), $params));
            $block->setTemplate('fooman/jirafe/dashboard-toggle.phtml');
            $block->setFoomanBlock($block->getLayout()->createBlock('foomanjirafe/adminhtml_dashboard_toggle'));
        }
    }



    public function readyToBuy ($observer)
    {
        Mage::getSingleton('customer/session')->setPiwikVisitorType(Fooman_Jirafe_Block_Js::VISITOR_READY2BUY);
    }
}