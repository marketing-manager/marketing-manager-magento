<?php

class Fooman_Jirafe_Model_Api
{
    const JIRAFE_HB_URL =  'https://jirafe.com/hb';
    const JIRAFE_API_URL = 'https://api.jirafe.com/';
    const JIRAFE_API_VERSION = 'v1';
    const JIRAFE_API_ACCOUNT = '/account';
    const JIRAFE_API_APPLICATION_REGISTER = '/application/register';

    public function createAccount ($data = array())
    {
        #Required parameters in bold, optional parameters in italics.

        # *username* - The name of the user account. Must be unique across accounts.
        # *email* - The email of the user. Must be a valid email address, and must be unique across accounts.
        # *password* - The password of the user. Must be 6 characters long.
        # *password_confirm* - The password confirmation of the user. Must be exactly the same as the 'password' field.
        # first_name - The first name of the user. Used to address the user when sending emails.
        # last_name - The last name of the user. Used to address the user when sending emails.
        # mobile_phone - The mobile phone number of the user. Used to send SMS alerts to the user.

        $data['username']='testfooman';
        $data['password']='test123';
        $data['password_confirm']='test123';
        Mage::log('createAccount');

        return $this->sendData(self::JIRAFE_API_ACCOUNT, $data);
    }

    public function sendHeartbeat ($data, $method = Zend_Http_Client::POST)
    {

        //set up connection
        $conn = new Zend_Http_Client(self::JIRAFE_HB_URL);
        $conn->setConfig(array(
            'timeout' => 30,
            'keepalive' => true
        ));

        try {

            Mage::log($data);
            if (is_array($data) && $method == Zend_Http_Client::POST) {
                foreach($data as $parameter=>$value) {
                    $conn->setParameterPost($parameter,$value);
                }
            }
            $result = $conn->request($method);
            Mage::log($result);
            Mage::log($conn->getLastRequest());

        } catch (Exception $e) {
            Mage::logException($e);
        }

    }

    public function sendData ($entryPoint, $data, $method = Zend_Http_Client::POST)
    {

        //set up connection
        $conn = new Zend_Http_Client(self::JIRAFE_API_URL);
        $conn->setConfig(array(
            'timeout' => 30,
            'keepalive' => true
        ));
        $conn->setUri(self::JIRAFE_API_URL.self::JIRAFE_API_VERSION.$entryPoint);

        try {
            //connect and send data to Jirafe
            //$result = $conn->setRawData(json_encode($data), 'application/json')->request($method);
            Mage::log($data);
            if (is_array($data) && $method == Zend_Http_Client::POST) {
                foreach($data as $parameter=>$value) {
                    $conn->setParameterPost($parameter,$value);
                }
            }
            $result = $conn->request($method);
            Mage::log($result);
            Mage::log($conn->getLastRequest());
            //if($this->_isDebug()) { $this->sendToFirebug($result);}
        } catch (Exception $e) {
            return '<?xml version="1.0" encoding="UTF-8" ?>
                    <response><status>FAILED</status><errormessage>' . $e->getMessage() . '</errormessage></response>';
        }
        $this->_errorChecking($result);
        return $result->getBody();
    }

    private function _errorChecking($result) {

    }
}