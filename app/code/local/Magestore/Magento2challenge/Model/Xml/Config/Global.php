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
class Magestore_Magento2challenge_Model_Xml_Config_Global extends Magestore_Magento2challenge_Model_Xml_Config {
    private $_notes;
    public function __construct($notes) {
        parent::__construct();
        $this->_notes = $notes['note'];
    }
    public function convert(){
        foreach($this->_notes->children() as $note){
            switch ($note->getName()) {
                case 'events':
                    $this->createEventFile($note, '');
                    break;
                case 'fieldsets':
                    $this->createFieldsetsFile($note);
                    break;
                case 'pdf':
                    $this->createPdfFile($note);
                    break;
                case 'sales':
                    $this->createSalesFile($note);
                    break;
                case 'template':
                    $this->createEmailTemplateFile($note->email);
                    break;
                default:
                    break;
            }
        }  
        $this->createDiFile($this->_notes);
    }
    public function createDiFile($globalValue){
        $rewrites = $globalValue->xpath('//rewrite');
        if(count($rewrites) == 0) return;
        $config = $this->createXmlElement('config');
        foreach($rewrites as $rewrite){
            $moduleRewrite = $rewrite->getParent()->getName();
            $type = trim($rewrite->getParent()->getParent()->getName(), 's');
            foreach($rewrite as $key => $value){
                $prefer = $this->createXmlElement('preference', array(
                    'for' => $this->_helper->getClassNameFromConfigPath($moduleRewrite.'/'.$key, $type),
                    'type' => str_replace('_', '\\', $value)
                ));
                $this->appendChild($config, $prefer);
            }
        }
        if(isset($globalValue->resources)){
            $resources = $globalValue->resources;
            $itemArray = array();
            foreach($resources->children() as $moduleSetup => $data){
                if(isset($data->setup->class)){
                    $itemArray[] = $this->createXmlElement('item', array('name' => $moduleSetup, 'xsi\:type' => "string"), $this->_helper->getClassNameFromClassName($data->setup->class->xmlentities()));
                }
            }
            if(count($itemArray)){
                $argument = $this->createXmlElement('argument', array('name' => 'resourceTypes', 'xsi\:type' => 'array'));
                foreach($itemArray as $item){
                    $this->appendChild($argument, $item);
                }
                $arguments = $this->createXmlElement('arguments');
                $this->appendChild($arguments, $argument);
                $resourceElement = $this->createXmlElement('type', array('name' => 'Magento\Framework\Module\Updater\SetupFactory'));
                $this->appendChild($resourceElement, $arguments);
                $this->appendChild($config, $resourceElement);
            }
        }
        $content = '<?xml version="1.0"?>'."\n";
        $content .= '<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../../../../lib/internal/Magento/Framework/ObjectManager/etc/config.xsd">'."\n";
        $content .= $config->innerXml();
        $content .= '</config>';
        
        $this->_helper->writeContentToFile($this->_miniTarget.'/etc/di.xml', $content);
        return;
    }
    public function createEmailTemplateFile($emailValue){
        $emailTemplate = array();
        foreach($emailValue->children() as $identify => $email){
            $emailElement = $this->createXmlElement('template', array(
                'id' => $identify,
                'label' => $email->label,
                'file' => $email->file,
                'type' => $email->type,
                'module' => $this->_nameSpace.'_'.$this->_moduleName
            ));
            $emailTemplate[] = $emailElement;
        }
        
        $content = '<?xml version="1.0"?>'."\n";
        $content .= '<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../Email/etc/email_templates.xsd">'."\n";
        foreach($emailTemplate as $email){
            $content .= $email->asNiceXml();
        }
        $content .= '</config>';
        
        $this->_helper->writeContentToFile($this->_miniTarget.'/etc/email_templates.xml', $content);
        return;
    }
    public function createSalesFile($salesValue){
        $config = $this->createXmlElement('config');
        foreach($salesValue->children() as $sale){
            $saleElement = $this->createXmlElement('session', array('name' => $sale->getName()));
            switch ($sale->getName()) {
                case 'quote':
                    $orderSortOrder = array('nominal' => 50, 'subtotal' => 100, 'freeshipping' => 150, 'tax_subtotal' => 200, 'weee' => 225, 'shipping' => 250, 'tax_shipping' => 300, 'discount' => 400, 'tax' => 450, 'weee_tax' => 460, 'grand_total' => 550, 'msrp' => 600);
                    break;
                case 'order_invoice':
                    $orderSortOrder = array('subtotal' => 50, 'discount' => 100, 'shipping' => 150, 'tax' => 200, 'cost_total' => 250, 'grand_total' => 350, 'weee' => 600);
                    break;
                case 'order_creditmemo':
                    $orderSortOrder = array('subtotal' => 50, 'weee' => 100, 'discount' => 150, 'shipping' => 200, 'tax' => 250, 'cost_total' => 300, 'grand_total' => 400);
                    break;
                default:
                    break;
            }
            foreach($sale->children() as $total){
                $totalElement = $this->createXmlElement('group', array('name' => $total->getName()));
                foreach($total->children() as $item){
                    //Bo qua cac total cua core
                    if(array_key_exists($item->getName(), $orderSortOrder)){
                        continue;
                    }
                    $itemArray = array(
                        'name' => $item->getName()
                    );
                    if($item->class){
                        $itemArray['instance'] = $this->_helper->getClassNameFromConfigPath($item->class->xmlentities(), 'model');
                    }
                    if($item->sort_order){
                        $itemArray['sort_order'] = (string) $item->sort_order;
                    }else{
                        $sort_before = 1000;
                        $sort_after = 0;
                        if($item->before){
                            foreach(explode(',', (string)$item->before) as $key){
                                $sort_before = (isset($orderSortOrder[trim($key)]) && $orderSortOrder[trim($key)] < $sort_before) ? $orderSortOrder[trim($key)] : $sort_before;
                            }
                        }
                        if($item->after){
                            foreach(explode(',', (string)$item->after) as $key){
                                $sort_after = (isset($orderSortOrder[trim($key)]) && $orderSortOrder[trim($key)] > $sort_after) ? $orderSortOrder[trim($key)] : $sort_after;
                            }
                        }
                        $itemArray['sort_order'] = (int)($sort_before + $sort_after) / 2;
                    }
                    $itemElement = $this->createXmlElement('item', $itemArray);
                    if($item->renderer){
                        $frontItem = $this->createXmlElement('renderer', array(
                            'name' => 'frontend',
                            'instance' => $this->_helper->getClassNameFromConfigPath($item->renderer->xmlentities(), 'block')
                        ));
                        $this->appendChild($itemElement, $frontItem);
                    }
                    if($item->admin_renderer){
                        $adminItem = $this->createXmlElement('renderer', array(
                            'name' => 'adminhtml',
                            'instance' => $this->_helper->getClassNameFromConfigPath($item->admin_renderer->xmlentities(), 'block')
                        ));
                        $this->appendChild($itemElement, $adminItem);
                    }
                    $this->appendChild($totalElement, $itemElement);
                }
                $this->appendChild($saleElement, $totalElement);
            }
            $this->appendChild($config, $saleElement);
        }
        
        $content = '<?xml version="1.0"?>'."\n";
        $content .= '<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../Sales/etc/sales.xsd">'."\n";
        $content .= $config->innerXml();
        $content .= '</config>';
        
        $this->_helper->writeContentToFile($this->_miniTarget.'/etc/sales.xml', $content);
        return;
    }
    public function createPdfFile($pdfValue){
        $config = $this->createXmlElement('config');
        $renders = null;
        foreach($pdfValue->children() as $key => $value){
            if($key == 'totals'){
                $totals = $this->createXmlElement('totals');
                foreach($value as $keyTotal => $totalValue){
                    $total = $this->createXmlElement('total', array('name' => $keyTotal));
                    $translate = $totalValue->getAttribute('translate');
                    foreach($totalValue as $key => $fieldValue){
                        $needTranslate = array();
                        if(strpos($translate, $key) !== false){
                            $needTranslate = array('translate' => "true");
                        }
                        if($key == 'model'){
                            $fieldValue = $this->_helper->getClassNameFromConfigPath($fieldValue->xmlentities(), 'model');
                        }
                        $fieldElement = $this->createXmlElement($key, $needTranslate, $fieldValue);
                        $this->appendChild($total, $fieldElement);
                    }
                    $this->appendChild($totals, $total);
                }
                $this->appendChild($config, $totals);
            } else {
                if(!$renders){
                    $renders = $this->createXmlElement('renderers');
                }
                $page = $this->createXmlElement('page', array('type' => $key));
                foreach($value as $keyRender => $valueRender){
                    $valueRender = $this->_helper->getClassNameFromConfigPath($valueRender->xmlentities(), 'model');
                    $renderer = $this->createXmlElement('renderer', array('product_type' => $keyRender), $valueRender);
                    $this->appendChild($page, $renderer);                    
                }
                $this->appendChild($renders, $page);
            }
        }
        if($renders){
            $this->appendChild($config, $renders);
        }
        $content = '<?xml version="1.0"?>'."\n";
        $content .= '<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../Sales/etc/pdf_file.xsd">'."\n";
        $content .= $config->innerXml();
        $content .= '</config>';
        
        $this->_helper->writeContentToFile($this->_miniTarget.'/etc/pdf.xml', $content);
        return;
    }
}
