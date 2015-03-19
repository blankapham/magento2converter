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
 * Magento2challenge Adminhtml Block
 * 
 * @category    Magestore
 * @package     Magestore_Magento2challenge
 * @author      Magestore Developer
 */
class Magestore_Magento2challenge_Block_Adminhtml_Magento2challenge extends Mage_Adminhtml_Block_Widget_Grid_Container
{
     public function _prepareLayout()
    {
        $this->setTemplate('magento2challenge/convertmodule.phtml');
    }
    public function getClassError(){
        $moduleData = Mage::helper('magento2challenge')->getModuleData();
        if(array_key_exists('class_error', $moduleData)){
            return $moduleData['class_error'];
        }
        return NULL;
    }
    public function createExampleModuleZip(){
        $zip = new ZipArchive;
        $result = $zip->open('INPUT'.DS.'ExampleModule.zip', ZipArchive::CREATE);
        $dir = Mage::getBaseDir().DS.'INPUT';
        $base = Mage::getBaseDir().DS.'INPUT';
        if ($result === TRUE){
            $this->addDirectoryToZip($zip, $dir, $base);
            $zip->close();
        }
        return 'ExampleModule.zip';
    }
      public function addDirectoryToZip($zip, $dir, $base){
        $newFolder = str_replace($base, '', $dir);
        $zip->addEmptyDir(ltrim($newFolder, DS));
        foreach(glob($dir . DS. '*') as $file){
            if(is_dir($file)){
                $zip = $this->addDirectoryToZip($zip, $file, $base);
            }else{
                $newFile = str_replace($base, '', $file);
                $zip->addFile($file, ltrim($newFile, DS));
            }
        }
        return $zip;
    }
}