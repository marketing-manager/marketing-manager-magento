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
    }

    public function cron($cronSchedule, $first = false)
    {
        $this->_helper->debug('starting jirafe report cron');
	
		// Re-initialise the config array so that we can get the proper values for config settings like timezone
		if(version_compare(Mage::getVersion(), '1.3.4.0') > 0) {
			Mage::app()->getConfig()->reinit();
		}
		
        // Get the GMT timestamp for this cron
        $gmtTs = Mage::getSingleton('core/date')->gmtTimestamp();
		
		// Set flag to make sure we were successful in reporting and logging all stores
		$success = true;

        //loop over stores to create reports
        $storeCollection = Mage::getModel('core/store')->getCollection();
        foreach ($storeCollection as $store) {
            if ($store->getIsActive()) {
				// Get the store ID
				$storeId = $store->getId();
				// Set the current store
				Mage::app()->setCurrentStore($store);
				// Get the timespan (array ('from', 'to')) for this report
				$timespan = $this->_getReportTimespan($store, $gmtTs);
				// Check if report exists
				$exists = Mage::getResourceModel('foomanjirafe/report')->getReport($storeId, date('Y-m-d', $timespan['from']));
				if (!$exists) {
					try {
						// Create new report
						$data = $this->_compileReport($store, $timespan);
						// Save report
						$this->_saveReport($store, $timespan, $data);
						// Send out emails
						$data_formatted = $this->_getReportDataFormatted($data);
						$this->_emailReport($store, $timespan, $data + $data_formatted + array('first' => $first));
						// Send Jirafe heartbeat
						$this->_sendReport($store, $timespan, $data);

					} catch (Exception $e) {
						Mage::logException($e);
						$success = false;
					}
				}
			}
        }
		
		if ($first) {
			// Set a flag to know that the user has been notified
			Mage::helper('foomanjirafe')->setStoreConfig('sent_initial_email', true);
			// Notify user if it is just installed
			$this->_notifyAdminUser($success);
		}
		
        $this->_helper->debug('finished jirafe report cron');
    }

	private function _notifyAdminUser($success)
	{
		if ($success) {
			Mage::getModel('adminnotification/inbox')
				->setSeverity(Mage_AdminNotification_Model_Inbox::SEVERITY_NOTICE)
				->setTitle('Jirafe plugin for Magento installed successfully.')
				->setDateAdded(gmdate('Y-m-d H:i:s'))
				->setUrl(Mage::helper('adminhtml')->getUrl('adminhtml/jirafe/manual', array('_nosid'=>true,'_nosecret'=>true)))
				->setDescription('We have just installed Jirafe and you have received your first report via email. You can change the settings in the admin area under System > Configuration > General > Jirafe Analytics.')
				->save();
		} else {
			Mage::getModel('adminnotification/inbox')
				->setSeverity(Mage_AdminNotification_Model_Inbox::SEVERITY_NOTICE)
				->setTitle('Jirafe plugin for Magento installed - needs configuration')
				->setDateAdded(gmdate('Y-m-d H:i:s'))
				->setUrl(Mage::getModel('core/config_data')->load('web/secure/base_url','path')->getValue().Mage::helper('adminhtml')->getUrl('adminhtml/jirafe/manual', array('_nosid'=>true,'_nosecret'=>true)))
				->setDescription('We have just installed Jirafe and but were unable to send you your first report via email. Please change the settings in the admin area under System > Configuration > General > Jirafe Analytics.')
				->save();
		}
	}
	
    private function _compileReport($store, $timespan)
    {
		$reportData = array();
		
		// Get the day we are running the report
		$from = $timespan['from'];
		$to = $timespan['to'];
		$reportData['date'] = date('Y-m-d', $from);

		// Get store information
		$storeId = $store->getId();
		$reportData['store_id'] = $storeId;
		$reportData['store_name'] = $store->getFrontendName().' ('.$store->getName().')';
		$reportData['store_url'] = $store->getConfig('web/unsecure/base_url');
		
		// Tell debugger we are kicking off the report compilation
        $this->_helper->debug("Compiling report for store [{$storeId}] {$reportData['store_name']} on {$reportData['date']}");

		// Get the currency
        $reportData['currency'] =  Mage::getStoreConfig('currency/options/base', $storeId);
		
		// Get version information
		$reportData['jirafe_version'] = Mage::getResourceModel('core/resource')->getDbVersion('foomanjirafe_setup');
		$reportData['magento_version'] = Mage::getVersion();
		
		// Get the email addresses where the email will be sent
		$reportData['email_addresses'] = $this->_helper->getStoreConfig('emails', $storeId);
		
		// Get the URL to the Magento admin console, Jirafe settings
		$reportData['jirafe_settings_url'] = Mage::helper('adminhtml')->getUrl('adminhtml/system_config/edit/section/foomanjirafe', array('_nosecret'=>true,'_nosid'=>true));
		
		// Get the timezone for this store
		$reportData['time_zone'] = $store->getConfig('general/locale/timezone');
		
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
		$reportData['visitor_num'] = Mage::getResourceModel('foomanjirafe/report')->getStoreVisitors($storeId, $from, $to, $intialEmail);
        if ($reportData['visitor_num'] > 0) {
            $reportData['visitor_conversion_rate'] = $reportData['customer_num'] / $reportData['visitor_num'];
            $reportData['sales_per_visitor'] = $reportData['sales_grand_total'] / $reportData['visitor_num'];
        } else {
            $reportData['visitor_conversion_rate'] = 0;
            $reportData['sales_per_visitor'] = 0;
        }

		// Calculate revenue per customer
        if ($reportData['customer_num'] > 0) {
            $reportData['sales_per_customer'] = $reportData['sales_grand_total'] / $reportData['customer_num'];
        } else {
            $reportData['sales_per_customer'] = 0;
        }

        if ($reportData['order_num'] > 0) {
            $reportData['sales_per_order'] = $reportData['sales_grand_total'] / $reportData['order_num'];
        } else {
            $reportData['sales_per_order'] = 0;
        }
        return $reportData;
    }
	
	private function _saveReport($store, $timespan, $data)
	{
		//save report for transmission
//		$this->_helper->debug($storeData);
		Mage::getModel('foomanjirafe/report')
			->setStoreId($store->getId())
			->setStoreReportDate(date('Y-m-d', $timespan['from']))
			->setReportData(json_encode($data))
			->save();
	}
	
	private function _emailReport($store, $timespan, $data)
	{
		// Get the store ID
		$storeId = $store->getId();
		// Get the template
		$template = Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $storeId);
		// Get the list of emails to send this report
		$emails = explode(',', $this->_helper->getStoreConfig('emails', $storeId));
		// Translate email
		$translate = Mage::getSingleton('core/translate');
		/* @var $translate Mage_Core_Model_Translate */
		$translate->setTranslateInline(false);

		$emailTemplate = Mage::getModel('core/email_template');
		/* @var $emailTemplate Mage_Core_Model_Email_Template */
		foreach ($emails as $email) {
			$emailTemplate
				->setDesignConfig(array('area' => 'backend'))
				->sendTransactional(
					$template,
					Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId),
					trim($email),
					null,
					$data,
					$storeId
				);
		}
	}
	
	function _getReportTimespan($store, $gmtTs, $span = 'day')
	{
		$timespan = array();
		// Get the current timestamp (local time) for this store
        $ts = Mage::getSingleton('core/date')->timestamp($gmtTs);
		$timespan['from'] = strtotime('yesterday', $ts);
		$timespan['to'] = strtotime('+1 day', $timespan['from']);
		return $timespan;
	}
	
	function _sendReport($store, $timespan, $data)
	{
		Mage::getModel('foomanjirafe/api')->sendData($data);
	}
	
	function _getReportDataFormatted($data)
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
			'refunds_grand_total',
			'refunds_shipping',
			'refunds_subtotal',
			'refunds_taxes',
			'sales_grand_total',
			'sales_gross',
			'sales_net',
			'sales_per_customer',
			'sales_per_order',
			'sales_per_visitor',
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