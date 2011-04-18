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

class Fooman_Jirafe_Block_Adminhtml_Permissions_User_Edit_Tab_Jirafe extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {


        $form = new Varien_Data_Form();
        $fieldset = $form->addFieldset('jirafe_fieldset', array('legend'=>Mage::helper('adminhtml')->__('Jirafe Analytics')));

        /*if ($model->getUserId()) {
            $fieldset->addField('user_id', 'hidden', array(
                'name' => 'user_id',
            ));
        } else {
            if (! $model->hasData('is_active')) {
                $model->setIsActive(1);
            }
        }*/
        $adminUser = Mage::registry('permissions_user');
        $yesNo = array();
        $yesNo[] = array('label' => Mage::helper('foomanjirafe')->__('Yes'), 'value' => 1);
        $yesNo[] = array('label' => Mage::helper('foomanjirafe')->__('No'), 'value' => 0);
        
        $fieldset->addField('jirafe_send_email', 'select', array(
            'name' => 'jirafe_send_email',
            'label' => Mage::helper('foomanjirafe')->__('Send Jirafe Emails'),
            'title' => Mage::helper('foomanjirafe')->__('Send Jirafe Emails'),
            'required' => false,
            'values' => $yesNo,
            'value' => $adminUser->getJirafeSendEmail()
        ));
        
        $reportTypes = array();
        $reportTypes[] = array('label' => Mage::helper('foomanjirafe')->__('Simple'), 'value' => 'simple');
        $reportTypes[] = array('label' => Mage::helper('foomanjirafe')->__('Detail'), 'value' => 'detail');

        $fieldset->addField('jirafe_email_report_type', 'select', array(
            'name' => 'jirafe_email_report_type',
            'label' => Mage::helper('foomanjirafe')->__('Email Report Type'),
            'title' => Mage::helper('foomanjirafe')->__('Email Report Type'),
            'after_element_html' => '<p class="nm"><small>' . Mage::helper('foomanjirafe')->__('Detail adds gross sales, refunds, discounts to the report') . '</small></p>',
            'required' => false,
            'values' => $reportTypes,
            'value' => $adminUser->getJirafeEmailReportType()
        ));

        $fieldset->addField('jirafe_email_suppress', 'select', array(
            'name' => 'jirafe_email_suppress',
            'label' => Mage::helper('foomanjirafe')->__('Suppress Emails With No Data'),
            'title' => Mage::helper('foomanjirafe')->__('Suppress Emails With No Data'),
            'after_element_html' => '<p class="nm"><small>' . Mage::helper('foomanjirafe')->__('Save virtual trees if you have lots of stores with no daily orders') . '</small></p>',
            'required' => false,
            'values' => $yesNo,
            'value' => $adminUser->getJirafeEmailSuppress()
        ));

        $fieldset->addField('jirafe_also_send_to', 'textarea', array(
            'name' => 'jirafe_also_send_to',
            'label' => Mage::helper('foomanjirafe')->__('Also Email Reports to'),
            'title' => Mage::helper('foomanjirafe')->__('Also Email Reports to'),
            'after_element_html' => '<p class="nm"><small>' . Mage::helper('foomanjirafe')->__('One email address per line') . '</small></p>',
            'required' => false,
            'value' => str_replace(",", "\n", $adminUser->getJirafeAlsoSendTo())
        ));

        $form->setValues($adminUser->getData());

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
