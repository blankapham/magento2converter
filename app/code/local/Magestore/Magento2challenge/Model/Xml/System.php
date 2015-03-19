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
class Magestore_Magento2challenge_Model_Xml_System extends Magestore_Magento2challenge_Model_Xml {
    private $_file;
    public function __construct() {
        parent::__construct();
        $this->_file = $this->_target.DS.'etc'.DS.'adminhtml'.DS.'system.xml';
    }
    public function convert(){
        $systemNotes = $this->getXmlNotes($this->_file);
        $systemElement = $this->createXmlElement('system');
        foreach($systemNotes->children() as $sessions){
            if($sessions->getName() == 'tabs'){
                foreach($sessions->children() as $tab){
                    $attributeArray = array();
                    $attributeArray['id'] = $tab->getName();
                    if($tab->getAttribute('translate')) $attributeArray['translate'] = $tab->getAttribute('translate');
                    if($tab->sort_order) $attributeArray['sortOrder'] = (string) $tab->sort_order;
                    $tabElement = $this->createXmlElement('tab', $attributeArray);
                    $tabElement->addChild('label', $tab->label);
                    $this->appendChild($systemElement, $tabElement);
                }
            }elseif($sessions->getName() == 'sections'){
                foreach($sessions->children() as $session){
                    $attributeArray = array();
                    $attributeArray['id'] = $session->getName();
                    if($session->getAttribute('translate')) $attributeArray['translate'] = $session->getAttribute('translate');
                    if($session->sort_order) $attributeArray['sortOrder'] = $session->sort_order;
                    if($session->show_in_default) $attributeArray['showInDefault'] = $session->show_in_default;
                    if($session->show_in_website) $attributeArray['showInWebsite'] = $session->show_in_website;
                    if($session->show_in_store) $attributeArray['showInStore'] = $session->show_in_store;
                    $sessionElement = $this->createXmlElement('section', $attributeArray);
                    if($session->class) $sessionElement->addChild('class', $session->class);
                    if($session->label) $sessionElement->addChild('label', $session->label);
                    if($session->tab) $sessionElement->addChild('tab', $session->tab);
                    //if($session->frontend_type) $sessionElement->addChild('frontend_type', $session->frontend_type);
                    if($session->getAttribute('module') == $this->_moduleName){
                        $sessionElement->addChild('resource', $this->_nameSpace.'_'.$this->_moduleName.'::config_'.$session->getName());
                    }
                    if($session->groups){
                        foreach($session->groups->children() as $groups){
                            $attributeGroupArray = array();
                            $attributeGroupArray['id'] = $groups->getName();
                            if($groups->getAttribute('translate')) $attributeGroupArray['translate'] = $groups->getAttribute('translate');
                            if($groups->frontend_type) $attributeGroupArray['type'] = $groups->frontend_type;
                            if($groups->sort_order) $attributeGroupArray['sortOrder'] = $groups->sort_order;
                            if($groups->show_in_default) $attributeGroupArray['showInDefault'] = $groups->show_in_default;
                            if($groups->show_in_website) $attributeGroupArray['showInWebsite'] = $groups->show_in_website;
                            if($groups->show_in_store) $attributeGroupArray['showInStore'] = $groups->show_in_store;
                            $groupElement = $this->createXmlElement('group', $attributeGroupArray);
                            if($groups->label) $groupElement->addChild('label', $groups->label);
                            if($groups->fields){
                                foreach($groups->fields->children() as $fields){
                                    $attributeFieldArray = array();
                                    $attributeFieldArray['id'] = $fields->getName();
                                    if($fields->getAttribute('translate')) $attributeFieldArray['translate'] = $fields->getAttribute('translate');
                                    if($fields->frontend_type) $attributeFieldArray['type'] = $fields->frontend_type;
                                    if($fields->sort_order) $attributeFieldArray['sortOrder'] = $fields->sort_order;
                                    if($fields->show_in_default) $attributeFieldArray['showInDefault'] = $fields->show_in_default;
                                    if($fields->show_in_website) $attributeFieldArray['showInWebsite'] = $fields->show_in_website;
                                    if($fields->show_in_store) $attributeFieldArray['showInStore'] = $fields->show_in_store;
                                    $fieldElement = $this->createXmlElement('field', $attributeFieldArray);
                                    
                                    if($fields->label) $fieldElement->addChild('label', $fields->label);
                                    if($fields->comment) $fieldElement->addChild('comment', $fields->comment);
                                    if($fields->backend_model) $fieldElement->addChild('backend_model', $this->_helper->getClassNameFromConfigPath($fields->backend_model->xmlentities(), 'model'));
                                    if($fields->source_model) $fieldElement->addChild('source_model', $this->_helper->getClassNameFromConfigPath($fields->source_model->xmlentities(), 'model'));
                                    if($fields->depends){
                                        $dependElement = $this->createXmlElement('depends');
                                        foreach($fields->depends->children() as $depend){
                                            $dependFieldElement = $this->createXmlElement('field', array('id' => $depend->getName()), $depend->xmlentities());
                                            $this->appendChild($dependElement, $dependFieldElement);
                                        }
                                        $this->appendChild($fieldElement, $dependElement);                                        
                                    }
                                    $this->appendChild($groupElement, $fieldElement);                                     
                                }
                            }
                            $this->appendChild($sessionElement, $groupElement);
                        }
                    }
                    $this->appendChild($systemElement, $sessionElement);
                }
            }
        }
        $content = '<?xml version="1.0"?>'."\n";
        $content .= '<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../../Backend/etc/system_file.xsd">'."\n";
        $content .= $systemElement->asNiceXml();
        $content .= '</config>';

        $this->_helper->writeContentToFile($this->_miniTarget.'/etc/adminhtml/system.xml', $content);
        return;
    }
}
