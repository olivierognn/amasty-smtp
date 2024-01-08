<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package SMTP Email Settings
*/
class Amasty_Smtp_Model_System_Config_Source_Providers
{
    const OTHER_KEY = 'other';

    public function toOptionArray()
    {
        $providersArr = array(
            'aol'       => 'AOL Mail',
            'gmail'     => 'Gmail',
            'outlook'   => 'Outlook',
            'gmx'       => 'GMX',
            'yahoo'     => 'Yahoo!',
            'zoho'      => 'Zoho',
            'mailcom'   => 'Mail.com',
            'office365' => 'Office365',
            'o2'        => 'O2 Mail',
            'orange'    => 'Orange',
            'hotmail'   => 'Hotmail',
            'comcast'   => 'Comcast'
        );
        asort($providersArr);
        $resultArr = array();

        foreach($providersArr as $key => $val){
            $resultArr[] = array(
                'value' => $key,
                'label' => Mage::helper('amsmtp')->__($val),
            );
        }
        array_unshift($resultArr, array(
            'value' => self::OTHER_KEY,
            'label' => Mage::helper('amsmtp')->__('- Other -'),
        ));

        return $resultArr;
    }
}