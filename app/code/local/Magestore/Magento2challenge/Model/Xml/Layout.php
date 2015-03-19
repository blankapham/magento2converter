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
class Magestore_Magento2challenge_Model_Xml_Layout extends Magestore_Magento2challenge_Model_Xml {
    private $_frontFile;
    private $_adminFile;
    private $_miniFrontFile;
    private $_miniAdminFile;
    private $_containerArray;
    public function __construct() {
        parent::__construct();
        $this->_frontFile = $this->_target.DS.'view'.DS.'frontend'.DS.'layout';
        $this->_adminFile = $this->_target.DS.'view'.DS.'adminhtml'.DS.'layout';
        
        $this->_miniFrontFile = $this->_miniTarget.DS.'view'.DS.'frontend'.DS.'layout';
        $this->_miniAdminFile = $this->_miniTarget.DS.'view'.DS.'adminhtml'.DS.'layout';
        $this->_containerArray = array("form.additional.info", "form.buttons", "root", "legal", "product.info.bundle.options.top", "bundle.product.options.wrapper", "bundle.options.container", "product.info.bundle.extra", "main", "form", "product-type-tabs", "category.view.container", "category.product.list.additional", "product.info.main", "product.info.price", "product.info.stock.sku", "product.info.type", "alert.urls", "product.info.form.content", "product.info.extrahint", "product.info.social", "product.info.media", "product.info.simple.extra", "product.info.virtual.extra", "checkout.cart.items", "checkout.cart.form.before", "checkout.cart.widget", "checkout.cart.totals.container", "checkout.cart.methods", "checkout.cart.noitems", "checkout.cart.empty.widget", "checkout.onepage.login.before", "form.login.additional.info", "form.billing.additional.info", "shipping_method.available", "shipping_method.additional", "checkout.progress.wrapper", "checkout.onepage.review.info.items.before", "checkout.onepage.review.info.items.after", "order.success.additional.info", "minicart.subtotal.container", "minicart.extra.info", "topCart.extra_actions", "cms_footer_links_container", "product.info.configurable.extra", "customer.form.register.fields.before", "customer.login.container", "product.info.grouped.extra", "payment_methods_before", "payment_methods_after", "checkout.multishipping.overview.items.after", "customer.newsletter.form.before", "adminhtml.block.report.product.lowstock.grid.container", "product.review.form.fields.before", "product.info.details", "content", "submit_before", "submit_after", "order.actions.container", "sales.order.history.info", "header.panel", "header-wrapper", "top.container", "page.messages", "content.top", "content.aside", "content.bottom", "page.bottom", "footer", "listing_head", "listing_before", "customer.wishlist.buttons");
    }
    public function convert(){
        $moduleData = $this->_helper->getModuleData();
        $adminLayoutFile = isset($moduleData['admin_layout']) ? $moduleData['admin_layout'] : $this->_lowerModuleName.'.xml';
        $frontLayoutFile = isset($moduleData['front_layout']) ? $moduleData['front_layout'] : $this->_lowerModuleName.'.xml';
        
        $this->createLayoutFile($this->_frontFile, $frontLayoutFile, $this->_miniFrontFile);
        $this->createLayoutFile($this->_adminFile, $adminLayoutFile, $this->_miniAdminFile);
    }
    public function createLayoutFile($path, $file, $target){
        $xmlNotes = $this->getXmlNotes($path.DS.$file);
        foreach($xmlNotes as $note){
            if(!$note->hasChildren()){
                continue;
            }            
            $layout = '';
            $label = isset($note->label) ? 'label="'.$note->label->xmlentities().'" ' : '';
            $pageName = str_replace('adminhtml_', '', $note->getName());
            $noteElements = $this->createXmlElement('config');
            $this->appendChild($noteElements, $this->createXmlElement('body'));
            $this->getReferenceNote($noteElements->body, $note);
            
           // Zend_debug::dump($noteElements); die();
            
            $content = '<?xml version="1.0"?>'."\n";
            if(isset($note->reference)){
                $content .= '<page '.$layout.' '.$label.' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"  xsi:noNamespaceSchemaLocation="../../../../../../../lib/internal/Magento/Framework/View/Layout/etc/page_configuration.xsd">'."\n";
                foreach($noteElements as $noteElement){
                    $content .= $noteElement->asNiceXml();
                }
                $content .= '</page>';
            } else{
                $content .= '<layout xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../../../../../../lib/internal/Magento/Framework/View/Layout/etc/layout_generic.xsd">'."\n";
                foreach($noteElements as $noteElement){
                    $content .= $noteElement->asNiceXml();
                }
                $content .= '</layout>';
            }
            $this->_helper->writeContentToFile($target.DS.$pageName.'.xml', $content);
        }
        if(file_exists($path.DS.$file)){
            unlink($path.DS.$file);
        }
        return;
    }
    public function getReferenceNote($parent, $note){        
        foreach($note->children() as $childNote){
            switch ($childNote->getName()) {
                case 'move':
                case 'remove':
                    $this->appendChild($parent, $childNote);
                    break;
                case 'update':
                    $this->appendChild($parent->getParent(), $childNote);
                    break;
                case 'action':
                    $action = $this->createXmlElement('action', array('method' => $childNote->getAttribute('method')));
                    $translate = $childNote->getAttribute('translate') ? explode(' ', $childNote->getAttribute('translate')) : array();
                    foreach($childNote->children() as $arg){
                        $translateAtt = in_array($arg->getName(), $translate) ? 'true' : 'false';
                        $argElement = $this->createXmlElement('argument', array('name' => $arg->getName(), 'translate' => $translateAtt), $arg->xmlentities());
                        $this->appendChild($action, $argElement);
                    }
                    $this->appendChild($parent, $action);
                    break;
                case 'block':
                    $attribute = array();
                    if($childNote->getAttribute('name')) 
                        $attribute['name'] = $childNote->getAttribute('name');
                    if($childNote->getAttribute('before')) 
                        $attribute['before'] = $childNote->getAttribute('before');
                    if($childNote->getAttribute('after')) 
                        $attribute['after'] = $childNote->getAttribute('after');
                    
                    $type = $childNote->getAttribute('type');
                    if($type == 'core/text_list'){
                        $block = $this->createXmlElement('container', $attribute);
                        $this->_containerArray[] = $attribute['name'];
                    } else {
                        $attribute['class'] = $this->_helper->getClassNameFromConfigPath($type, 'block');
                        if($childNote->getAttribute('template')){
                            $module = explode('/', $childNote->getAttribute('template'));
                            if($module[0] == $this->_lowerModuleName){
                                $moduleNameSpace = $this->_nameSpace.'_'.$this->_moduleName.'::';
                            }else {
                                $moduleNameSpace = 'Magento_'.  uc_words($module[0]).'::';
                            }                        
                            $attribute['template'] = str_replace($module[0].'/', $moduleNameSpace, $childNote->getAttribute('template'));
                        }
                        $block = $this->createXmlElement('block', $attribute);
                    }
                    if($childNote->hasChildren()){
                        $this->getReferenceNote($block, $childNote);
                    }
                    $this->appendChild($parent, $block);
                    break;
                case 'item':
                    
                    break;
                case 'reference':
                    $referenceType = $this->getReferenceType($childNote->getAttribute('name'));
                    $referenceElement = $this->createXmlElement($referenceType, array('name' => $childNote->getAttribute('name')));
                    $this->getReferenceNote($referenceElement, $childNote);
                    $this->appendChild($parent, $referenceElement);
                    break;

                default:
                    break;
            }
        }
    }
    public function getReferenceType($name){
        if(in_array($name, $this->_containerArray)){
            return 'referenceContainer';
        }
        return 'referenceBlock';
    }
}
