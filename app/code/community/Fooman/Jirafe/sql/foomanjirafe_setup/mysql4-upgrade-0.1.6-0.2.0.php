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
$version = '0.2.0';
Mage::log('Running Fooman Jirafe DB upgrade '.$version);

$installer = $this;
/* @var $installer Fooman_Jirafe_Model_Mysql4_Setup */

$installer->startSetup();

// Modify tables with the new DB schema
Mage::helper('foomanjirafe/setup')->runDbSchemaUpgrade($installer, $version);

// Add defaults into config file
$keys = array('isActive', 'isEmailActive');
foreach ($keys as $key) {
	$value = Mage::helper('foomanjirafe')->getStoreConfig($key);
	
	if (is_null($value)) {
		Mage::helper('foomanjirafe')->setStoreConfig($key, '1');
	}
}

// Get email addresses in the global jirafe settings
$emails = explode(',', Mage::helper('foomanjirafe')->getStoreConfig('emails'));
$reportType = Mage::helper('foomanjirafe')->getStoreConfig('reportType') == 'detail' ? 'detail' : 'simple';
$suppress = '0';

if (!empty($emails)) {
    $firstUser = null;
    $orphanEmails = array();
    // Get the list of stores that we will put in each users email
    $storeIds = Mage::helper('foomanjirafe')->getStoreIds();
    // Iterate through the emails and find the admin user for the email
    foreach ($emails as $email) {
        $adminUser = Mage::getModel('admin/user')->load($email,'email');
        if ($adminUser->getId()) {
            $adminUser
                    ->setJirafeSendEmailForStore($storeIds)
                    ->setJirafeEmailReportType($reportType)
                    ->setJirafeEmailSuppress($suppress)
                    ->save();
            // Save the first user matched - we will add 'orphaned' email addresses to this one
            if (empty($firstUser)) {
                $firstUser = $adminUser;
            }
        } else {
            $orphanEmails[] = $email;
        }
    }
    // Save off any orphan emails to the first admin user
    if (!empty($firstUser) && !empty($orphanEmails)) {
        $firstUser
                ->setJirafeEmails(implode(',', $orphanEmails))
                ->save();
    }
}

// Remove the emails field in global Jirafe settings
$configModel = Mage::getModel('core/config_data');
$collection = $configModel->getCollection()->addFieldToFilter('path', Fooman_Jirafe_Helper_Data::XML_PATH_FOOMANJIRAFE_SETTINGS.'emails');
foreach ($collection as $jirafeOldSetting) {
    $jirafeOldSetting->delete();
}

// Remove the ReportType field in global Jirafe settings
$configModel = Mage::getModel('core/config_data');
$collection = $configModel->getCollection()->addFieldToFilter('path', Fooman_Jirafe_Helper_Data::XML_PATH_FOOMANJIRAFE_SETTINGS.'reportType');
foreach ($collection as $jirafeOldSetting) {
    $jirafeOldSetting->delete();
}

$installer->endSetup();
Mage::unregister('foomanjirafe_upgrade');

//Run sync when finished with install/update
Mage::getModel('foomanjirafe/jirafe')->initialSync($version);
