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

class Fooman_Jirafe_Model_JirafeTracker extends Fooman_Jirafe_Model_PiwikTracker
{
    protected function sendRequest($url)
    {
        $client = new Zend_Http_Client($url);
        $response = $client->request();

        //check server response
        if ($client->getLastResponse()->isError()) {
            throw new Exception($response->getStatus() .' '. $response->getMessage());
        }
        return $response;
    }
}


