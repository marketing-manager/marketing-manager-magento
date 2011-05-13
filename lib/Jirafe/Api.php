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

class Jirafe_Api
{
    // PRODUCTION environment
    const JIRAFE_API_SERVER = 'https://api.jirafe.com';
    const JIRAFE_API_BASE = '';
    const JIRAFE_PIWIK_BASE_URL = 'data.jirafe.com';
    
    // DEV environment
//    const JIRAFE_API_SERVER = 'http://api.jirafe.local';
//    const JIRAFE_API_BASE = 'app_dev.php';
//    const JIRAFE_PIWIK_BASE_URL = 'piwik.local';
    
    // TEST environment
//    const JIRAFE_API_SERVER = 'https://test-api.jirafe.com';
//    const JIRAFE_API_BASE = '';
//    const JIRAFE_PIWIK_BASE_URL = 'test-data.jirafe.com';
    


	// Don't put http/s on this URL because it is added later

    const JIRAFE_API_VERSION = 'v1';
    const JIRAFE_API_LOGS = '/logs';
    const JIRAFE_API_APPLICATIONS = '/applications';
    const JIRAFE_API_RESOURCES =  '/resources';
    const JIRAFE_API_SITES = '/sites';
    const JIRAFE_API_USERS = '/users';
    const JIRAFE_DOC_URL = 'http://jirafe.com/doc';

    private $_application = null;
    private $_log = null;
    private $_resource = null;
    private $_site = null;
    private $_user = null;
    
    public function getApplication()
    {
        if($this->_application == null) {
            $this->_application = new Jirafe_Api_Application;
        }
        return $this->_application;
    }
    
    public function getLog()
    {
        if($this->_log == null) {
            $this->_log = new Jirafe_Api_Log;
        }
        return $this->_log;
    }
    
    public function getResource()
    {
        if($this->_resource == null) {
            $this->_resource = new Jirafe_Api_Resource;
        }
        return $this->_resource;
    }
    
    public function getSite()
    {
        if($this->_site == null) {
            $this->_site = new Jirafe_Api_Site;
        }
        return $this->_site;
    }
    
    public function getUser()
    {
        if($this->_user == null) {
            $this->_user = new Jirafe_Api_User;
        }
        return $this->_user;
    }    
    
    /**
     * Returns the URL of the API
     *
     * @param  string $entryPoint An optional entry point
     *
     * @return string
     */
    public function getApiUrl($entryPoint = null)
    {
        // Server
        $url = rtrim(self::JIRAFE_API_SERVER, '/');

        // Base
        if ((boolean) self::JIRAFE_API_BASE) {
            $url.= '/' . ltrim(self::JIRAFE_API_BASE, '/');
        }

        // Version
        if ((boolean) self::JIRAFE_API_VERSION) {
            $url.= '/' . ltrim(self::JIRAFE_API_VERSION, '/');
        }

        // Entry Point
        if (null !== $entryPoint) {
            $url.= '/' . ltrim($entryPoint, '/');
        }

        return $url;
    }

    /**
     * Returns the URL of the asset corresponding to the specified filename
     *
     * @param  string $filename The filename of the asset
     *
     * @return string
     */
    public function getAssetUrl($filename)
    {
        return rtrim(self::JIRAFE_API_SERVER, '/') . '/' . ltrim($filename, '/');
    }
    
    public function getPiwikBaseUrl()
    {
        return rtrim(self::JIRAFE_PIWIK_BASE_URL, '/') . '/' ;
    }

    public function getDocUrl($platform, $type='user', $version=null)
    {
        if ($version) {
            return rtrim(self::JIRAFE_DOC_URL, '/') . "/{$platform}/{$version}/" . ltrim($type, '/');
        } else {
            return rtrim(self::JIRAFE_DOC_URL, '/') . "/{$platform}/" . ltrim($type, '/');
        }
    }

    public function sendData ($entryPoint, $data, $adminToken = false,
            $method = Zend_Http_Client::POST, $httpAuth = array())
    {

        if(!in_array('ssl', stream_get_transports())) {
            throw new Exception("The Jirafe plugin requires outgoing connectivity via ssl to communicate securely with our server to function. Please enable ssl support for php or get your webhosting company to do it for you.");
        }
        
        //set up connection
        $conn = new Zend_Http_Client($this->getApiUrl($entryPoint));
        $conn->setConfig(array(
            'timeout' => 30,
            'keepalive' => true
        ));
        if($adminToken) {
            $conn->setParameterGet('token', $adminToken);
        }
        //$conn->setParameterGet('XDEBUG_SESSION_START', 'switzer');

        if(!empty($httpAuth)) {
            $conn->setAuth($httpAuth['username'], $httpAuth['password']);
        }

        try {
            //connect and send data to Jirafe
            //loop over data items and add them as post/put parameters if requested
            if (is_array($data) && ($method == Zend_Http_Client::POST || $method == Zend_Http_Client::PUT)) {
                foreach ($data as $parameter => $value) {
                    $conn->setParameterPost($parameter, $value);
                }
            }
            $conn->request($method);
            $result = $this->_errorChecking($conn->getLastResponse());
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
            return false;
        }
        return $result;
    }

    private function _errorChecking ($response)
    {
        //check server response
        if ($response->isError()) {
            throw new Exception($response->getStatus() .' '. $response->getMessage());
        }
        //TODO: dev mode returns debug toolbar remove it from output here
        $reponseBody = preg_replace('/<!-- START of Symfony2 Web Debug Toolbar -->(.*?)<!-- END of Symfony2 Web Debug Toolbar -->/', '', $response->getBody());
        if(strpos($reponseBody,'You are not allowed to access this file.') !== false) {
            throw new Exception('Server Response: You are not allowed to access this file.');
        }
        if(strpos($reponseBody,'Call Stack:') !== false) {
            throw new Exception('Server Response contains errors');
        }
        if(strpos($reponseBody,'Fatal error:') !== false) {
            throw new Exception('Server Response contains errors');
        }

        //check for returned errors
        $result = json_decode($reponseBody,true);
        if(isset($result['errors']) && !empty($result['errors'])) {
            $errors = array();
            foreach ($result['errors'] as $error) {
                $errors[] = $error;
            }
            throw new Exception(implode(',',$errors));
        }
        return $result;
    }

}
