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

class Fooman_Jirafe_Model_Api_Resource extends Fooman_Jirafe_Model_Api
{

  /*
    * users - A collection (an array) of JSON data corresponding to the users we want to synchronize. Accepted JSON keys are:
          o token - Jirafe authentication token for the given user. If provided, the user informations will be updated. If not, the user will be created.
          o username - Desired username
          o email - Your email
          o first_name - Your first name
          o last_name - Your last name
          o mobile_phone - Your mobile phone number
    * sites - A collection (an array) of JSON data corresponding to the sites we want to synchronize. Accepted JSON keys are:
          o id - Jirafe site id. If provided, the site informations will be updated. If not, the site will be created.
          o description - The description of the site (Magento store)
          o url - The URL of the site
          o timezone - The timezone of the site
          o currency - The currency of the site
    */

    public function sync ($appId, $adminToken, $userArray = array(), $siteArray = array())
    {
        if(empty($appId) || empty($adminToken)) {
            throw new Exception('$appId and $adminToken can\'t be empty');
        }
        $data = array();
        $data['users'] = $userArray;
        $data['sites'] = $siteArray;

        return $this->sendData(self::JIRAFE_API_APPLICATIONS.'/'.$appId .self::JIRAFE_API_RESOURCES, $data, $adminToken, Zend_Http_Client::POST);
    }

}