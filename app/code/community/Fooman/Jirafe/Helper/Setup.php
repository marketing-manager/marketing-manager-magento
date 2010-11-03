<?php

class Fooman_Jirafe_Helper_Setup extends Mage_Core_Helper_Abstract
{

    public function getDbSchema ($version)
    {
        $instructions = array();
        switch ($version) {
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
                //nobreak intentionally;
            default:
                break;
        }
        return $instructions;

    }
}

