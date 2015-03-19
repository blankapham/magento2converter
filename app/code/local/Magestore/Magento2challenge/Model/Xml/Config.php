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
class Magestore_Magento2challenge_Model_Xml_Config extends Magestore_Magento2challenge_Model_Xml {
    private $_configFile;
    public function __construct() {
        parent::__construct();
        $this->_configFile = $this->_target.DS.'etc'.DS.'config.xml';
    }
    public function convert(){
        foreach($this->getXmlNotes($this->_configFile) as $area => $value){
            switch ($area) {
                case 'modules':
                    $this->createModuleFile($value);
                    break;
                case 'frontend':
                    $this->getModel('config_frontend', array('note' => $value))->convert();
                    break;
                case 'admin':
                case 'adminhtml':
                    $this->getModel('config_admin', array('note' => $value))->convert();
                    break;
                case 'global':
                    $this->getModel('config_global', array('note' => $value))->convert();
                    break;
                case 'default':
                    $this->createConfigDefaultFile($value);
                    break;
                case 'crontab':
                    $this->createCrontabFile($value);
                    break;
                default:
                    break;
            }
        }
    }
    public function createModuleFile($moduleValue){
        $moduleNameSpace = $this->_nameSpace.'_'.$this->_moduleName;
        $version = $moduleValue->$moduleNameSpace->version ? $moduleValue->$moduleNameSpace->version : '2.0.0';
        
        $moduleConfig = $this->createXmlElement('module', array(
            'name' => $moduleNameSpace,
            'schema_version' => $version
        ));
        
        //Find depends module
        $moduleConfigDir = $this->_root.DS.'OUTPUT'.DS.'app'.DS.'etc'.DS.'modules'.DS.$moduleNameSpace.'.xml';
        if(file_exists($moduleConfigDir)){
            $moduleDepends = $this->getXmlNotes($moduleConfigDir)->modules->$moduleNameSpace->depends;
            if($moduleDepends && $moduleDepends->hasChildren()){
                $sequence = $this->createXmlElement('sequence');
                foreach($moduleDepends->children() as $module => $value){
                    $this->appendChild($sequence, $this->createXmlElement('module', array('name' => $module)));
                }
                $this->appendChild($moduleConfig, $sequence);
            }
            $this->_helper->rrmdir($this->_root.DS.'OUTPUT'.DS.'app'.DS.'etc');
        }
        
        $content = '<?xml version="1.0"?>'."\n";
        $content .= '<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../../../../lib/internal/Magento/Framework/Module/etc/module.xsd">'."\n";
        $content .= $moduleConfig->asNiceXml();
        $content .= '</config>';
        
        $this->_helper->writeContentToFile($this->_miniTarget.DS.'etc'.DS.'module.xml', $content);
        
        return ;
    }
    public function createConfigDefaultFile($defaultValue){
        $config = '<?xml version="1.0"?>'."\n";
        $config .= '<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../Core/etc/config.xsd">'."\n";
        $config .= $defaultValue->asNiceXml();
        $config .= '</config>';
        
        $this->_helper->writeContentToFile($this->_miniTarget.'/etc/config.xml', $config);
        
        return;
    }
    public function createCrontabFile($cronValue){
        $cronJobs = array();  
        foreach($cronValue->jobs->children() as $key => $value){
            $cronJobs[$key]['schedule'] = $value->schedule->cron_expr;
            list($cronJobs[$key]['class_name'], $cronJobs[$key]['method_name']) = explode('::', $value->run->model);
            $cronJobs[$key]['class_name'] = $this->_helper->getClassNameFromConfigPath($cronJobs[$key]['class_name'], 'model');
        }
        $notes = $this->createXmlElement('group', array('id' => 'default'));
        foreach($cronJobs as $name => $job){
            $jobElement = $this->createXmlElement('job', array(
                'name' => $name,
                'instance' => $job['class_name'],
                'method' => $job['method_name'])
            );
            $jobElement->addChild('schedule', $job['schedule']);
            $this->appendChild($notes, $jobElement);
        }
        $content = '<?xml version="1.0"?>'."\n";
        $content .= '<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../Cron/etc/crontab.xsd">'."\n";
        $content .= $notes->asNiceXml();
        $content .= '</config>';
        
        $this->_helper->writeContentToFile($this->_miniTarget.'/etc/crontab.xml', $content);
        
        return;
    }
    public function createRoutesFile($routersNotes, $area){        
        $routes = array();
        foreach($routersNotes->children() as $name => $value){
            $use = $value->use->xmlentities();
            if(!in_array($use, $routes)){
                $routes[$use] = $this->createXmlElement('router', array('id' => $value->use));
            }
            $route = $this->createXmlElement('route', array(
                'id' => $name,
                'frontName' => $value->args->frontName
            ));
            $module = $this->createXmlElement('module', array('name' => $value->args->module));
            $this->appendChild($route, $module);
            
            $this->appendChild($routes[$use], $route);
        }
        
        $content = '<?xml version="1.0"?>'."\n";
        $content .= '<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../../../../../lib/internal/Magento/Framework/App/etc/routes.xsd">'."\n";
        foreach($routes as $key => $value){
            $content .= $value->asNiceXml();
        }
        $content .= '</config>';
        $this->_helper->writeContentToFile($this->_miniTarget.'/etc/'.$area.'/routes.xml', $content);
        return;
    }
    public function createEventFile($eventValue, $area){      
        $eventNotes = array();
        $config = $this->createXmlElement('config');
        foreach($eventValue->children() as $eventName => $event){
            $eventNote = $this->createXmlElement('event', array(
                'name' => $eventName
            ));          
            foreach($event->observers->children() as $observerName => $observer){
                $instant = $this->_helper->getClassNameFromConfigPath($observer->class->xmlentities(), 'model');
                $share = $observer->type == 'singleton' ? 'true' : 'false';
                $observerNote = $this->createXmlElement('observer', array(
                    'name' => $observerName,
                    'instance' => $instant, 
                    'method' => $observer->method,
                    'shared' => $share
                ));
                $this->appendChild($eventNote, $observerNote);
            }
            $this->appendChild($config, $eventNote);
        }
        $eventConfig = '<?xml version="1.0"?>'."\n";
        $eventConfig .= '<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../../../../../lib/internal/Magento/Framework/Event/etc/events.xsd">'."\n";
        $eventConfig .= $config->innerXml();
        $eventConfig .= '</config>';
        
        $this->_helper->writeContentToFile($this->_miniTarget.'/etc/'.$area.'events.xml', $eventConfig);
        
        return;
    }
    public function createFieldsetsFile($fieldsetsValue, $area = 'global'){
        $scope = $this->createXmlElement('scope', array('id' => $area));
        foreach($fieldsetsValue->children() as $identify => $fieldsetValue){
            $fieldset = $this->createXmlElement('fieldset', array('id' => $identify));
            foreach($fieldsetValue as $variable => $fieldValue){
                $field = $this->createXmlElement('field', array('name' => $variable));
                foreach($fieldValue as $aspectValue => $value){
                    $aspect = $this->createXmlElement('aspect', array('name' => $aspectValue));
                    $this->appendChild($field, $aspect);
                }
                $this->appendChild($fieldset, $field);
            }
            $this->appendChild($scope, $fieldset);
        }
        
        $fieldsetConfig = $this->getXmlNotes($this->_target.'/etc/fieldset.xml');
        if($fieldsetConfig){
            $this->appendChild($fieldsetConfig, $scope);
            $newFile = false;
        }  else {
            $fieldsetConfig = $scope;
            $newFile = true;
        }
        
        $content = '<?xml version="1.0"?>'."\n";
        $content .= '<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../../../../lib/internal/Magento/Framework/Object/etc/fieldset.xsd">'."\n";
        $content .= $newFile ? $fieldsetConfig->asNiceXml() : $fieldsetConfig->innerXml();
        $content .= '</config>';
        
        $this->_helper->writeContentToFile($this->_miniTarget.'/etc/fieldset.xml', $content);
        return;
    }
}
