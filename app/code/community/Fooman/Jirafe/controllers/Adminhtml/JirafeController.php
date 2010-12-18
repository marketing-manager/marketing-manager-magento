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

    protected function _construct() {
        $this->setUsedModuleName('Fooman_Jirafe');
    }

    public function manualAction()
    {

        Mage::getSingleton('adminhtml/session')
            ->addError(
                    Mage::helper('foomanjirafe')->__('You can configure Jirafe here System > Configuration > <a href="%s">Jirafe Analytics</a> to enter your details.',
                            Mage::helper('adminhtml')->getUrl('adminhtml/system_config/edit/section/foomanjirafe')
                            )
                    );

        $this->loadLayout();

        $this->_addContent(
                $this->getLayout()->createBlock('foomanjirafe/adminhtml_manual', 'jirafemanual')
        );

        $this->renderLayout();
    }
}