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
class Magestore_Magento2challenge_Model_Locale extends Magestore_Magento2challenge_Model_Base
{
    protected $_locale;
    protected $_csvFileName;


    public function __construct() {
        parent::__construct();
        $this->_locale = $this->_target.DS.'i18n';
        $this->_csvFileName = $this->getTranslateFileName();
    }
    public function convert(){
        if(!$this->_csvFileName){
            return;
        }
        $listing = opendir($this->_locale);
        $processEmailFile = false;
        while (($entry = readdir($listing)) !== false) {
            if ($entry != "." && $entry != "..") {
                $translateFile = $this->_locale.DS.$entry.DS.$this->_csvFileName;
                if(is_file($translateFile)){
                    rename($translateFile, $this->_locale.DS.$entry.'.csv');
                }
                $emailFolder = $this->_locale.DS.$entry.DS.'template'.DS.'email';
                if(is_dir($emailFolder) && (!$processEmailFile || $entry == 'en_US')){
                    //copy($emailFolder, $this->_target.DS.'view');
                    rename($emailFolder, $this->_target.DS.'view'.DS.'email');
                    $processEmailFile = true;
                }
                $this->_helper->rrmdir($this->_locale.DS.$entry);
            }
        }
        return $this;
    }
    protected function getTranslateFileName(){
        $moduleData = $this->_helper->getModuleData();
        if(array_key_exists('config', $moduleData)){
            $configData = $moduleData['config'];
            if(isset($configData['frontend']['translate']['modules'][$this->_nameSpace.'_'.$this->_moduleName]['files']['default'])){
                $translateData = $configData['frontend']['translate']['modules'][$this->_nameSpace.'_'.$this->_moduleName]['files']['default'];
                return $translateData;
            }
        }
        return null;
    } 
}
