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

class Fooman_Jirafe_Model_Api_Site extends Fooman_Jirafe_Model_Api
{
   
    /**
     * Create site (Magento store)
     * @param $appId - The Application ID, created by "Applications" resource
     * @param $adminToken
     * @param $description - The description of the site (Magento store)
     * @param $url - The URL of the site
     * @param $timezone - The timezone of the site
     * @param $currency - The currency of the site
     */
    public function create ($appId, $adminToken, $description, $url, $timezone, $currency)
    {
        $data = array();
        $data['app_id'] = $appId;
        $data['description'] = $description;
        $data['url'] = $url;
        $data['timezone'] = $timezone;
        $data['currency'] = $currency;
        return $this->sendData(self::JIRAFE_API_SITES, $data, $adminToken, Zend_Http_Client::POST);
    }

    /**
     * Get site information for site ID
     *
     * @param $siteId
     * @param $adminToken
     */
    public function getInfo ($siteId, $adminToken)
    {
        return $this->sendData(self::JIRAFE_API_SITES.'/'.$siteId, false, $adminToken, Zend_Http_Client::GET);
    }

    /**
     * Get linked users for site ID
     *
     * @param $siteId
     * @param $adminToken
     */
    public function getLinkedUsers ($siteId, $adminToken)
    {
        return $this->sendData(self::JIRAFE_API_SITES.'/'.$siteId.self::JIRAFE_API_USERS, false, $adminToken, Zend_Http_Client::GET);
    }

    /**
     * Update site information
     *
     * @param $siteId
     * @param $adminToken
     * @param $timezone
     */
    public function update ($siteId, $adminToken, $timezone)
    {
        $data = array();
        $data['timezone'] = $timezone;
        return $this->sendData(self::JIRAFE_API_SITES.'/'.$siteId, $data, $adminToken, Zend_Http_Client::PUT);
    }

    /**
     * Delete site (Caution! This operation can't be reverted and will delete all dependent data) 
     *
     * @param $siteId
     * @param $adminToken
     */
    public function delete ($siteId, $adminToken)
    {
        return $this->sendData(self::JIRAFE_API_SITES.'/'.$siteId, false, $adminToken, Zend_Http_Client::DELETE);
    }
}