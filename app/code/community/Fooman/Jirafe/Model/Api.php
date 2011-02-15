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
    const JIRAFE_HB_URL =  'https://api.jirafe.com/v1/heartbeat';
    const JIRAFE_API_URL = 'https://api.jirafe.com/';
    const JIRAFE_API_VERSION = 'v1';
    const JIRAFE_API_ACCOUNT = '/account';
    const JIRAFE_API_APPLICATION_REGISTER = '/application/register';

    public function sendData ($entryPoint, $data, $method = Zend_Http_Client::POST)
    {
        //set up connection
        $conn = new Zend_Http_Client(self::JIRAFE_API_URL);
        $conn->setConfig(array(
            'timeout' => 30,
            'keepalive' => true
        ));
        $conn->setUri(self::JIRAFE_API_URL . self::JIRAFE_API_VERSION . $entryPoint);

        try {
            //connect and send data to Jirafe
            //$result = $conn->setRawData(json_encode($data), 'application/json')->request($method);
            //Mage::log($data);
            if (is_array($data) && $method == Zend_Http_Client::POST) {
                foreach ($data as $parameter => $value) {
                    $conn->setParameterPost($parameter, $value);
                }
            }
            $result = $conn->request($method);
            $this->_errorChecking($result);
            //Mage::log($result);
            //Mage::log($conn->getLastRequest());
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return $result->getBody();
    }

    private function _errorChecking ($result)
    {

    }

    public function sendHeartbeat ($data, $method = Zend_Http_Client::POST)
    {
        //url, admin email, daily visitors, daily sales, currency, time zone,
        $hbData = array();
        $hbData['url'] = $data['base_url'];
        $hbData['visitors'] = $data['num_visitors'];
        $hbData['sales'] = $data['revenue'];
        $hbData['currency'] = $data['currency'];
        $hbData['time_zone'] = $data['time_zone'];

        //set up connection
        $conn = new Zend_Http_Client(self::JIRAFE_HB_URL);
        $conn->setConfig(array(
            'timeout' => 30,
            'keepalive' => true
        ));

        try {
            if (is_array($hbData) && $method == Zend_Http_Client::POST) {
                foreach($hbData as $parameter=>$value) {
                    $conn->setParameterPost($parameter,$value);
                }
            }
            $result = $conn->request($method);

        } catch (Exception $e) {
            Mage::logException($e);
        }

    }

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
        return $this->sendData(self::JIRAFE_API_ACCOUNT, $data);
    }

}
