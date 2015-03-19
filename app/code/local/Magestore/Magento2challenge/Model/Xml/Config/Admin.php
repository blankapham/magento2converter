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
class Magestore_Magento2challenge_Model_Xml_Config_Admin extends Magestore_Magento2challenge_Model_Xml_Config {
    private $_notes;
    public function __construct($notes) {
        parent::__construct();
        $this->_notes = $notes['note'];
    }
    public function convert(){
        foreach($this->_notes->children() as $note){
            switch ($note->getName()) {
                case 'routers':
                    $this->createRoutesFile($note, 'adminhtml');
                    break;
                case 'events':
                    $this->createEventFile($note, 'adminhtml/');
                    break;
                case 'fieldsets':
                    $this->createFieldsetsFile($note, 'adminhtml');
                    break;
                case 'layout':
                    $moduleData = $this->_helper->getModuleData();
                    $moduleData['admin_layout'] = $note->updates->{$this->_lowerModuleName}->file->xmlentities();
                    $this->_helper->setModuleData($moduleData);
                    break;
                default:
                    break;
            }
        }                  
    }
}
