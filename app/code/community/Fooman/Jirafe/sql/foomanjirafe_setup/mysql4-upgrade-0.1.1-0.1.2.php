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
Mage::log('Running Fooman Jirafe DB upgrade 0.1.2');
// Once complete, reinit config files
if(version_compare(Mage::getVersion(), '1.3.4.0') > 0) {
	Mage::app()->getConfig()->reinit();
}
// Run cron for the first time
Mage::getModel('foomanjirafe/report')->cron(null, true);
