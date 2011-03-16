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

class Fooman_Jirafe_Model_Report extends Mage_Core_Model_Abstract
{
    const XML_PATH_EMAIL_TEMPLATE = 'foomanjirafe/report_email_template';
    const XML_PATH_EMAIL_IDENTITY = 'foomanjirafe/report_email_identity';

    protected $_helper = '';

    protected function _construct ()
    {
        $this->_init('foomanjirafe/report');
        $this->_helper = Mage::helper('foomanjirafe');
        $this->_jirafe = Mage::getModel('foomanjirafe/jirafe');
    }

    public function cron ($cronSchedule, $first = false)
    {
        $this->_helper->debug('starting jirafe report cron');

        // Get the GMT timestamp for this cron - make sure we only get it once for all stores just in case
        $gmtTs = Mage::getSingleton('core/date')->gmtTimestamp();

        // Set flag to make sure we were successful in reporting and logging all stores
        $success = true;

        //check if we have a current applicationi id for jirafe
        $appId = $this->_jirafe->checkAppId();
        if(!$appId) {
            $this->_helper->debug("no Jirafe application ID present - abort cron.");
            $success = false;
        } else {
            //loop over stores to create reports
            $storeCollection = Mage::getModel('core/store')->getCollection();
            foreach ($storeCollection as $store) {
                // Only continue if the store is active
                if ($store->getIsActive()) {
                    // Get the store ID
                    $storeId = $store->getId();
                    // Only continue if this plugin is active for the store or if this is the first email
                    if ($this->_helper->getStoreConfig('isActive', $storeId) || $first) {
                        // Set the current store
                        Mage::app()->setCurrentStore($store);
                        // Check Jirafe Site Id
                        $siteId = $this->_jirafe->checkSiteId($appId, $store);
                        // Get the timespan (array ('from', 'to')) for this report
                        $timespan = $this->_getReportTimespan($store, $gmtTs);
                        // Only continue if the report does not already exist
                        $exists = Mage::getResourceModel('foomanjirafe/report')->getReport($storeId, $timespan['date']);
                        if (!$exists && $siteId) {
                            try {
                                // Create new report
                                $data = $this->_compileReport($store, $timespan, $siteId, $first);
                                // Save report
                                $this->_saveReport($store, $timespan, $data);
                                // Send out emails
                                $data_formatted = $this->_getReportDataFormatted($data);
                                // Are we sending a simple or a detailed report?
                                $detailReport = $this->_helper->getStoreConfig('reportType', $storeId) == 'detail';
                                // Email the report to users
                                $this->_emailReport($store, $timespan, $data + $data_formatted + array('first' => $first, 'detail_report' => $detailReport));
                                // Send Jirafe heartbeat
                                $this->_sendReport($store, $timespan, $data);
                                //save status message
                                $this->_helper->setStoreConfig('last_status_message',
                                        $this->_helper->__("Successfully sent report for %s for %s", $data['store_name'], $timespan['date'])
                                    );
                            } catch (Exception $e) {
                                Mage::logException($e);
                                $success = false;
                                //save status message
                                $this->_helper->setStoreConfig('last_status_message',
                                        $this->_helper->__("Encountered errors sending report for %s", $this->_helper->getStoreDescription($store))
                                    );
                            }
                        } else {
                            $this->_helper->debug("The report for store ID {$storeId} already exists for {$timespan['date']}.  Discontinuing processing for this report.");
                        }
                    }
                }
            }
        }

        if ($first) {
            // Set a flag to know that the user has been notified
            $this->_helper->setStoreConfig('sent_initial_email', true);
            // Notify user if it is just installed
            $this->_notifyAdminUser($success);
        }

        $this->_helper->debug('finished jirafe report cron');
    }

