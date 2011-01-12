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

    const XML_PATH_EMAIL_TEMPLATE   = 'foomanjirafe/report_email_template';
    const XML_PATH_EMAIL_TEMPLATE_INITIAL   = 'foomanjirafe/report_email_template_initial';
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

    public function cron ($cronSchedule, $justInstalledEmail = false)
    {
        $this->_helper->debug('starting jirafe report cron');
	
        if($justInstalledEmail) {
            Mage::app()->getConfig()->reinit();
        }
        //global data
        $currentGmtTimestamp = Mage::getSingleton('core/date')->gmtTimestamp();

        //loop over stores to create reports
        $storeCollection = Mage::getModel('core/store')->getCollection();
        $notified = false;
        foreach ($storeCollection as $store) {
            if ($this->_helper->getStoreConfig('isActive', $store->getId()) || $justInstalledEmail) {
                $storeData = array();
                $storeData[$store->getId()] = $this->_gatherReportData($store, $currentGmtTimestamp, $justInstalledEmail);

                //new report created
                if ($storeData[$store->getId()]){
                    //save report for transmission
                    $jirafeVersion = Mage::getResourceModel('core/resource')->getDbVersion('foomanjirafe_setup');
                    $this->_helper->debug($storeData);
                    Mage::getModel('foomanjirafe/report')
                        ->setStoreId($store->getId())
                        ->setGeneratedByJirafeVersion($jirafeVersion)
                        ->setStoreReportDate($storeData[$store->getId()]['dt'])
                        ->setReportData(json_encode($storeData))
                        ->save();
                    //send email
                    try {
                        $this->sendJirafeEmail($storeData[$store->getId()], $store->getId(), $justInstalledEmail);
                        //notify Jirafe
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
                                ->setUrl(Mage::helper('adminhtml')->getUrl('adminhtml/jirafe/manual', array('_nosid'=>true,'_nosecret'=>true)))
                                ->setDescription('We have just installed Jirafe and but were unable to send you your first report via email. Please change the settings in the admin area under System > Configuration > General > Jirafe Analytics.')
                                ->save();
                            $notified = true;
                        }
                    }
                    //notify Jirafe
                    $this->sendJirafeHeartbeat($storeData[$store->getId()], $store->getId());
                }
            }
        }
        $this->_helper->debug('finished jirafe report cron');
    }

    public function sendJirafeEmail($storeData, $storeId, $justInstalledEmail)
    {
        $t = $justInstalledEmail ? self::XML_PATH_EMAIL_TEMPLATE_INITIAL : self::XML_PATH_EMAIL_TEMPLATE;
        $template = Mage::getStoreConfig($t, $storeId);
        $emails = explode(',', $this->_helper->getStoreConfig('emails', $storeId));
        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);

        $emailTemplate = Mage::getModel('core/email_template');
        /* @var $emailTemplate Mage_Core_Model_Email_Template */
        foreach ($emails as $email){
            $emailTemplate->setDesignConfig(array('area' => 'backend'))
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
    }

    public function sendJirafeHeartbeat($storeData, $storeId)
    {
        $data = $storeData;
        return Mage::getModel('foomanjirafe/api')->sendHeartbeat($data);
    }

    private function _gatherReportData($store, $currentGmtTimestamp, $intialEmail= false)
    {

        Mage::app()->setCurrentStore($store);        
        $currentStoreTimestamp = Mage::getSingleton('core/date')->timestamp($currentGmtTimestamp);
        $offset = $currentStoreTimestamp - $currentGmtTimestamp;
        $format = 'Y-m-d H:i:s';

        $currentTimeAtStore = date($format, $currentStoreTimestamp);
        $yesterdayAtStore = date("Y-m-d", strtotime("yesterday", $currentStoreTimestamp));
        $yesterdayAtStoreFormatted = date($format, strtotime($yesterdayAtStore));

        $this->_helper->debug('store '.$store->getName().' $offset '. $offset);
        $this->_helper->debug('store '.$store->getName().' $currentTimeAtStore '. $currentTimeAtStore);
        $this->_helper->debug('store '.$store->getName().' $yesterdayAtStore '. $yesterdayAtStore);

        if($this->_checkIfReportExists ($store->getId(), $yesterdayAtStoreFormatted)) {
            return false;
        }

        //db data is stored in GMT so run reports with adjusted times
        $from = date($format, strtotime($yesterdayAtStore) - $offset);
        $to = date($format, strtotime("tomorrow",strtotime($yesterdayAtStore)) - $offset);

        $this->_helper->debug('store '.$store->getName().' Report $from '. $from);
        $this->_helper->debug('store '.$store->getName().' Report $to '. $to);

        $currency =  Mage::getStoreConfig('currency/options/base', $store->getId());
        $currencyLocale = Mage::getModel('directory/currency')->load($currency);
        $abandonedCarts = $this->_gatherStoreAbandonedCarts($store->getId(), $from, $to);
        $maxMinOrders = $this->_gatherMaxMinOrders($store->getId(), $from, $to);
	
        $reportData = array(
            'store_id' => $store->getId(),
            'jirafe_version' => Mage::getResourceModel('core/resource')->getDbVersion('foomanjirafe_setup'),
            'magento_version' => Mage::getVersion(),
            'admin_emails'=> $this->_helper->getStoreConfig('emails', $store->getId()),
            'description' => $store->getFrontendName().' ('.$store->getName().')',
            'time_zone'=> $store->getConfig('general/locale/timezone'),
            'dt'=> $yesterdayAtStoreFormatted,
            'dt_nice'=> Mage::helper('core')->formatDate($yesterdayAtStore, 'medium'),
            'base_url' => $store->getConfig('web/unsecure/base_url'),
            'jirafe_settings_url'=> Mage::helper('adminhtml')->getUrl('adminhtml/system_config/edit/section/foomanjirafe', array('_nosecret'=>true,'_nosid'=>true)),
            'num_orders' => $this->_gatherStoreOrders($store->getId(), $from, $to),
            'num_customers'=>$this->_gatherStoreUniqueCustomers($store->getId(), $from, $to),
            'revenue' => $this->_gatherStoreRevenue($store->getId(), $from, $to),
            'num_visitors' => $this->_gatherStoreVisitors($store->getId(), $from, $to, $intialEmail),
            'num_abandoned_carts'=> $abandonedCarts['num'],
            'revenue_abandoned_carts'=> $abandonedCarts['revenue'],
            'currency' => $currency,
            'max_order'=> $maxMinOrders['max_order'],
            'min_order'=> $maxMinOrders['min_order']
        );

        if ($reportData['num_visitors'] > 0) {
            $reportData['conversion_rate'] = sprintf("%01.2f", ($reportData['num_customers'] / $reportData['num_visitors'])*100);
            $reportData['revenue_per_visitor'] = sprintf("%01.2f", $reportData['revenue'] / $reportData['num_visitors']);
        } else {
            $reportData['conversion_rate'] = '0.00';
            $reportData['revenue_per_visitor'] = '0.00';
        }

        if ($reportData['num_customers'] > 0) {
            $reportData['revenue_per_customer'] = sprintf("%01.2f", $reportData['revenue'] / $reportData['num_customers']);
        } else {
            $reportData['revenue_per_customer'] = '0.00';
        }

        if ($reportData['num_orders'] > 0) {
            $reportData['revenue_per_order'] = sprintf("%01.2f", $reportData['revenue'] / $reportData['num_orders']);
        } else {
            $reportData['revenue_per_order'] = '0.00';
        }

        $formatTheseValues = array('revenue','max_order','min_order','revenue_abandoned_carts','revenue_per_visitor','revenue_per_customer','revenue_per_order');
        foreach ($formatTheseValues as $value){
            $reportData[$value.'_nice'] = $currencyLocale->formatTxt($reportData[$value]);
        }

        return $reportData;
    }

    private function _gatherStoreRevenue ($storeId, $from, $to)
    {
        return Mage::getResourceModel('foomanjirafe/report')->getStoreRevenue($storeId, $from, $to);
    }

    private function _gatherStoreOrders ($storeId, $from, $to)
    {
        return Mage::getResourceModel('foomanjirafe/report')->getStoreOrders($storeId, $from, $to);
    }

    private function _gatherStoreUniqueCustomers ($storeId, $from, $to)
    {
        return Mage::getResourceModel('foomanjirafe/report')->getStoreUniqueCustomers($storeId, $from, $to);
    }

    private function _gatherMaxMinOrders ($storeId, $from, $to)
    {
        return Mage::getResourceModel('foomanjirafe/report')->getMaxMinOrders($storeId, $from, $to);
    }

    private function _gatherStoreVisitors ($storeId, $from, $to, $intialEmail = false)
    {
        return Mage::getResourceModel('foomanjirafe/report')->getStoreVisitors($storeId, $from, $to, $intialEmail);
    }

    /**
     *
     * retrieve number and value of carts that are active and haven't been converted to orders
     *
     * @param int $storeId
     * @param date $from
     * @param date $to
     * @return array('num','revenue')
     */
    private function _gatherStoreAbandonedCarts ($storeId, $from, $to)
    {
        return Mage::getResourceModel('foomanjirafe/report')->getStoreAbandonedCarts($storeId, $from, $to);
    }

    private function _checkIfReportExists ($storeId, $day)
    {
        return Mage::getResourceModel('foomanjirafe/report')->checkIfReportExists($storeId, $day);
    }

}