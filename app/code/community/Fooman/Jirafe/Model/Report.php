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

    const XML_PATH_JIRAFE_EMAIL_TEMPLATE   = 'foomanjirafe/report_email_template';
    const XML_PATH_EMAIL_IDENTITY   = 'foomanjirafe/report_email_identity';

    protected $_helper = '';


    protected function _construct ()
    {
        $this->_init('foomanjirafe/report');
        $this->_helper = Mage::helper('foomanjirafe');
    }

    /*
    * website_id - The Jirafe website ID for this particular Magento instance.
    * email - Array of information about the people to email the daily reports to. email + first_name + last_name
    * stores - Array of information about the stores that have been set up in this Magento instance. store_id + description + base_url
    * time_zone - The time zone that this store is set up in
    * currency - The currency that this store uses
     *
    * dt - The date that this data comes from
    * num_orders - Number of orders made in the previous day
    * revenue - Amount of sales made in the previous day
    * num_visitors - Amount of visitors in the previous day
    * num_abandoned_carts - Number of visitors who abandoned carts in the previous day
    * revenue_abandoned_carts - Revenue left in abandoned carts in the previous day

     */

    public function cron($cronSchedule, $justInstalledEmail = false)
    {
        $this->_helper->debug('starting jirafe report cron');
	
        // Get the GMT timestamp for this cron
        $gmtTs = Mage::getSingleton('core/date')->gmtTimestamp();

        //loop over stores to create reports
        $storeCollection = Mage::getModel('core/store')->getCollection();
        foreach ($storeCollection as $store) {
            if ($store->getIsActive()) {
				// Get the store ID
				$storeId = $store->getId();
				// Get the store time zone
				$tz1 = $store->getConfig('general/locale/timezone');
		        $tz2 = Mage::getStoreConfig('general/locale/timezone', $storeId);
				$time = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));


		// Set the current store to the given store
//        Mage::app()->setCurrentStore($store);
	
        $currentStoreTimestamp = Mage::getSingleton('core/date')->timestamp($gmtTs);
        $offset = $currentStoreTimestamp - $gmtTs;
        $format = 'Y-m-d H:i:s';

        $currentTimeAtStore = date($format, $currentStoreTimestamp);
        $yesterdayAtStore = date("Y-m-d", strtotime("yesterday", $currentStoreTimestamp));
        $yesterdayAtStoreFormatted = date($format, strtotime($yesterdayAtStore));

        $this->_helper->debug('store '.$store->getName().' $offset '. $offset);
        $this->_helper->debug('store '.$store->getName().' $currentTimeAtStore '. $currentTimeAtStore);
        $this->_helper->debug('store '.$store->getName().' $yesterdayAtStore '. $yesterdayAtStore);

        if(Mage::getResourceModel('foomanjirafe/report')->checkIfReportExists($storeId, $yesterdayAtStoreFormatted)) {
            return false;
        }

        //db data is stored in GMT so run reports with adjusted times
        $from = date($format, strtotime($yesterdayAtStore) - $offset);
        $to = date($format, strtotime("tomorrow",strtotime($yesterdayAtStore)) - $offset);





				
				// Create new report
                $storeData = $this->_gatherReportData($storeId, $store, $gmtTs);
				if (!empty($storeData)) {
					// Save report
					$this->_saveReport($storeId, $storeData);
					// Send out emails
					$this->_emailReport($storeId, $storeData);
					// Send Jirafe heartbeat
					Mage::getModel('foomanjirafe/api')->sendHeartbeat($storeData);
				}
            }
        }
        $this->_helper->debug('finished jirafe report cron');
    }

	private function _saveReport($storeId, $storeData)
	{
		//save report for transmission
		$this->_helper->debug($storeData);
		Mage::getModel('foomanjirafe/report')
			->setStoreId($storeId)
			->setStoreReportDate($storeData[$storeId]['date'])
			->setReportData(json_encode($storeData))
			->save();
	}
	
	private function _emailReport($storeId, $storeData)
	{
		try {
//			$t = $justInstalledEmail ? self::XML_PATH_EMAIL_TEMPLATE_INITIAL : self::XML_PATH_EMAIL_TEMPLATE;
			$template = Mage::getStoreConfig(XML_PATH_JIRAFE_EMAIL_TEMPLATE, $storeId);
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
						$storeData,
						$storeId
					);
			}
			
			$translate->setTranslateInline(true);
			if ($justInstalledEmail && !Mage::helper('foomanjirafe')->getStoreConfig('sent_initial_email')) {
				if(!$notified) {
					Mage::helper('foomanjirafe')->setStoreConfig('sent_initial_email',true);
					Mage::getModel('adminnotification/inbox')
						->setSeverity(Mage_AdminNotification_Model_Inbox::SEVERITY_NOTICE)
						->setTitle('Jirafe plugin for Magento installed successfully.')
						->setDateAdded(gmdate('Y-m-d H:i:s'))
						->setUrl(Mage::helper('adminhtml')->getUrl('adminhtml/jirafe/manual', array('_nosid'=>true,'_nosecret'=>true)))
						->setDescription('We have just installed Jirafe and you have received your first report via email. You can change the settings in the admin area under System > Configuration > General > Jirafe Analytics.')
						->save();
					$notified = true;
				}
			}
		} catch (Exception $e) {
			Mage::logException($e);
			if(!$notified) {
				Mage::getModel('adminnotification/inbox')
					->setSeverity(Mage_AdminNotification_Model_Inbox::SEVERITY_NOTICE)
					->setTitle('Jirafe plugin for Magento installed - needs configuration')
					->setDateAdded(gmdate('Y-m-d H:i:s'))
					->setUrl(Mage::getModel('core/config_data')->load('web/secure/base_url','path')->getValue().Mage::helper('adminhtml')->getUrl('adminhtml/jirafe/manual', array('_nosid'=>true,'_nosecret'=>true)))
					->setDescription('We have just installed Jirafe and but were unable to send you your first report via email. Please change the settings in the admin area under System > Configuration > General > Jirafe Analytics.')
					->save();
				$notified = true;
			}
		}
	}
	
    private function _gatherReportData($storeId, $store, $gmtTs, $intialEmail=false)
    {
        $this->_helper->debug('store '.$store->getName().' Report $from '. $from);
        $this->_helper->debug('store '.$store->getName().' Report $to '. $to);

        $currency =  Mage::getStoreConfig('currency/options/base', $storeId);
        $currencyLocale = Mage::getModel('directory/currency')->load($currency);
	
        $reportData = array(
            'store_id' => $storeId,
            'date'=> $yesterdayAtStoreFormatted,
            'jirafe_version' => Mage::getResourceModel('core/resource')->getDbVersion('foomanjirafe_setup'),
            'magento_version' => Mage::getVersion(),
            'admin_emails'=> $this->_helper->getStoreConfig('emails', $storeId),
            'description' => $store->getFrontendName().' ('.$store->getName().')',
            'time_zone'=> $store->getConfig('general/locale/timezone'),
            'currency' => $currency,
            'base_url' => $store->getConfig('web/unsecure/base_url'),
            'jirafe_settings_url'=> Mage::helper('adminhtml')->getUrl('adminhtml/system_config/edit/section/foomanjirafe', array('_nosecret'=>true,'_nosid'=>true)),
            'customer_num'=> Mage::getResourceModel('foomanjirafe/report')->getStoreUniqueCustomers($storeId, $from, $to),
            'visitor_num' => Mage::getResourceModel('foomanjirafe/report')->getStoreVisitors($storeId, $from, $to, $intialEmail)
        );
	
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

		// Calculate conversion rate and revenue per visitor
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

		// Make formatted values for reports
        $reportData['date_formatted'] = Mage::helper('core')->formatDate($yesterdayAtStore, 'medium');
		$reportData['visitor_conversion_rate_formatted'] = sprintf("%01.2f", $reportData['visitor_conversion_rate'] * 100);

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
            $reportData[$item . '_formatted'] = $currencyLocale->formatTxt($reportData[$item]);
        }

        return $reportData;
    }
}