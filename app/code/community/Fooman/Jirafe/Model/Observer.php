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
    /* the event with the order_ids added was only introduced in Magento 1.4.2.0
    public function setSuccessfulOrderIds(Varien_Event_Observer $observer)
    {
        $orderIds = $observer->getEvent()->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return;
        }
        $block = Mage::app()->getFrontController()->getAction()->getLayout()->getBlock('foomanjirafe_js');
        if ($block) {
            $block->setOrderIds($orderIds);
        }
    }
     */

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
        $jirafeEmailReportType = Mage::app()->getRequest()->getPost('jirafe_email_report_type');
        if(!$jirafeEmailReportType) {
            //we don't have Jirafe POST data from the My Account Form = we are adding a new user
            //TODO: is also called when updating a user via System > Role
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
        $user = $observer->getEvent()->getObject();
        $jirafeEmailReportType = Mage::app()->getRequest()->getPost('jirafe_email_report_type');

        if ($jirafeEmailReportType) {
            //we have Jirafe POST data from the My Account Form = we are updating an existing user
            $jirafeStoreIds = implode(',', Mage::app()->getRequest()->getPost('jirafe_send_email_for_store'));
            $jirafeEmailSuppress = (int) Mage::app()->getRequest()->getPost('jirafe_email_suppress');
            $jirafeEmails = str_replace(array("\r", " "), "", str_replace("\n", ",", Mage::app()->getRequest()->getPost('jirafe_emails')));

            $jirafeSettingsHaveChanged = false;
            if ($jirafeStoreIds != $user->getJirafeSendEmailForStore()) {
                $user->setJirafeSendEmailForStore($jirafeStoreIds);
                $user->setDataChanges(true);
                $jirafeSettingsHaveChanged = true;
            }
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
            if ($jirafeEmails != $user->getJirafeEmails()) {
                $user->setJirafeEmails($jirafeEmails);
                $user->setDataChanges(true);
                $jirafeSettingsHaveChanged = true;
            }
            if($jirafeSettingsHaveChanged) {
                Mage::getModel('foomanjirafe/jirafe')->syncUsersAndStores();
            }
        }
    }

    /**
     * we can't add external javascript via normal Magento means
     * adding child elements to the head block are also not automatically rendered
     * add foomanjirafe_dashboard_head via this observer
     *
     * @param $observer
     */
    public function appendJsToAdminHead($observer)
    {
        $block = $observer->getEvent()->getBlock();
        $transport = $observer->getEvent()->getTransport();
        if ($block instanceof Mage_Adminhtml_Block_Page_Head) {
            $transport->setHtml($transport->getHtml().$block->getChildHtml('foomanjirafe_dashboard_head'));
        }
    }
}