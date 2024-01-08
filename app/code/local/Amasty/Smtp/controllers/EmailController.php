<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package SMTP Email Settings
*/
class Amasty_Smtp_EmailController extends Mage_Adminhtml_Controller_Action
{
    public function _isAllowed()
    {
        return true;
    }
}
