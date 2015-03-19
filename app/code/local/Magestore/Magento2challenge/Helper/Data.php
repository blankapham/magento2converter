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
class Magestore_Magento2challenge_Helper_Data extends Mage_Core_Helper_Abstract {

    /**
     * Write content to file. If $fileName existed, clear all content and put new content.
     * Else create new file with content
     *
     * @param string $fileName - path to the file
     * @param string $content
     * @return type
     */
    public function writeContentToFile($file, $content) {
        $root = getcwd();
        if(is_int(strpos($file, $root))){
            $dir = $file;
        } else {
            $dir = $root.DS.'OUTPUT'.DS.$file;
        }
        $dirName = dirname($dir);
        if (!is_dir($dirName)) {
            mkdir($dirName, 0777, true);
        }
        $handle = fopen($dir, 'w');
        $res = fputs($handle, $content);
        fclose($handle);
        return $res;
    }
    public function writeContentSqlFile($file){
        if(is_file($file)){
            $content = file_get_contents($file);
            $content = $this->run($content);
/**            $content = Mage::getModel('magento2challenge/compiler')->replaceGetModel($content);
            $content = '<?php $object_manager = \Magento\Core\Model\ObjectManager::getInstance(); ?>'."\n".$content;
            $content = str_replace('$this->_objectManager', '$object_manager', $content);*/
            $this->writeContentToFile($file, $content);
            
            $fileName = basename($file);
            $fileName = str_replace('mysql4-', '', $fileName);
            rename($file, dirname($file).DS.$fileName);
        }
        return true;
    }

    /**
     * Find the path of model/block/helper class with the path used in Magento 1.x
     *
     * @param string $path - magento2challenge/example_path
     * @param type $type - block, model, helper, model_resource
     * @return string Magestore\Magento2challege\Model\Example\Path
     */
    public function getClassNameFromConfigPath($path, $type) {
        $moduleData = $this->getModuleData();
        $nameSpace = $moduleData['name_space'];
        $moduleName = $moduleData['module_name'];
        $lowerModuleName = $moduleData['lower_module_name'];
        $nameArray = explode('/', $path);
        $nameArray[1] = isset($nameArray[1]) ? $nameArray[1] : 'data';
        if ($nameArray[0] == $lowerModuleName) {
            $addition = $nameArray[1];
        } elseif ($nameArray[0] == 'adminhtml') {
            $pathArray = explode('_', $nameArray[1]);
            $hasValue = false;
            if ($tempName = $this->isMagentoModule($pathArray[0])) {
                $nameSpace = 'Magento';
                $moduleName = $tempName;
                $addition = str_replace($pathArray[0] . '_', '', $nameArray[1]);
                $hasValue = true;
            } elseif ($pathArray[0] == 'system') {
                foreach ($pathArray as $key => $value) {
                    if ($key > 0 && strpos($nameArray[1], $value . '_') && $tempName = $this->isMagentoModule($value)) {
                        $nameSpace = 'Magento';
                        $moduleName = $tempName;
                        $addition = str_replace(array('system_', $value . '_'), array('', ''), $nameArray[1]);
                        $hasValue = true;
                        break;
                    } elseif ($key > 0 && $type == 'model') {
                        $nameSpace = 'Magento';
                        $moduleName = 'Backend';
                        $addition = str_replace('system_', '', $nameArray[1]);
                        $hasValue = true;
                    }
                }
            }
            if (!$hasValue) {
                $nameSpace = 'Magento';
                $moduleName = 'Backend';
                $addition = $nameArray[1];
            }
        } elseif ($tempName = $this->isMagentoModule($nameArray[0])) {
            $nameSpace = 'Magento';
            $moduleName = $tempName;
            $addition = $nameArray[1];
        } else {
            $moduleName = $nameArray[0];
            $addition = $nameArray[1];
        }
        $className = uc_words("{$nameSpace}_{$moduleName}_{$type}_{$addition}", '\\');
        if ($moduleName != $moduleData['module_name'] && !$this->magentoClassExist($className)) {
            $className = $this->checkClassError($className, $path, $type, $moduleData);
        }

        return $className;
    }
    /**
     *
     * @param type $className Mage_Core_Model_Resource_Setup
     */
    public function getClassNameFromClassName($className){
        $pathArray = explode('_', $className);
        if(count($pathArray) < 4) return '';
        if($pathArray[0] == 'Mage'){
            $pathArray[0] = 'Magento';
            if($pathArray[1] == 'Adminhtml'){
                if($this->isMagentoModule($pathArray[3])){
                    $pathArray[1] = $pathArray[3];
                    if($pathArray[2] == 'Block'){
                        $pathArray[3] = 'Adminhtml';
                    }else{
                        unset($pathArray[3]);
                    }
                } else {
                    $pathArray[1] = 'Backend';
                }
            }
        }
        $className = implode('\\', $pathArray);
        if($pathArray[0] == 'Magento' && !$this->magentoClassExist($className)){
            $className = $this->checkClassError($className, $className, '');
        }
        return $className;
    }
    public function checkClassError($className, $path, $type, $moduleData = null){
        if(!$moduleData){
            $moduleData = $this->getModuleData();
        }
        $classError = array_key_exists('class_error', $moduleData) ? $moduleData['class_error'] : array();
        $model = Mage::getModel('magento2challenge/magento2challenge')->getCollection()
                ->addFieldToFilter('origin', $path)
                ->addFieldToFilter('type', $type)
                ->getFirstItem();
        if ($model && $model->getId() && $model->getTarget()) {
            $className = $model->getTarget();
        } else {
            $classError[$path] = $type;
        }
        if (count($classError) > 0) {
            $moduleData['class_error'] = $classError;
            $this->setModuleData($moduleData);
        }
        return $className;
    }

