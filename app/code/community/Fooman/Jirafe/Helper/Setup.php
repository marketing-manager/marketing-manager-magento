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

class Fooman_Jirafe_Helper_Setup extends Mage_Core_Helper_Abstract
{

    public function getDbSchema ($version, $returnComplete=false)
    {
        $instructions = array();
        switch ($version) {
            case '0.2.6':
                $instructions = array_merge(
                        $instructions,
                            array(
                                array("type" =>"sql-column", "table" =>"sales/order", "name" =>"jirafe_visitor_id","params" =>"varchar(255) DEFAULT NULL"),
                                array("type" =>"sql-column", "table" =>"sales/order", "name" =>"jirafe_attribution_data","params" =>"text DEFAULT NULL"),
                                array("type" =>"sql-column", "table" =>"sales/order", "name" =>"jirafe_export_status","params" =>"int(5) DEFAULT 0"),
                                array("type" =>"sql-column", "table" =>"sales/order", "name" =>"jirafe_placed_from_frontend","params" =>"tinyint(1) DEFAULT 0"),
                                )
                        );
                if(!$returnComplete) {
                    break;
                }
                //nobreak intentionally;
            case '0.2.0':
                $instructions = array_merge(
                        $instructions,
                            array(
                                array("type" =>"sql-column", "table" =>"admin_user", "name" =>"jirafe_send_email_for_store","params" =>"varchar(255)"),
                                array("type" =>"sql-column", "table" =>"admin_user", "name" =>"jirafe_send_email","params" =>"tinyint(1) DEFAULT 0"),
                                array("type" =>"sql-column", "table" =>"admin_user", "name" =>"jirafe_email_report_type","params" =>"varchar(255) DEFAULT 'simple'"),
                                array("type" =>"sql-column", "table" =>"admin_user", "name" =>"jirafe_user_id","params" =>"varchar(255)"),
                                array("type" =>"sql-column", "table" =>"admin_user", "name" =>"jirafe_user_token","params" =>"varchar(255)"),
                                array("type" =>"sql-column", "table" =>"admin_user", "name" =>"jirafe_email_suppress","params" =>"tinyint(1)"),
                                array("type" =>"sql-column", "table" =>"admin_user", "name" =>"jirafe_also_send_to","params" =>"text")
                                )
                        );
                if(!$returnComplete) {
                    break;
                }
                //nobreak intentionally;
            case '0.1.1':
                $instructions = array_merge(
                        $instructions,
                            array(
                                array("type" =>"sql-column", "table" =>"foomanjirafe_report", "name" =>"store_id","params" =>"smallint(5) AFTER `report_id`"),
                                array("type" =>"sql-column", "table" =>"foomanjirafe_report", "name" =>"store_report_date","params" =>"timestamp AFTER `generated_by_jirafe_version`")
                                )
                        );
                if(!$returnComplete) {
                    break;
                }
                //nobreak intentionally;
            case '0.1.0':
                $instructions = array_merge(
                        $instructions,
                            array(
                                array("type" => "table", "name" => "foomanjirafe_report", "items" =>
                                    array(
                                        array("sql-column", "report_id", "int(10) unsigned NOT NULL auto_increment"),
                                        array("sql-column", "created_at", "timestamp NOT NULL default CURRENT_TIMESTAMP"),
                                        array("sql-column", "generated_by_jirafe_version", "varchar(128)"),
                                        array("sql-column", "report_data", "text"),
                                        array("key", "PRIMARY KEY", "report_id")
                                        )
                                    )
                                )
                        );
                if(!$returnComplete) {
                    break;
                }
                //nobreak intentionally;
            default:
                break;
        }
        return $instructions;

    }

    public function runDbSchemaUpgrade ($installer, $version)
    {
        foreach (Mage::helper('foomanjirafe/setup')->getDbSchema($version) as $instruction) {
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
                    $sql = "DROP TABLE IF EXISTS `{$installer->getTable($instruction['name'])}`;\n";
                    $sql .="CREATE TABLE `{$installer->getTable($instruction['name'])}` (".$tableDetails.") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
                    $installer->run($sql);
                    break;
                case 'sql-column':
                    $return = $installer->run("
                        ALTER TABLE `{$installer->getTable($instruction['table'])}` ADD COLUMN `{$instruction['name']}` {$instruction['params']}");
                    break;
            }

        }
    }

}

