<?php

$installer = $this;
/* @var $installer Fooman_Jirafe_Model_Mysql4_Setup */

$installer->startSetup();
Mage::helper('foomanjirafe/setup')->runDbSchemaUpgrade($installer, '0.1.1');
$installer->endSetup();