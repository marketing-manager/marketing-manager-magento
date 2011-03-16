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

class Fooman_Jirafe_Model_Api_Log extends Fooman_Jirafe_Model_Api
{

    /**
     * Update application information
     *
     * @param $appId
     * @param $adminToken
     * @param $data
     */
    public function sendLog ($adminToken, $data)
    {
        return $this->sendData(self::JIRAFE_API_LOGS, $data, $adminToken, Zend_Http_Client::POST);
    }

       
}