    public function setRequireMagento2Class($class) {
        $moduleData = $this->getModuleData();
        $classError = $moduleData['class_error'];
        $classError[$class] = 1;
        $moduleData['class_error'] = $classError;
        $this->setModuleData($moduleData);
        return;
    }

    public function magentoClassExist($className) {
        $model = Mage::getModel('magento2challenge/class')->getCollection()
                ->addFieldToFilter('class', $className);
        if ($model->getSize()) {
            return true;
        }
        if(strpos($className, 'Magento\\Framework')!== FALSE){
            return true;
        }
        return false;
    }

    public function isMagentoModule($moduleName = '') {
        $moduleName = strtolower($moduleName);
        $moduleArray = array("adminnotification" => "AdminNotification", "authorization" => "Authorization", "backup" => "Backup", "bundle" => "Bundle", "captcha" => "Captcha", "catalog" => "Catalog", "catalogimportexport" => "CatalogImportExport", "cataloginventory" => "CatalogInventory", "catalogrule" => "CatalogRule", "catalogsearch" => "CatalogSearch", "catalogurlrewrite" => "CatalogUrlRewrite", "catalogwidget" => "CatalogWidget", "centinel" => "Centinel", "checkout" => "Checkout", "checkoutagreements" => "CheckoutAgreements", "cms" => "Cms", "cmsurlrewrite" => "CmsUrlRewrite", "configurableimportexport" => "ConfigurableImportExport", "configurableproduct" => "ConfigurableProduct", "contact" => "Contact", "core" => "Core", "cron" => "Cron", "currencysymbol" => "CurrencySymbol", "customer" => "Customer", "customerimportexport" => "CustomerImportExport", "designeditor" => "DesignEditor", "dhl" => "Dhl", "directory" => "Directory", "downloadable" => "Downloadable", "eav" => "Eav", "email" => "Email", "fedex" => "Fedex", "giftmessage" => "GiftMessage", "googleadwords" => "GoogleAdwords", "googleanalytics" => "GoogleAnalytics", "googleoptimizer" => "GoogleOptimizer", "googleshopping" => "GoogleShopping", "groupedimportexport" => "GroupedImportExport", "groupedproduct" => "GroupedProduct", "importexport" => "ImportExport", "indexer" => "Indexer", "integration" => "Integration", "layerednavigation" => "LayeredNavigation", "log" => "Log", "msrp" => "Msrp", "multishipping" => "Multishipping", "newsletter" => "Newsletter", "offlinepayments" => "OfflinePayments", "offlineshipping" => "OfflineShipping", "pagecache" => "PageCache", "payment" => "Payment", "persistent" => "Persistent", "productalert" => "ProductAlert", "reports" => "Reports", "requirejs" => "RequireJs", "review" => "Review", "rss" => "Rss", "rule" => "Rule", "sales" => "Sales", "salesrule" => "SalesRule", "search" => "Search", "sendfriend" => "Sendfriend", "shipping" => "Shipping", "sitemap" => "Sitemap", "store" => "Store", "tax" => "Tax", "taximportexport" => "TaxImportExport", "theme" => "Theme", "translation" => "Translation", "ui" => "Ui", "ups" => "Ups", "urlrewrite" => "UrlRewrite", "user" => "User", "usps" => "Usps", "webapi" => "Webapi", "weee" => "Weee", "widget" => "Widget", "wishlist" => "Wishlist");
        if (in_array($moduleName, array_keys($moduleArray))) {
            return $moduleArray[$moduleName];
        } else {
            return false;
        }
    }

    /**
     *
     * @return array('module_name', 'name_space', 'lower_module_name')
     */
    public function getModuleData() {
        if($data = Mage::getSingleton('admin/session')->getData('magento2convert_data')){
            return $data;
        }
        return array();
    }

    public function setModuleData($data) {
        return Mage::getSingleton('admin/session')->setData('magento2convert_data', $data);
    }

