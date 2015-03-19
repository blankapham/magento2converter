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
 * Magento2challenge Helper
 * 
 * @category    Magestore
 * @package     Magestore_Magento2challenge
 * @author      Magestore Developer
 */
class Magestore_Magento2challenge_Model_Base
{
    protected $_helper;
    protected $_target;
    protected $_moduleName;
    protected $_lowerModuleName;
    protected $_nameSpace;
    
    public function __construct() {
        $this->_helper = Mage::helper('magento2challenge');
        $this->_target = $this->_helper->getModuleDir('target');
        
        $moduleData = $this->_helper->getModuleData();
        $this->_moduleName = $moduleData['module_name'];
        $this->_lowerModuleName = $moduleData['lower_module_name'];
        $this->_nameSpace = $moduleData['name_space'];
    }
}
