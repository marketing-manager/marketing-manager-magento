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
$version = '0.2.5';
Mage::log('Running Fooman Jirafe DB upgrade '.$version);
$installer = $this;
/* @var $installer Fooman_Jirafe_Model_Mysql4_Setup */

$installer->startSetup();

// Modify tables with the new DB schema
Mage::helper('foomanjirafe/setup')->runDbSchemaUpgrade($installer, $version);

// Loop through the users and tone down emailing to just those who need it
$adminUsers = Mage::getSingleton('admin/user')->getCollection();
foreach ($adminUsers as $adminUser) {
	$adminUser
		->setJirafeEmailSuppress('1')
		->save();
}

$installer->endSetup();

//Run sync when finished with install/update
Mage::getModel('foomanjirafe/jirafe')->initialSync($version);
