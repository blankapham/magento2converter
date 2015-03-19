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
 * Magento2challenge Resource Model
 * 
 * @category    Magestore
 * @package     Magestore_Magento2challenge
 * @author      Magestore Developer
 */
class Magestore_Magento2challenge_Model_Mysql4_Class extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('magento2challenge/class', 'id');
    }
//    function saveClassLibrary($dir){
//        $data = array();
//        $this->dupCodeDirsAndFiles($dir, $data);
//        $write = $this->_getWriteAdapter();
//        $write->beginTransaction();
//        $write->insertMultiple($this->getTable('magento2challenge/class'), $data);
//        $write->commit();
//        return;
//    }
//    function dupCodeDirsAndFiles($dir, &$data) {
//        $listing = opendir($dir);
//        while (($entry = readdir($listing)) !== false) {
//            if ($entry != "." && $entry != "..") {
//                if(in_array($entry, array('etc', 'data', 'sql', 'i18n', 'view', 'composer.json', 'LICENSE.txt', 'LICENSE_AFL.txt', 'README.md'))){
//                    continue;
//                }
//                $coreItem = "$dir/$entry";             
//                if (is_dir($coreItem)) {                    
//                    $this->dupCodeDirsAndFiles($coreItem, $data);
//                }elseif(is_file($coreItem)){
//                    $path = str_replace(array('D:\Work\localhost\magento2beta1\app\code\\', '.php', '/'), array('', '', '\\'), $coreItem);
//                    $data[] = array('class' => $path);
//                }
//            }
//        }
//    }
}