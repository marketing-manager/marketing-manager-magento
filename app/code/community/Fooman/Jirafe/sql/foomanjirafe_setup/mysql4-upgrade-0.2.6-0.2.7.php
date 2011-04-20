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
$version = '0.2.7';
Mage::log('Running Fooman Jirafe DB upgrade '.$version);

$installer = $this;
/* @var $installer Fooman_Jirafe_Model_Mysql4_Setup */

$installer->startSetup();

// Move alternative emails from account settings to global setting
$adminUsers = Mage::getSingleton('admin/user')->getCollection();
$emails = array();
foreach ($adminUsers as $adminUser) {
    $alsoSendToEmails = $adminUser->getJirafeAlsoSendTo();
    if($alsoSendToEmails) {
        foreach(explode(',',$alsoSendToEmails) as $altEmail) {
            if(!empty($altEmail)) {
                $emails[trim($altEmail)] = trim($altEmail);
            }
        }
    }
}
if (!empty($emails)) {
    Mage::helper('foomanjirafe')->setStoreConfig('also_send_emails_to', implode(',', $emails));
}

// Modify tables with the new DB schema
Mage::helper('foomanjirafe/setup')->runDbSchemaUpgrade($installer, $version);

//Run sync when finished with install/update
Mage::getModel('foomanjirafe/jirafe')->initialSync($version);