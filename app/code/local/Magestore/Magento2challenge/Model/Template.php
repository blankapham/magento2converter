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
class Magestore_Magento2challenge_Model_Template extends Magestore_Magento2challenge_Model_Base
{
    public function __construct() {
        parent::__construct();
    }
    public function convert($file){
       $content = file_get_contents($file);
       $content = '<?php $object_manager = \Magento\Framework\App\ObjectManager::getInstance(); ?>'."\n".$content;
       //Helper, resourceHelper
        if(preg_match_all('/Mage\s*::\s*helper\s*\(\s*["\'](.*?)["\']\s*\)/', $content, $matched)){
            $this->replaceContent($content, $matched, 'helper');
        } 
        //Model, singleton
        if(preg_match_all('#Mage\:\:(getModel|getSingleton)\s*\(["\'](.*?)["\']\)#', $content, $matches)){
            $this->replaceContent($content, $matched, 'model');
        }
        //Block, blockSingleton
        if(preg_match_all('/Mage\s*::\s*getBlockSingleton\s*\(\s*["\'](.*?)["\']\s*\)/', $content, $matched)){
            $this->replaceContent($content, $matched, 'block');
        }
        //Event
        $content = Mage::helper('magento2challenge/convertModel')->replaceEvent($content);
        $content = Mage::helper('magento2challenge/convertModel')->replaceStoreConfig($content);
        $content = Mage::helper('magento2challenge/convertModel')->replaceGetBaseUrl($content);
        $content = Mage::helper('magento2challenge/convertModel')->replaceAppGetStore($content);

        $content = $this->_helper->translateText($content);
        $this->_helper->writeContentToFile($file, $content);
        return $this;
    }
    public function replaceContent(&$content, $matched, $type){
        if(isset($matched[0]) && isset($matched[1]) && count($matched[0]) && count($matched[1])){
            foreach($matched[0] as $key => $value){
                $className = $this->_helper->getClassNameFromConfigPath($matched[1][$key], $type);
                if(strpos($value, 'Singleton')){
                    $method = 'get';
                }else{
                    $method = 'create';
                }
                $content = str_replace($value, '$object_manager->'.$method.'(\''.$className.'\')', $content);
            }
        }
        return $this;
    }
}
