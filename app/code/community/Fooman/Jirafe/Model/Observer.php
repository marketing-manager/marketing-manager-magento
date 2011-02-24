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

    public function syncJirafeStore ($observer)
    {
        $jirafe = Mage::getModel('foomanjirafe/jirafe');
        $appId = $jirafe->checkAppId();
        $store = $observer->getEvent()->getObject();
        $jirafe->checkSiteId ($appId, $store);
    }


    public function saveJirafeStoreEmailMapping ($observer)
    {
        $user = $observer->getEvent()->getObject();
        $jirafeStoreIds = implode(',' ,Mage::app()->getRequest()->getPost('jirafe_send_email_for_store'));
        $jirafeEmailReportType = Mage::app()->getRequest()->getPost('jirafe_email_report_type');
        $jirafeEmailSuppress = (int)Mage::app()->getRequest()->getPost('jirafe_email_suppress');
        $jirafeEmails = str_replace(array("\r"," "),"",str_replace("\n",",",Mage::app()->getRequest()->getPost('jirafe_emails')));

        $jirafeSettingsHaveChanged = false;
        if($jirafeStoreIds != $user->getJirafeSendEmailForStore()){
            $user->setJirafeSendEmailForStore($jirafeStoreIds);
            $user->setDataChanges(true);
            $jirafeSettingsHaveChanged = true;
            //TODO: Jirafe User to Store mapping has changed trigger api call
        }
        if($jirafeEmailReportType != $user->getJirafeEmailReportType()){
            $user->setJirafeEmailReportType($jirafeEmailReportType);
            $user->setDataChanges(true);
            $jirafeSettingsHaveChanged = true;
            //TODO: Jirafe Email report type have changed trigger api call
        }
        if($jirafeEmailSuppress != $user->getJirafeEmailSuppress()){
            $user->setJirafeEmailSuppress($jirafeEmailSuppress);
            $user->setDataChanges(true);
            $jirafeSettingsHaveChanged = true;
            //TODO: Jirafe Email suppress have changed trigger api call
        }
        if($jirafeEmails != $user->getJirafeEmails()){
            $user->setJirafeEmails($jirafeEmails);
            $user->setDataChanges(true);
            $jirafeSettingsHaveChanged = true;
            //TODO: Jirafe Emails have changed trigger api call
        }

        if($jirafeSettingsHaveChanged) {
            //TODO: call api sync
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