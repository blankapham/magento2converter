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
 * @package     Magestore_Magento2challenge
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * Magento2challenge Resource Model
 * 
 * @category    Magestore
 * @package     Magestore_Magento2challenge
 * @author      Magestore Developer
 */
class Magestore_Magento2challenge_Model_Mysql4_Magento2challenge extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('magento2challenge/magento2challenge', 'id');
    }
    public function insertData($data){
        $write = $this->_getWriteAdapter();
        $write->beginTransaction();
        $write->insertMultiple($this->getTable('magento2challenge/magento2challenge'), $data);
        $write->commit();
        return;
    }
}