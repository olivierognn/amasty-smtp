<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package SMTP Email Settings
*/

if (Mage::helper('core')->isModuleEnabled('Amasty_Perm')) {
    $autoloader = Varien_Autoload::instance();
    $autoloader->autoload('Amasty_Smtp_Model_Core_Email_Template_Perm');
} elseif (Mage::helper('core')->isModuleEnabled('Amasty_Customerattr')) {
    $autoloader = Varien_Autoload::instance();
    $autoloader->autoload('Amasty_Smtp_Model_Core_Email_Template_Customerattr');
} else {
    class Amasty_Smtp_Model_Core_Email_Template_Pure extends Mage_Core_Model_Email_Template {}
}
