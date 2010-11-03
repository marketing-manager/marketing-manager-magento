<?php

require_once 'app'.DIRECTORY_SEPARATOR.'Mage.php';
Mage::app();

Mage::getModel('foomanjirafe/report')->cron();