    private function _notifyAdminUser ($success)
    {
        if ($success) {
            Mage::getModel('adminnotification/inbox')
                    ->setSeverity(Mage_AdminNotification_Model_Inbox::SEVERITY_NOTICE)
                    ->setTitle('Jirafe plugin for Magento installed successfully.')
                    ->setDateAdded(gmdate('Y-m-d H:i:s'))
                    ->setUrl(Mage::helper('adminhtml')->getUrl('adminhtml/jirafe/manual', array('_nosid' => true, '_nosecret' => true)))
                    ->setDescription('We have just installed Jirafe and you have received your first report via email. You can change the settings in the admin area under System > Configuration > General > Jirafe Analytics.')
                    ->save();
        } else {
            Mage::getModel('adminnotification/inbox')
                    ->setSeverity(Mage_AdminNotification_Model_Inbox::SEVERITY_NOTICE)
                    ->setTitle('Jirafe plugin for Magento installed - needs configuration')
                    ->setDateAdded(gmdate('Y-m-d H:i:s'))
                    ->setUrl(Mage::getModel('core/config_data')->load('web/secure/base_url', 'path')->getValue() . Mage::helper('adminhtml')->getUrl('adminhtml/jirafe/manual', array('_nosid' => true, '_nosecret' => true)))
                    ->setDescription('We have just installed Jirafe and but were unable to send you your first report via email. Please change the settings in the admin area under System > Configuration > General > Jirafe Analytics.')
                    ->save();
        }
    }

    private function _compileReport ($store, $timespan, $siteId, $first = false)
    {
        $reportData = array();

        // Get the day we are running the report
        $from = $timespan['from'];
        $to = $timespan['to'];
        $reportData['date'] = $timespan['date'];

        // Get store information
        $storeId = $store->getId();
        $reportData['store_id'] = $storeId;
        $reportData['site_id'] = $siteId;
        $reportData['store_name'] = $this->_helper->getStoreDescription($store);
        $reportData['store_url'] = $store->getConfig('web/unsecure/base_url');

        // Tell debugger we are kicking off the report compilation
        $this->_helper->debug("Compiling report for store [{$storeId}] {$reportData['store_name']} on {$reportData['date']}");

        // Get the currency
        $reportData['currency'] = $store->getConfig('currency/options/base');

        // Get version information
        $reportData['jirafe_version'] = (string) Mage::getConfig()->getModuleConfig('Fooman_Jirafe')->version;
        $reportData['magento_version'] = Mage::getVersion();

        // Get the email addresses where the email will be sent
        $reportData['admin_emails'] = $this->_helper->collectJirafeEmails($storeId);

        // Get the URL to the Magento admin console, Jirafe settings
        $reportData['jirafe_settings_url'] = Mage::helper('adminhtml')->getUrl('adminhtml/system_config/edit/section/foomanjirafe', array('_nosecret' => true, '_nosid' => true));

        // Get the timezone for this store
        $reportData['timezone'] = $store->getConfig('general/locale/timezone');

        // Get customer data
        $reportData['customer_num'] = Mage::getResourceModel('foomanjirafe/report')->getStoreUniqueCustomers($storeId, $from, $to);

        // Get refund data
        $reportData += Mage::getResourceModel('foomanjirafe/report')->getStoreRefunds($storeId, $from, $to);

        // Get revenue data
        $reportData += Mage::getResourceModel('foomanjirafe/report')->getStoreRevenues($storeId, $from, $to);
        $reportData['sales_gross'] = $reportData['sales_grand_total'] - $reportData['sales_discounts'];  // Discounts is a negative number so gross will be >= grand total
        $reportData['sales_net'] = $reportData['sales_subtotal'] - $reportData['refunds_subtotal'];

        // Get abandoned cart data
        $reportData += Mage::getResourceModel('foomanjirafe/report')->getStoreAbandonedCarts($storeId, $from, $to);

        // Get order data
        $reportData += Mage::getResourceModel('foomanjirafe/report')->getStoreOrders($storeId, $from, $to);

        // Get visitor and conversion data
        $reportData['visitor_num'] = Mage::getResourceModel('foomanjirafe/report')->getStoreVisitors($storeId, $from, $to, $first);
        if ($reportData['visitor_num'] > 0) {
            $reportData['visitor_conversion_rate'] = $reportData['customer_num'] / $reportData['visitor_num'];
            $reportData['sales_grand_total_per_visitor'] = $reportData['sales_grand_total'] / $reportData['visitor_num'];
            $reportData['sales_net_per_visitor'] = $reportData['sales_net'] / $reportData['visitor_num'];
        } else {
            $reportData['visitor_conversion_rate'] = 0;
            $reportData['sales_grand_total_per_visitor'] = 0;
            $reportData['sales_net_per_visitor'] = 0;
        }

        // Calculate revenue per customer
        if ($reportData['customer_num'] > 0) {
            $reportData['sales_grand_total_per_customer'] = $reportData['sales_grand_total'] / $reportData['customer_num'];
            $reportData['sales_net_per_customer'] = $reportData['sales_net'] / $reportData['customer_num'];
        } else {
            $reportData['sales_grand_total_per_customer'] = 0;
            $reportData['sales_net_per_customer'] = 0;
        }

        if ($reportData['order_num'] > 0) {
            $reportData['sales_grand_total_per_order'] = $reportData['sales_grand_total'] / $reportData['order_num'];
            $reportData['sales_net_per_order'] = $reportData['sales_net'] / $reportData['order_num'];
        } else {
            $reportData['sales_grand_total_per_order'] = 0;
            $reportData['sales_net_per_order'] = 0;
        }
        return $reportData;
    }

