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

class Fooman_Jirafe_Adminhtml_JirafeController extends Mage_Adminhtml_Controller_Action {

    protected $_publicActions = array('manual');

    protected function _construct() {
        $this->setUsedModuleName('Fooman_Jirafe');
    }

    public function manualAction()
    {
        $this->loadLayout();

        $this->_addContent(
                $this->getLayout()->createBlock('foomanjirafe/adminhtml_manual', 'jirafemanual')
        );

        $this->renderLayout();
    }

    public function reportAction ()
    {
        if (Mage::helper('foomanjirafe')->isConfigured()) {
            Mage::getModel('foomanjirafe/report')->cron(null, false);
        } else {
            Mage::getModel('foomanjirafe/report')->cron(null, true);
        }
        $this->_redirect('adminhtml/dashboard');
    }

    public function syncAction()
    {
        $jirafe = Mage::getModel('foomanjirafe/jirafe');
        $appId = $jirafe->checkAppId();
        $jirafe->syncUsersAndStores();
        $this->_redirect('adminhtml/dashboard');
    }
}