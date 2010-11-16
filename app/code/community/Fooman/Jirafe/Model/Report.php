<?php
class Fooman_Jirafe_Model_Report extends Mage_Core_Model_Abstract
{
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

    public function cron ()
    {
        $this->_helper->debug('starting jirafe report cron');
        $websiteId = $this->checkWebsiteId();
        if($websiteId) {
            //we have a valid website id
            //global data
            $currentGmtTimestamp = Mage::getSingleton('core/date')->gmtTimestamp();
            $data = array(
                'website_id' => $websiteId,
                'email' => $this->_helper->getStoreConfig('emails'),
                'currency'=> Mage::getStoreConfig('currency/options/base')
            );
            
            //loop over stores to create reports            
            $storeCollection = Mage::getModel('core/store')->getCollection();
            foreach ($storeCollection as $store) {
                if ($this->_helper->getStoreConfig('isActive', $store->getId())) {
                    $storeData = array();
                    $combinedData = $data;
                    $storeData[$store->getId()] = $this->_gatherReportData($store, $currentGmtTimestamp);

                    //new report created
                    if ($storeData[$store->getId()]){
                        //combine global and store wide data
                        $combinedData['stores'] = $storeData;

                        //save report for transmmission
                        $jirafeVersion = Mage::getResourceModel('core/resource')->getDbVersion('foomanjirafe_setup');
                        $this->_helper->debug($combinedData);
                        Mage::getModel('foomanjirafe/report')
                            ->setStoreId($store->getId())
                            ->setGeneratedByJirafeVersion($jirafeVersion)
                            ->setStoreReportDate($storeData[$store->getId()]['dt'])
                            ->setReportData(json_encode($combinedData))
                            ->save();
                    }
                }
            }
        }
        
        $this->_helper->debug('finished jirafe report cron');
    }

    public function checkWebsiteId ()
    {
        $websiteId = $this->_helper->getStoreConfig('websiteId');
        if ($websiteId) {
            return $websiteId;
        } else {
            $email = $this->_helper->getStoreConfig('emails');
            if($email) {
                return $this->_requestWebsiteId($email);
            }
        }
        //we don't have a valid website id
        return false;
    }

    public function _requestWebsiteId ($email)
    {
        //functionality to retrieve new website_id
        $id = md5($email);
        $this->_helper->debug("New Jirafe website_id ". $id);
        $this->_helper->setStoreConfig('websiteId', $id);
        return $this->_helper->getStoreConfig('websiteId');
    }

    private function _gatherReportData($store, $currentGmtTimestamp)
    {

        $storeTimeZone = $store->getConfig('general/locale/timezone');
        $offset = Mage::getSingleton('core/date')->calculateOffset($storeTimeZone);
        $this->_helper->debug('$offset '. $offset);
        $format = 'Y-m-d H:i:s';

        $currentTimeAtStore = Mage::helper('core')->formatDate(date($format,($currentGmtTimestamp+$offset)), 'short', true);
        $yesterdayAtStore = date("Y-m-d", strtotime("-1 day", strtotime($currentTimeAtStore)));        
        $yesterdayAtStoreFormatted = date($format, strtotime($yesterdayAtStore));

        $this->_helper->debug('$offset '. $offset);
        $this->_helper->debug('$currentTimeAtStore '. $currentTimeAtStore);

        if($this->_checkIfReportExists ($store->getId(), $yesterdayAtStoreFormatted)) {
            return false;
        }

        //db data is stored in GMT so run reports with adjusted times
        $from = date($format, strtotime($yesterdayAtStore) - $offset);
        $to = date($format, strtotime("+1 day",strtotime($yesterdayAtStore)) - $offset);
        $counts = Mage::getResourceModel('log/aggregation')->getCounts($from, $to, $store->getId());

        $this->_helper->debug('Report $from '. $from);
        $this->_helper->debug('Report $to '. $to);

        $abandonedCarts = $this->_gatherStoreAbandonedCarts($store->getId(), $from, $to);
        return array(
            'store_id' => $store->getId(),
            'description' => $store->getName(),
            'time_zone'=> $storeTimeZone,
            'dt'=> $yesterdayAtStoreFormatted,
            'base_url' => $store->getConfig('web/unsecure/base_url'),
            'num_orders' => $this->_gatherStoreOrders($store->getId(), $from, $to),
            'revenue' => $this->_gatherStoreRevenue($store->getId(), $from, $to),
            'num_visitors' => $counts['customers'] + $counts['visitors'],
            'num_abandoned_carts'=> $abandonedCarts['num'],
            'revenue_abandoned_carts'=> $abandonedCarts['revenue']
        );
    }

    private function _gatherStoreRevenue ($storeId, $from, $to)
    {
        return Mage::getResourceModel('foomanjirafe/report')->getStoreRevenue($storeId, $from, $to);
    }

    private function _gatherStoreOrders ($storeId, $from, $to)
    {
        return Mage::getResourceModel('foomanjirafe/report')->getStoreOrders($storeId, $from, $to);
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