    private function _saveReport ($store, $timespan, $data)
    {
        //save report for transmission
//		$this->_helper->debug($storeData);
        Mage::getModel('foomanjirafe/report')
                ->setStoreId($store->getId())
                ->setStoreReportDate($timespan['date'])
                ->setGeneratedByJirafeVersion($data['jirafe_version'])
                ->setReportData(json_encode($data))
                ->save();
    }

    private function _emailReport ($store, $timespan, $data)
    {
        // Get the store ID
        $storeId = $store->getId();
        // Get the template
        $template = Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $storeId);
        // Get the list of emails to send this report
        $emails = $this->_helper->collectJirafeEmails($storeId, false);
        // Translate email
        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);

        $emailTemplate = Mage::getModel('core/email_template');
        /* @var $emailTemplate Mage_Core_Model_Email_Template */
        foreach ($emails as $emailAddress=>$reportType) {
            $data['detail_report'] = $reportType;
            $emailTemplate
                    ->setDesignConfig(array('area' => 'backend'))
                    ->sendTransactional(
                            $template,
                            Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId),
                            trim($emailAddress),
                            null,
                            $data,
                            $storeId
            );
        }
    }

    function _getReportTimespan ($store, $gmtTs, $span='day')
    {
        // Get the current timestamp (local time) for this store
        $ts = Mage::getSingleton('core/date')->timestamp($gmtTs);
        $offset = $ts - $gmtTs;
        $fromUnix = strtotime('yesterday', $ts) - $offset;
        $toUnix = strtotime('+1 day', $fromUnix);
        $from = date('Y-m-d H:i:s', $fromUnix);
        $to = date('Y-m-d H:i:s', $toUnix);
        $date = date('Y-m-d', $fromUnix + $offset);

        return array('from' => $from, 'to' => $to, 'date' => $date);
    }

    function _sendReport ($store, $timespan, $data)
    {
        return Mage::getModel('foomanjirafe/jirafe')->sendLogUpdate($data);
    }

    function _getReportDataFormatted ($data)
    {
        $fdata = array();

        // Get the currency locale so that we can format currencies correctly
        $currencyLocale = Mage::getModel('directory/currency')->load($data['currency']);

        // Make formatted values for reports
        $fdata['date_formatted'] = Mage::helper('core')->formatDate($data['date'], 'medium');
        $fdata['visitor_conversion_rate_formatted'] = sprintf("%01.2f", $data['visitor_conversion_rate'] * 100);

        $currencyFormatItems = array(
            'abandoned_cart_grand_total',
            'order_max',
            'order_min',
            'refunds_discounts',
            'refunds_grand_total',
            'refunds_shipping',
            'refunds_subtotal',
            'refunds_taxes',
            'sales_discounts',
            'sales_grand_total',
            'sales_gross',
            'sales_net',
            'sales_grand_total_per_customer',
            'sales_grand_total_per_order',
            'sales_grand_total_per_visitor',
            'sales_net_per_customer',
            'sales_net_per_order',
            'sales_net_per_visitor',
            'sales_shipping',
            'sales_subtotal',
            'sales_taxes'
        );

        foreach ($currencyFormatItems as $item) {
            $fdata[$item . '_formatted'] = $currencyLocale->formatTxt($data[$item]);
        }

        return $fdata;
    }

}
