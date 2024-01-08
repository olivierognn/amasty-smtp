<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package SMTP Email Settings
*/
class Amasty_Smtp_Model_System_Config_Source_Auth
{
    public function toOptionArray()
    {
        return array(
            array('value' => Amasty_Smtp_Helper_Auth::AMASTY_AUTH_TYPE_NONE,
                  'label' => Mage::helper('amsmtp')->__('Authentication Not Required')),
            array('value' => Amasty_Smtp_Helper_Auth::AMASTY_AUTH_TYPE_LOGIN,
                  'label' => Mage::helper('amsmtp')->__('Login/Password')),
        );
    }
}