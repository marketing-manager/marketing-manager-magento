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

class Fooman_Jirafe_Model_Api
{
    const JIRAFE_API_SERVER = 'api.jirafe.com';
//    const JIRAFE_API_SERVER = 'api.jirafe.local';

    const JIRAFE_API_BASE = '';
//    const JIRAFE_API_BASE = 'app_dev.php';

    const JIRAFE_PIWIK_BASE_URL = 'data.jirafe.com';
//    const JIRAFE_PIWIK_BASE_URL = 'piwik.local';

    const JIRAFE_API_VERSION = 'v1';

    const JIRAFE_API_LOGS = '/logs';
    const JIRAFE_API_APPLICATIONS = '/applications';
    const JIRAFE_API_RESOURCES =  '/resources';
    const JIRAFE_API_SITES = '/sites';
    const JIRAFE_API_USERS = '/users';

    public function getApiUrl ($includeBase = true, $includeVersion = true, $secure = false)
    {
        // Protocol
        $url = $secure ? 'https://' : 'http://';

        // Server
        $url .= self::JIRAFE_API_SERVER;

        // Base
        if ($includeBase) {
            $base = self::JIRAFE_API_BASE;
            if (!empty($base)) {
                $url .= "/{$base}";
            }
        }

        // Version
        if ($includeVersion) {
            $version = self::JIRAFE_API_VERSION;
            if (!empty($version)) {
                $url .= "/{$version}";
            }
        }

        return $url;
    }

    public function sendData ($entryPoint, $data, $adminToken = false,
            $method = Zend_Http_Client::POST, $httpAuth = array())
    {

        //set up connection
        $conn = new Zend_Http_Client($this->getApiUrl(true, true, true) . $entryPoint);
        $conn->setConfig(array(
            'timeout' => 30,
            'keepalive' => true
        ));
        if($adminToken) {
            $conn->setParameterGet('token', $adminToken);
        }
//		$conn->setParameterGet('XDEBUG_SESSION_START', 'switzer');

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
            Mage::logException($conn->getLastResponse());
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
