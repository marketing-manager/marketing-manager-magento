<?php

$installer = $this;
/* @var $installer Fooman_Jirafe_Model_Mysql4_Setup */

$installer->startSetup();

foreach (Mage::helper('foomanjirafe/setup')->getDbSchema('0.1.0') as $instruction) {
    switch ($instruction['type']) {
        case 'table':
            $keys = array();
            $columns = array();

            foreach ($instruction['items'] as $item) {
                switch ($item[0]) {
                    case 'sql-column':
                        $columns[] = '`'.$item[1].'` '.$item[2];
                        break;
                    case 'key':
                        $keys[] = $item[1] .' (`'.$item[2].'`)';
                        break;
                }
            }
            $tableDetails = implode(",",array_merge($columns,$keys));
            $sql = "DROP TABLE IF EXISTS `{$this->getTable($instruction['name'])}`;\n";
            $sql .="CREATE TABLE `{$this->getTable($instruction['name'])}` (".$tableDetails.") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
            $installer->run($sql);
            Mage::log($sql);
    }
    
}
$installer->endSetup();