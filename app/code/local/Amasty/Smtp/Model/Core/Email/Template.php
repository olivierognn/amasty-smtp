<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package SMTP Email Settings
*/

class Amasty_Smtp_Model_Core_Email_Template extends Amasty_Smtp_Model_Core_Email_Template_Pure
{
    public function send($email, $name = null, array $variables = array())
    {
        $storeId = 0;

        if (isset($variables['store'])) {
            $storeId = $variables['store']->getStoreId();
        }

        if (!Mage::getStoreConfig('amsmtp/general/enable', $storeId))
        {
            return parent::send($email, $name, $variables);
        }

        $isQueueMail = Mage::helper('amsmtp')->isQueue();

        if (!$isQueueMail) {
            Mage::helper('amsmtp')->debug('Ready to send e-mail at amsmtp/core_email_template::send()');
        }

        if (!$this->isValidForSend()) {
            Mage::helper('amsmtp')->debug('E-mail not valid for send. Probably SMTP is disabled or template incorrect.');
            Mage::logException(new Exception('This letter cannot be sent.'));
            return false;
        }

        $emails = array_values((array)$email);
        $names = is_array($name) ? $name : (array)$name;
        $names = array_values($names);
        foreach ($emails as $key => $email) {
            if (!isset($names[$key])) {
                $names[$key] = substr($email, 0, strpos($email, '@'));
            }
        }

        $variables['email'] = reset($emails);
        $variables['name'] = reset($names);

        $mail = $this->getMail();

        if (!$mail->getMessageId()) {
            $mail->setMessageId(true);
        }

        $mail = $this->_prepareRecipients($mail);

        foreach ($emails as $key => $email) {
            $mail->addTo($email, '=?utf-8?B?' . base64_encode($names[$key]) . '?=');
        }

        $this->setUseAbsoluteLinks(true);
        $text = $this->getProcessedTemplate($variables, true);

        $transportFacade = Mage::getModel('amsmtp/transport');
        $transport = $transportFacade->getTransport(array(), $storeId);

        $plainText = Mage::helper('amsmtp')->html2text($text);
        if ($this->isPlain()) {
            $mail->setBodyText($text);
        } else {
            $mail->setBodyText($plainText);
            $mail->setBodyHTML($text);
        }

        list($senderEmail, $senderName) = Mage::helper('amsmtp')
            ->getCustomEmailParams($this->getSenderEmail(), $this->getSenderName());

        if (!$isQueueMail) {
            $mail->setSubject('=?utf-8?B?' . base64_encode($this->getProcessedTemplateSubject($variables)) . '?=');

            $mail->setFrom($senderEmail, $senderName);

            $logIds = array();
            foreach ($emails as $key => $email) {
                $logId = Mage::helper('amsmtp')->log(array(
                    'subject'           => $this->getProcessedTemplateSubject($variables),
                    'body'              => $text,
                    'recipient_name'    => $names[$key],
                    'recipient_email'   => $email,
                    'template_code'     => $this->getTemplateId(),
                    'status'            => Amasty_Smtp_Model_Log::STATUS_PENDING,
                ));
                $logIds[] = $logId;
            }

            try
            {
                if (!Mage::getStoreConfig('amsmtp/general/disable_delivery', $storeId))
                {
                    $mail->send($transport);
                } else
                {
                    Mage::helper('amsmtp')->debug('Actual delivery disabled under settings.');
                }

                foreach ($logIds as $logId)
                {
                    Mage::helper('amsmtp')->logStatusUpdate($logId, Amasty_Smtp_Model_Log::STATUS_SENT);
                }

                Mage::helper('amsmtp')->debug('E-mail sent successfully at amsmtp/core_email_template::send().');
                $this->_mail = null;
            } catch (Exception $e)
            {
                Mage::logException($e);
                foreach ($logIds as $logId)
                {
                    Mage::helper('amsmtp')->logStatusUpdate($logId, Amasty_Smtp_Model_Log::STATUS_FAILED);
                }
                Mage::helper('amsmtp')->debug('Error sending e-mail: ' . $e->getMessage());
                $this->_mail = null;
                return false;
            }
        } else {
            $subject = $this->getProcessedTemplateSubject($variables);

            $setReturnPath = Mage::getStoreConfig(self::XML_PATH_SENDING_SET_RETURN_PATH, $storeId);
            switch ($setReturnPath) {
                case 1:
                    $returnPathEmail = $senderEmail;
                    break;
                case 2:
                    $returnPathEmail = Mage::getStoreConfig(self::XML_PATH_SENDING_RETURN_PATH_EMAIL, $storeId);
                    break;
                default:
                    $returnPathEmail = null;
                    break;
            }

            if ($this->hasQueue() && $this->getQueue() instanceof Mage_Core_Model_Email_Queue) {
                /** @var $emailQueue Mage_Core_Model_Email_Queue */
                $emailQueue = $this->getQueue();
                $emailQueue->setMessageBody($text);
                $emailQueue->setMessageParameters(array(
                    'subject'           => $subject,
                    'return_path_email' => $returnPathEmail,
                    'is_plain'          => $this->isPlain(),
                    'from_email'        => $senderEmail,
                    'from_name'         => $senderName,
                    'reply_to'          => $this->getMail()->getReplyTo(),
                    'return_to'         => $this->getMail()->getReturnPath(),
                    'store_id'          => $storeId
                ))
                    ->addRecipients($emails, $names, Mage_Core_Model_Email_Queue::EMAIL_TYPE_TO)
                    ->addRecipients($this->_bccEmails, array(), Mage_Core_Model_Email_Queue::EMAIL_TYPE_BCC);
                $emailQueue->addMessageToQueue();

                return true;
            }

            ini_set('SMTP', Mage::getStoreConfig('system/smtp/host', $storeId));
            ini_set('smtp_port', Mage::getStoreConfig('system/smtp/port', $storeId));

            if ($returnPathEmail !== null) {
                $mailTransport = new Zend_Mail_Transport_Sendmail("-f".$returnPathEmail);
                Zend_Mail::setDefaultTransport($mailTransport);
            }

            $mail->setSubject('=?utf-8?B?' . base64_encode($subject) . '?=');
            $mail->setFrom($this->getSenderEmail(), $this->getSenderName());

            try {
                $mail->send($transport);
                $this->_mail = null;
            }
            catch (Exception $e) {
                $this->_mail = null;
                Mage::logException($e);
                return false;
            }
        }

        return true;
    }

    protected function _prepareRecipients($mail)
    {
        $headers = $mail->getHeaders();
        if (isset($headers['Bcc'])) {
            $bcc = $headers['Bcc'];
        }
        if (isset($headers['Cc'])) {
            $cc = $headers['Cc'];
        }
        $mail->clearRecipients();

        if (isset($bcc)) {
            foreach ($bcc as $email) {
                if (!is_bool($email)) {
                    $email = trim($email);
                    $mail->addBcc($email);
                }

            }
        }

        if (isset($cc)) {
            foreach ($cc as $email) {
                if (!is_bool($email)) {
                    $email = trim($email);
                    $mail->addCc($email);
                }
            }
        }

        return $mail;
    }
}
