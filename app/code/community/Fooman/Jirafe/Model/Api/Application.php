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

class Fooman_Jirafe_Model_Api_Application extends Fooman_Jirafe_Model_Api
{
    
    /**
     *
     * @param $name - The name of Application
     * @param $url - The URL of the application, which will be the admin URL for the Magento instance
     * @param $callbackUrl - The URL that will be redirected to after an account login
     */
    public function create ($name, $url, $callbackUrl=null)
    {
        $data = array();
        $data['name'] = $name;
        $data['url'] = $url;
        $data['callback_url'] = $callbackUrl;
        return $this->sendData(self::JIRAFE_API_APPLICATIONS, $data, Zend_Http_Client::POST);
    }

    /**
     * Get application information for application ID
     *
     * @param $appId
     */
    public function getInfo ($appId)
    {
        return $this->sendData(self::JIRAFE_API_APPLICATIONS.'/'.$appId, false, Zend_Http_Client::GET);
    }

    /**
     * Get linked sites for application ID
     *
     * @param $appId
     */
    public function getLinkedSites ($appId)
    {
        return $this->sendData(self::JIRAFE_API_APPLICATIONS.'/'.$appId.self::JIRAFE_API_SITES, false, Zend_Http_Client::GET);
    }

    /**
     * Update application information
     *
     * @param $appId

     */
    public function update ($appId, $url, $callbackUrl=null)
    {
        $data = array();
        $data['url'] = $url;
        $data['callback_url'] = $callbackUrl;
        return $this->sendData(self::JIRAFE_API_APPLICATIONS.'/'.$appId, $data, Zend_Http_Client::PUT);
    }

    /**
     * Delete application (Caution! This operation can't be reverted and will delete all dependent data)
     *
     * @param $appId
     */
    public function delete ($appId)
    {
        return $this->sendData(self::JIRAFE_API_APPLICATIONS.'/'.$appId, false, Zend_Http_Client::DELETE);
    }
}