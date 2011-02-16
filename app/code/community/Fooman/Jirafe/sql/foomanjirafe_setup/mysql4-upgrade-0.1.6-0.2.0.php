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
Mage::log('Running Fooman Jirafe DB upgrade 0.2.0');
$installer = $this;
/* @var $installer Fooman_Jirafe_Model_Mysql4_Setup */

$installer->startSetup();

// Modify tables with the new DB schema
Mage::helper('foomanjirafe/setup')->runDbSchemaUpgrade($installer, '0.2.0');

// Add defaults into config file
$keys = array('isActive', 'isDashboardActive', 'isEmailActive');
foreach ($keys as $key) {
	$value = Mage::helper('foomanjirafe')->getStoreConfig($key);
	
	if (is_null($value)) {
		Mage::helper('foomanjirafe')->setStoreConfig($key, '1');
	}
}

// Get email addresses in the global jirafe settings
$emails = explode(',', Mage::helper('foomanjirafe')->getStoreConfig('emails'));
$reportType = Mage::helper('foomanjirafe')->getStoreConfig('reportType') == 'detail' ? 'detail' : 'simple';
$suppress = 'no';

if (!empty($emails)) {
	$firstUser = null;
	$orphanEmails = array();
	// Get the list of stores that we will put in each users email
	$storeIds = Mage::helper('foomanjirafe')->getStoreIds();
	// Iterate through the emails and find the admin user for the email
	foreach ($emails as $email) {
		$adminUser = Mage::helper('foomanjirafe')->getAdminUserByEmail($email);
		if (!empty($adminUser)) {
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

// TODO Remove the emails field in global Jirafe settings
// TODO Remove the ReportType field in global Jirafe settings

// Once complete, reinit config files
// reloading the config on earlier Magento versions causes an infinite loop
if(version_compare(Mage::getVersion(), '1.3.4.0') > 0) {
	Mage::app()->getConfig()->reinit();
}
// Run cron for the first time since the upgrade, so that users can see any changes right away.
Mage::getModel('foomanjirafe/report')->cron(null, true);

$installer->endSetup();

