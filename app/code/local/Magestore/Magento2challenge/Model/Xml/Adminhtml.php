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
class Magestore_Magento2challenge_Model_Xml_Adminhtml extends Magestore_Magento2challenge_Model_Xml {
    private $_file;
    public function __construct() {
        parent::__construct();
        $this->_file = $this->_target.DS.'etc'.DS.'adminhtml.xml';
    }
    public function convert(){
        $adminhtml = $this->getXmlNotes($this->_file);
        if(isset($adminhtml->menu)){
            $config = $this->createXmlElement('menu');
            $this->createMenuFile($config, $adminhtml->menu);
            
            $content = '<?xml version="1.0"?>'."\n";
            $content .= '<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../../Backend/etc/menu.xsd">'."\n";
            $content .= $config->asNiceXml();
            $content .= '</config>';

            $this->_helper->writeContentToFile($this->_miniTarget.'/etc/adminhtml/menu.xml', $content);
        }
        if(isset($adminhtml->acl->resources)){
            $admin = $this->createXmlElement('resource', array('id'=>'Magento_Adminhtml::admin'));
            $this->createAclFile($admin, $adminhtml->acl->resources->admin->children);
            
            $resources = $this->createXmlElement('resources');
            $config = $this->createXmlElement('acl');
            $this->appendChild($resources, $admin);
            $this->appendChild($config, $resources);
            
            $content = '<?xml version="1.0"?>'."\n";
            $content .= '<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../../../../lib/internal/Magento/Framework/Acl/etc/acl.xsd">'."\n";
            $content .= $config->asNiceXml();
            $content .= '</config>';

            $this->_helper->writeContentToFile($this->_miniTarget.'/etc/acl.xml', $content);
        }
        
        if(file_exists($this->_file)){
            unlink($this->_file);
        }
        return;
    }
    public function createMenuFile($config, $menuNotes, $parentResource = null){
        $moduleNameSpace = $this->_nameSpace.'_'.$this->_moduleName;
        foreach($menuNotes->children() as $menu){
            $module = $menu->getAttribute('module');
            
            if($module == $this->_lowerModuleName){
                $module = $moduleNameSpace;
            }else{
                $module = uc_words('Magento_'.$module);
            }
            $attribute = array();
            if(!$parentResource){
                $attribute['id'] = $module.'::'.$menu->getName();              
            } else {
                $attribute['id'] = $parentResource.'_'.$menu->getName();                
            }
            $attribute['title'] = $menu->title;
            $attribute['module'] = $module;
            $attribute['sortOrder'] = $menu->sort_order;
            if(isset($menu->action)){
                $attribute['action'] = Mage::getModel('magento2challenge/config_config')->getRouterPath($menu->action, false);
            }
            if(!$parentResource){
                $attribute['resource'] = $module.'::'.$menu->getName();                
            } else {
                $attribute['parent'] = $parentResource;
                $attribute['resource'] = $parentResource.'_'.$menu->getName();                
            }
            if(isset($menu->depends)){
                $attribute['dependsOnModule'] = str_replace('Mage_', 'Magento_', $menu->depends->module);
            }
            $item = $this->createXmlElement('add', $attribute);
            $this->appendChild($config, $item);
            if(isset($menu->children)){
                $this->createMenuFile($config, $menu->children, $attribute['resource']);
            }            
        }
        return;
    }
    public function createAclFile($config, $aclNotes, $parentResource = null){
        foreach($aclNotes->children() as $note){
            if($note->getName() == 'system'){
                $configElement = $this->createXmlElement('resource', array('id'=>'Magento_Adminhtml::config'));
                foreach($note->children->config->children->children() as $noteConfig){
                    if($noteConfig->getAttribute('module') == $this->_lowerModuleName){
                        $noteId = $this->_nameSpace.'_'.$this->_moduleName.'::config_'.$noteConfig->getName();
                    }else{
                        $noteId = 'Magento_'.uc_words($noteConfig->getAttribute('module')).'::config_'.$noteConfig->getName();
                    }
                    $noteElement = $this->createXmlElement('resource', array('id'=>$noteId, 'title'=>$noteConfig->title));
                    $this->appendChild($configElement, $noteElement);
                }
                $storeSettingElement = $this->createXmlElement('resource', array('id'=>'Magento_Adminhtml::stores_settings'));
                $this->appendChild($storeSettingElement, $configElement);
                $storeElement = $this->createXmlElement('resource', array('id'=>'Magento_Adminhtml::stores'));
                $this->appendChild($storeElement, $storeSettingElement);
                $this->appendChild($config, $storeElement);
            }else{
                $noteAttributes = array();
                if($parentResource){
                    $noteAttributes['id'] = $parentResource.'_'.$note->getName();
                }else{
                    if($note->getAttribute('module') == $this->_lowerModuleName){
                        $noteAttributes['id'] = $this->_nameSpace.'_'.$this->_moduleName.'::'.$note->getName();
                    }else{
                        $noteAttributes['id'] = 'Magento_'.uc_words($note->getAttribute('module')).'::'.$note->getName();
                    }
                }
                if($note->title){
                    $noteAttributes['title'] = $note->title;
                }
                if($note->sort_order){
                    $noteAttributes['sortOrder'] = $note->sort_order;
                }
                $noteElement = $this->createXmlElement('resource', $noteAttributes);
                if($note->children){
                    $this->createAclFile($noteElement, $note->children, $noteAttributes['id']);
                }
                $this->appendChild($config, $noteElement);
            }
        }
        return;
    }
}
