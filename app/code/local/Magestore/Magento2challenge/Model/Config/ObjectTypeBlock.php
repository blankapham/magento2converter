<?php
/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Convertext
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * Convertext Model
 *
 * @category    Magestore
 * @package     Magestore_Convertext
 * @author      Magestore Developer
 */
class Magestore_Magento2challenge_Model_Config_ObjectTypeBlock extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('magento2challenge/config_objectTypeBlock');
    }

    //config for special converting old class to new class
    //can config chang module name, ex: adminhtml => backend
    public function getConfig(){
        return array(
            'Core\Block\Store' => 'Store\Block',
            'Adminhtml\Block\Urlrewrite' => 'UrlRewrite\Block',
            'Adminhtml\Block' => 'Backend\Block',
            'Adminhtml\Block\Backup' => 'Backup\Block\Backup',
        );
    }
}