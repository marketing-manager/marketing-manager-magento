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

Mage::log('Running Fooman Jirafe DB install 0.1.0');
$installer = $this;
/* @var $installer Fooman_Jirafe_Model_Mysql4_Setup */

$installer->startSetup();

// Upgrade the DB version
Mage::helper('foomanjirafe/setup')->runDbSchemaUpgrade($installer, '0.1.0');

// Automatically set automatic emails to every active admin user
$adminUsers = Mage::getSingleton('admin/user')->getCollection();
$emails = array();
foreach ($adminUsers as $adminUser) {
	if ($adminUser->getIsActive()) {
		$emails[] = $adminUser->getEmail();
	}
}
if (!empty($emails)) {
	Mage::helper('foomanjirafe')->setStoreConfig('emails',implode(',', $emails));
}

$installer->endSetup();