    public function getModuleDir($type = '') {
        $moduleData = $this->getModuleData();
        $dir = getcwd();
        switch ($type) {
            case 'target':
                $dir = $dir . DS . 'OUTPUT' . DS . 'app' . DS . 'code' . DS . $moduleData['name_space'] . DS . $moduleData['module_name'];
                break;
            case 'origin':
                $dir = $dir . DS . 'INPUT'.DS.'app'.DS.'code';
                if(is_dir($dir.DS.'local')){
                    $dir = $dir.DS.'local'.DS.$moduleData['name_space'] . DS . $moduleData['module_name'];
                } else {
                    $dir = $dir.DS.'community'.DS.$moduleData['name_space'] . DS . $moduleData['module_name'];
                }
                break;
            case 'mini_target':
                $dir = 'app' . DS . 'code' . DS . $moduleData['name_space'] . DS . $moduleData['module_name'];
        }
        return $dir;
    }

//    function rrmdir($dir) {
//        foreach (glob($dir . DS . '*') as $file) {
//            if (is_dir($file))
//                $this->rrmdir($file);
//            else
//                unlink($file);
//        }
//        rmdir($dir);
//        return;
//    }
    function rrmdir($dir) { 
        $files = array_diff(scandir($dir), array('.','..')); 
        foreach ($files as $file) { 
            (is_dir($dir.DS.$file)) ? $this->rrmdir($dir.DS.$file) : unlink($dir.DS.$file); 
        } 
        return rmdir($dir); 
    } 

    public function getClassName($file) {   // get class name of file
        $fp = fopen($file, 'r');
        $class = $buffer = '';
        $i = 0;
        while (!$class) {
            if (feof($fp))
                break;
            $buffer .= fread($fp, 512);
            if (preg_match('#class(.*?)(extends|\{)#s', $buffer, $matches)) {
                $class = $matches[1];
                break;
            }
        }
        return trim($class);
    }

    public function getParentClassName($file) {   // get class name of file
        $fp = fopen($file, 'r');
        $class = $buffer = '';
        $i = 0;
        while (!$class) {
            if (feof($fp))
                break;
            $buffer .= fread($fp, 512);
            if (preg_match('#extends(.*?)\{#s', $buffer, $matches)) {
                $class = $matches[1];
                break;
            }
        }
        return trim($class);
    }

    public function getBodyAFunction($content, $funcName) { // get content of a function
        $allToken = token_get_all($content);
        $functionContent = ''; //content of function
        $is_in_function = false;
        $inGroup = 0;
        foreach ($allToken as $tok) {
            if (is_array($tok)) {
                if ($tok[1] == $funcName) {
                    $is_in_function = true;
                }
            }
            if (is_string($tok) && $is_in_function) {
                if ($tok == '{')
                    $inGroup++;
                else if ($tok == '}')
                    $inGroup--;
                //out of class and break loop when
                if ($tok == '}' && $inGroup == 0) {
                    $is_in_function = false;
                    break;
                }
            }
            //get content * when in class { * }
            if ($is_in_function && $inGroup) {
                if (is_array($tok)) {
                    $functionContent .= $tok[1];
                } else if (is_string($tok)) {
                    $functionContent .= $tok;
                }
            }
        }
        return substr($functionContent, 1);
    }
    /**
     * replace translate from magento 1.x to magento 2x
     *
     * @param type $content
     * @param type $className
     * @return type
     */
    public function translateText($content) {
        if (preg_match_all('#(\$|Mage::)[A-z0-9_\>\(\)\'\"]*?\s*-\>\s*__\(#', $content, $matches)) {
            if(isset($matches[0]) && isset($matches[0][0])){
                $content = str_replace($matches[0][0], '__(', $content);
            }
        }
        return $content;
    }

    /**
     * Replace content of a file.
     *
     * @param string $file
     * @param array $search
     * @param array $replace
     * @return
     */
    public function replaceContent($file, $search, $replace) {
        $handle = fopen($file, 'r+');
        $content = '';
        while (!feof($handle)) {
            $content .= fgets($handle);
        }
        fclose($handle);

        $content = preg_replace($search, $replace, $content);
        $handle = fopen($file, 'w');
        fputs($handle, $content);
        fclose($handle);

        return;
    }
    public function replaceMageLog($content){
        if (preg_match_all('#Mage\:\:(log\(|logException\(|throwException\()(.*?)\s*#', $content, $matches)) {
           $oldString = $matches[0][0]; 
           if(strpos($matches[1][0],'throwException') !== false)
                $matches[1][0] = str_replace ('throwException','log', $matches[1][0]); 
           $newString = "\$this->_objectManager->create('Magento\Framework\Logger')->".$matches[1][0];
           $content = str_replace($oldString,$newString,$content);
        }
        return $content;
    }
    public function run($content){
        $content = Mage::getModel('magento2challenge/compiler')->replaceHelper($content);
        $content = Mage::getModel('magento2challenge/compiler')->replaceStoreConfig($content);
        $content = Mage::getModel('magento2challenge/compiler')->replaceEvent($content);
        $content = Mage::getModel('magento2challenge/compiler')->replaceBlockSingleton($content);
        $content = Mage::getModel('magento2challenge/compiler')->replaceGetBaseUrl($content);
        $content = Mage::getModel('magento2challenge/compiler')->replaceAppGetStore($content);
        $content = Mage::getModel('magento2challenge/compiler')->replaceVersion($content);
        $content = Mage::helper('magento2challenge')->translateText($content);
        $content = $this->replaceMageLog($content);
        return $content;
    }
}
