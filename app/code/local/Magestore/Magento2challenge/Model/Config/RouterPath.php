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
 * @author      Magestore Developer Tit
 */
class Magestore_Magento2challenge_Model_Config_RouterPath extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('magento2challenge/config_routerPath');
    }

    public function getRouterMap(){
        return array(
            'adminhtml/sales_order/view' => 'sales/order/view',
            'sales/order/view'  => 'sales/order/view',
            //'adminhtml/system_config/edit' => 'admin/system_config/edit'
        );
    }
}