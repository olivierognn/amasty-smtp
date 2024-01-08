<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package SMTP Email Settings
*/
class Amasty_Smtp_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function debug($str)
    {
        if (!Mage::getStoreConfig('amsmtp/general/debug'))
        {
            return;
        }
        $debug = Mage::getModel('amsmtp/debug');
        $debug->setData(array(
            'cdate' => now(),
            'message' => $str,
        ))->save();
    }

    public function log($data)
    {
        if (!Mage::getStoreConfig('amsmtp/general/log'))
        {
            return;
        }
        $log = Mage::getModel('amsmtp/log');
        $data['cdate'] = now();
        $log->setData($data)->save();
        return $log->getId();
    }

    public function logStatusUpdate($logId, $status)
    {
        if (!Mage::getStoreConfig('amsmtp/general/log'))
        {
            return;
        }
        $log = Mage::getModel('amsmtp/log')->load($logId);
        if ($log->getId())
        {
            $log->setStatus($status)->save();
        }
        return true;
    }

    public function isQueue()
    {
        $isQueue = false;
        if (mageFindClassFile('Mage_Core_Model_Email_Queue') && method_exists('Mage_Core_Model_Email_Queue', 'addMessageToQueue')) {
            $backTrace = debug_backtrace();
            foreach ($backTrace as $step) {
                if (isset($step['object']) && ($step['object'] instanceof Mage_Sales_Model_Order) && ($step['function'] == 'queueNewOrderEmail')) {
                    $isQueue = true;
                    break;
                }
            }
            $backTrace = NULL;
        }

        return $isQueue;
    }

    public function html2text($text)
    {
        require_once Mage::getBaseDir('lib') . DS . 'Html2Text' . DS . 'Html2Text.php';

        $converter = new Html2Text($text);

        return trim($converter->get_text());
    }

    public function hasCustomEmailSender()
    {
        $hasEmailSender = (Mage::getStoreConfig('amsmtp/smtp/provider')
            == Amasty_Smtp_Model_System_Config_Source_Providers::OTHER_KEY
            && Mage::getStoreConfig('amsmtp/smtp/use_custom_email_sender'));

        return $hasEmailSender;
    }

    public function getCustomEmailParams($defaulEmailSender = '', $defaultNameSender = '')
    {
        $emailSender = $defaulEmailSender;
        $nameSender = $defaultNameSender;
        if ($this->hasCustomEmailSender()) {
            $emailSender = Mage::getStoreConfig('amsmtp/smtp/custom_sender_email');
            $nameSender = Mage::getStoreConfig('amsmtp/smtp/custom_sender_name');
        }

        return array($emailSender, $nameSender);
    }
}