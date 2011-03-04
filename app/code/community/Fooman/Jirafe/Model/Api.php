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
    const JIRAFE_API_SERVER = 'https://api.jirafe.com/';
    const JIRAFE_API_URL = 'http://jirafe-local.com/app_dev.php/';
    const JIRAFE_UI_URL = 'http://jirafe-local.com/app_dev.php/v1/';
    const JIRAFE_API_VERSION = 'v1';

    const JIRAFE_API_HB = '/heartbeat';
    const JIRAFE_API_APPLICATIONS = '/applications';
    const JIRAFE_API_RESOURCES =  '/resources';
    const JIRAFE_API_SITES = '/sites';
    const JIRAFE_API_USERS = '/users';

    public function sendData ($entryPoint, $data, $adminToken = false,
            $method = Zend_Http_Client::POST, $httpAuth = array())
    {

        //set up connection
        $conn = new Zend_Http_Client(self::JIRAFE_API_URL);
        $conn->setConfig(array(
            'timeout' => 30,
            'keepalive' => true
        ));
        $conn->setUri(self::JIRAFE_API_URL . self::JIRAFE_API_VERSION . $entryPoint);
        if($adminToken) {
            $conn->setParameterGet('token', $adminToken);
        }
        if(!empty($httpAuth)) {
            $conn->setAuth($httpAuth['username'], $httpAuth['password']);
        }

        try {
            //connect and send data to Jirafe
            //$result = $conn->setRawData(json_encode($data), 'application/json')->request($method);
            Mage::helper('foomanjirafe')->debug($data);
            //loop over data items and add them as post/put parameters if requested
            if (is_array($data) && ($method == Zend_Http_Client::POST || $method == Zend_Http_Client::PUT)) {
                foreach ($data as $parameter => $value) {
                    $conn->setParameterPost($parameter, $value);
                }
            }
            $conn->request($method);            
            Mage::helper('foomanjirafe')->debug($conn->getLastRequest());
            Mage::helper('foomanjirafe')->debug($conn->getLastResponse());
            $result = $this->_errorChecking($conn->getLastResponse());
            Mage::helper('foomanjirafe')->debug($result);
        } catch (Exception $e) {
            Mage::logException($e);
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
        Mage::helper('foomanjirafe')->debug($response->getBody());
        //dev mode returns debug toolbar remove it from output here
        $reponseArray = explode('}', $response->getBody(), 2);
        $reponseBody = $reponseArray[0].'}';
        if(strpos($reponseBody,'You are not allowed to access this file.') !== false) {            
            throw new Exception('Server Response: You are not allowed to access this file.');
        }
        //check for returned errors
        $result = json_decode($reponseBody,true);
        $errors = array();
        if(isset($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $errors[] = $error;
            }
            throw new Exception(implode(',',$errors));
        }
        return $result;
    }

}
