<?php

require_once 'app'.DIRECTORY_SEPARATOR.'Mage.php';
Mage::app();
echo "start";
Mage::getModel('foomanjirafe/report')->cron();
echo "ended";