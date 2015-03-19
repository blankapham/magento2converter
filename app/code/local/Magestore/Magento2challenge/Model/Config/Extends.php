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
class Magestore_Magento2challenge_Model_Config_Extends extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('magento2challenge/config_extends');
    }

    //getExtendsClass
    public function getExtendsClass($strName){
        $configs = $this->getModelExtendsConfig();
        if(isset($configs[$strName]) && $configs[$strName]){
            return $configs[$strName];
        }else{
            return '\Magento\Framework\ObjectManager\ObjectManager'; //default object extends
        }
    }


    private function getModelExtendsConfig(){
        return array(
            'no_extends' => '',
            'Mage_Core_Model_Abstract' => '\Magento\Framework\Model\AbstractModel',
            'Mage_Core_Model_Mysql4_Abstract' => '\Magento\Framework\Model\Resource\Db\AbstractDb',
            'Mage_Core_Model_Mysql4_Collection_Abstract' => '\Magento\Framework\Model\Resource\Db\Collection\AbstractCollection',
            '' => '',
        );
    }
}