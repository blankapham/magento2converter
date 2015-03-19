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
class Magestore_Magento2challenge_Model_Xml extends Magestore_Magento2challenge_Model_Base
{
    protected $_miniTarget;
    protected $_origin;
    protected $_root;
    
    public function __construct() {
        parent::__construct();
        $this->_miniTarget = $this->_helper->getModuleDir('mini_target');
        $this->_origin = $this->_helper->getModuleDir('origin');
        $this->_root = $this->_helper->getModuleDir();
    }
    public function convert(){
        /**
         * Convert config.xml
         */
        if (file_exists($this->_target.DS.'etc'.DS.'config.xml')) {
            $this->getModel('config')->convert();
        }
       
        /**
         * Convert adminhtml.xml
         */
        if (file_exists($this->_target.DS.'etc'.DS.'adminhtml.xml')) {
            $this->getModel('adminhtml')->convert();
        }
        /**
         * Convert system.xml
         */
        if (file_exists($this->_target.DS.'etc'.DS.'adminhtml'.DS.'system.xml')) {
            $this->getModel('system')->convert();
        }
        /**
         * Convert layout.xml
         */
        $this->getModel('layout')->convert();
    }
    public function getModel($path, $arg = array()){
        return Mage::getModel('magento2challenge/xml_'.$path, $arg);
    }
    /**
     * Create a new xml element with name, attribute and value
     * 
     * @param string $elementName
     * @param array $elementAttr
     * @param string $elementValue
     * @return \Varien_Simplexml_Element
     */
    public function createXmlElement($elementName, $elementAttr = array(), $elementValue = '') {
        $element = new Varien_Simplexml_Element("<$elementName>$elementValue</$elementName>");
        foreach ($elementAttr as $key => $value) {
            $element->addAttribute($key, $value);
        }
        return $element;
    }

    /**
     * Get all xml notes of a xml file
     * 
     * @param string $file - path to the xml file
     * @return XML element
     */
    public function getXmlNotes($file) {
        $file = str_replace('/', DS, $file);
        $merge = new Mage_Core_Model_Config_Base();
        $merge->loadFile($file);
        return $merge->getNode();
    }
    
    /**
     * Append an element as a child of a parent element
     * Fix for core function. Should use $source->hasChildren() instead of $source->children()
     * 
     * @param xml_object $parent
     * @param xml_object $source
     * @return 
     */
    public function appendChild($parent, $source) {
        if ($source->hasChildren()) {
            if (version_compare(phpversion(), '5.2.4', '<') === true) {
                $name = $source->children()->getName();
            } else {
                $name = $source->getName();
            }
            $child = $parent->addChild($name);
        } else {
            $child = $parent->addChild($source->getName(), $parent->xmlentities($source));
        }
        $child->setParent($parent);

        $attributes = $source->attributes();
        foreach ($attributes as $key => $value) {
            $child->addAttribute($key, $parent->xmlentities($value));
        }

        foreach ($source->children() as $sourceChild) {
            $this->appendChild($child, $sourceChild);
        }
        return ;
    }  

}
