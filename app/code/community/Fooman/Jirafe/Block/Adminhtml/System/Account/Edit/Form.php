<?php

class Fooman_Jirafe_Block_Adminhtml_System_Account_Edit_Form extends Mage_Adminhtml_Block_System_Account_Edit_Form
{
    protected function _prepareForm()
    {
        parent::_prepareForm();

        $form = $this->getForm();
        $fieldset = $form->addFieldset('jirafe', array('legend'=>Mage::helper('adminhtml')->__('Jirafe')));

        $fieldset->addField('jirafe_send_email_for_store', 'multiselect', array(
            'name'      => 'jirafe_send_email_for_store[]',
            'label'     => Mage::helper('foomanjirafe')->__('Send Daily Email for'),
            'title'     => Mage::helper('foomanjirafe')->__('Send Daily Email for'),
            'required'  => false,
            'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false),
            'value'    => explode(",",Mage::getSingleton('admin/session')->getUser()->getJirafeSendEmailForStore())
        ));
        $fieldset->addField('jirafe_emails', 'textarea', array(
            'name'      => 'jirafe_emails',
            'label'     => Mage::helper('foomanjirafe')->__('Send Emails To'),
            'title'     => Mage::helper('foomanjirafe')->__('Send Emails To'),
            'after_element_html'     => '<p class="nm"><small>'.Mage::helper('foomanjirafe')->__('One email address per Line').'</small></p>',
            'required'  => false,
            'value'    => str_replace(",","\n",Mage::getSingleton('admin/session')->getUser()->getJirafeEmails())
        ));
        return $this;
    }
}