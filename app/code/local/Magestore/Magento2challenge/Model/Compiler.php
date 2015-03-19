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
 * Magento2challenge Model
 *
 * @category    Magestore
 * @package     Magestore_Magento2challenge
 * @author      Magestore Developer
 */
class Magestore_Magento2challenge_Model_Compiler extends Mage_Core_Model_Abstract
{
    protected $_content;
    protected $_newContent;
    protected $_construct;
    protected $_newConstruct;
    protected $_mainTable; //main table of database
    protected $_functions = array();
    protected $_variables = array();

    protected $_helper; //helper/convertModel
    protected $_helperData; //helper/data
    protected $_config; //model/config/config

    public function _construct()
    {
        parent::_construct();
        $this->_init('magento2challenge/compiler');
        $this->_helper = Mage::helper('magento2challenge/convertModel');
        $this->_helperData = Mage::helper('magento2challenge');
        $this->_config = Mage::getModel('magento2challenge/config_config');
    }

    public function setContent($content)
    {
        $this->_content = $this->_newContent = $content;

        return $this;
    }

    public function setMaintable($maintable)
    {
        $this->_mainTable = $maintable;
    }

    public function setFunctions($functions)
    {
        $this->_functions = $functions;
        return $this;
    }

    public function setVariables($variables)
    {
        $this->_variables = $variables;
    }

    public function getVariables()
    {
        return $this->_variables;
    }

    public function setConstruct($construct)
    {
        $this->_construct = $construct;
    }

    public function setNewConstruct($newConstruct)
    {
        $this->_newConstruct = $newConstruct;
    }

    public function run()
    {

        foreach ($this->_functions as $name => $content) {
            //replace registry
            $content = $this->replaceRegistry($content);
            //replace event
            $content = $this->replaceEvent($content);
            //replace translate text
            $content = $this->_helperData->translateText($content);
            //replace new get model: getModel, getSingleton, getResourceModel
            $content = $this->replaceGetModel($content);
            //replace blockSingleton
            $content = $this->replaceBlockSingleton($content);
            //replace get helper
            $content = $this->replaceHelper($content);
            //repace getStoreConfig
            $content = $this->replaceStoreConfig($content);
            //repace getBaseUrl
            $content = $this->replaceGetBaseUrl($content);
            //replace getUrl -> \Magento\Framework\UrlInterface $urlBuilder->getUrl
            //$content = $this->replaceGetUrl($content);
            //replace Mage::app()->getStore[s]()
            $content = $this->replaceAppGetStore($content);
            //replace Varien_Object() -> \Magento\Framework\Object()
            $content = $this->replaceVarienObject($content);
            //replace Mage::getVersion
            $content = $this->replaceVersion($content);



            $this->_functions[$name] = $content;
        }

        //check object need declare
        $this->_checkObjectNeed();

        return $this->_functions;
    }

    public function replaceVarienObject($content){
        //\Magento\Framework\Object();
        if(preg_match_all('#Varien_Object\s*\(\s*(.*?)\s*\)#', $content, $matched)){
            foreach($matched[0] as $i => $s){
                $content = str_replace($s, '\Magento\Framework\Object('.$matched[1][$i].')', $content);
            }
        }
        return $content;
    }

    //replace old registry and register with new registry and new register
    //param: $content is string to replace on
    //return: string new content
    public function replaceRegistry($content)
    {
        //get old registrys and replace
        if (preg_match_all('/Mage\s*::\s*registry\s*\(\s*["\'](.*?)["\']\s*\)\s*/', $content, $matched)) {
            $registrys = $matched[0];
            $registryKey = $matched[1];
            foreach ($registrys as $i => $reg) {
                $content = str_replace($reg, '$this->_registry->registry(\'' . $registryKey[$i] . '\')', $content);
            }
        }
        //get old registers and replace
        if (preg_match_all('/Mage\s*::\s*register\s*\(\s*["\'](.*?)["\']\s*,\s*(.*?)\s*\)\s*/', $content, $matched)) {
            $registers = $matched[0];
            $registerKeys = $matched[1];
            $registerDatas = $matched[2];
            foreach ($registers as $i => $reg) {
                $content = str_replace($reg, '$this->_registry->register(\'' . $registerKeys[$i] . '\', ' . $registerDatas[$i] . ')', $content);
            }
        }

        return $content;
    }

    //replace an dispatch event from old event to new event
    public function replaceEvent($content)
    {
        //find and replace Mage::dispatchEvent('catalog_block_product_status_display', array('status' => $statusInfo));
        if (preg_match_all('#(Mage\s*\:\:\s*dispatchEvent\s*\({1}\s*(["\']?(?:.|\n|\s)*?["\']?)\s*(?:,\s*(["\']?(?:.|\n|\s)*?["\']?)\s*)?\){1})\s*(?:\$|->|;)#', $content, $matched)) {
            $funcMatchs = $matched[1];
            $params1 = $matched[2];
            $params2 = $matched[3];
            foreach ($funcMatchs as $i => $f) {
                if ($params2[$i] != '') {
                    $content = str_replace($f, '$this->_eventManager->dispatch(' . $params1[$i] . ', ' . $params2[$i] . ')', $content);
                } else {
                    $content = str_replace($f, '$this->_eventManager->dispatch(' . $params1[$i] . ')', $content);
                }
            }
        }
        return $content;
    }

    //replace Mage::getModel, getSingleton, getResourceModel to new method
    public function replaceGetModel($content)
    {
        if (preg_match_all('/Mage\s*\:\:\s*(getModel|getSingleton|getResourceModel)\s*\(\s*["\'](.*?)["\']\s*\)/', $content, $matches)) {
            $allFunctions = $matches[0];
            $allTextParams = $matches[2];
            $allMethodTypes = $matches[1];
            foreach ($allFunctions as $index => $strFunc) {
                $method = '';
                if ($allMethodTypes[$index] == 'getModel') {
                    $method = 'create';
                } elseif ($allMethodTypes[$index] == 'getSingleton' || $allMethodTypes[$index] == 'getResourceModel') {
                    $method = 'get';
                }
                if ($method) {
                    $newClassEntity = $this->_helperData->getClassNameFromConfigPath($allTextParams[$index], 'model'); //get new object type from old type
                    $newFunctionContent = '$this->_objectManager->' . $method . '(\'' . $newClassEntity . '\')';
                    $content = str_replace($strFunc, $newFunctionContent, $content);
                }
            }
        }

        return $content;
    }

    //replace Mage::getBlockSingleton
    public function replaceBlockSingleton($content)
    {
        if (preg_match_all('/Mage\s*::\s*getBlockSingleton\s*\(\s*["\'](.*?)["\']\s*\)/', $content, $matched)) {
            $blocks = $matched[0];
            $blockNames = $matched[1];
            foreach ($blocks as $i => $blk) {
                $blockType = $this->_helperData->getClassNameFromConfigPath($blockNames[$i], 'block');
                $content = str_replace($blk, '$this->_objectManager->get(\'' . $blockType . '\')', $content);
            }
        }

        return $content;
    }

    //replace Mage::helper
    public function replaceHelper($content)
    {
        if (preg_match_all('#(Mage\s*\:\:\s*helper\s*\({1}\s*((?:.|\n|\s)*?)\s*\){1})\s*(?:;|->|\$)#', $content, $matched)) {
            $helpers = $matched[1];
            $helperNames = $matched[2];
            foreach ($helpers as $i => $hlp) {
                $helperType = $this->_helperData->getClassNameFromConfigPath(trim($helperNames[$i], '"\''), 'helper');
                $content = str_replace($hlp, '$this->_objectManager->get(\'' . $helperType . '\')', $content);
            }
        }

        return $content;
    }

    //replace Mage::helper
    public function replaceStoreConfig($content)
    {
        if (preg_match_all('/Mage\s*::\s*getStoreConfig\s*\(\s*(["\']?.*?["\']?)\s*(,\s*(["\']?.*["\']?)\s*)?\)/', $content, $matched)) {
            $funcMatchs = $matched[0];
            $params1 = $matched[1];
            $params2 = $matched[3];
            foreach ($funcMatchs as $i => $func) {
                //TODO: can Bien dich config path $params1
                if ($params2[$i] != '') {
                    $content = str_replace($func, '$this->_config->getValue(' . $params1[$i] . ', ' . $params2[$i] . ')', $content);
                } else {
                    $content = str_replace($func, '$this->_config->getValue(' . $params1[$i] . ')', $content);
                }
            }
        }

        return $content;
    }

    public function replaceGetBaseUrl($content)
    {
        if (preg_match_all('#(Mage\s*\:\:\s*getBaseUrl\s*\({1}\s*((?:.|\n|\s)*?)\s*(?:,\s*((?:.|\n|\s)*?)\s*)?\){1})\s*(?:\.|\$|->|;)#', $content, $matched)) {
            $replace = $matched[1];
            $param1 = $matched[2];
            $param2 = $matched[3];
            foreach ($replace as $i => $s) {
                if ($param2[$i] != '') {
                    $content = str_replace($s, '$this->_storeManager->getStore()->getBaseUrl(' . $this->_config->getRouterPath($param1[$i]) . ', ' . $param2[$i] . ')', $content);
                } else {
                    $content = str_replace($s, '$this->_storeManager->getStore()->getBaseUrl(' . $this->_config->getRouterPath($param1[$i]) . ')', $content);
                }
            }
        }

        /*if (preg_match_all('/Mage\s*\:\:\s*getUrl\s*\(\s*(["\']?.*?["\']?)\s*(,\s*(["\']?.*["\']?)\s*)?\)/', $content, $matched)) {
            $funcMatchs = $matched[0];
            $params1 = $matched[1];
            $params2 = $matched[3];
            foreach ($funcMatchs as $i => $func) {
                if ($params2[$i] != '') {
                    $content = str_replace($func, '$this->_storeManager->getStore()->getBaseUrl(' . $params1[$i] . ', ' . $params2[$i] . ')', $content);
                } else {
                    $content = str_replace($func, '$this->_storeManager->getStore()->getBaseUrl(' . $params1[$i] . ')', $content);
                }
            }
        }*/
        if(preg_match_all('#(Mage\s*\:\:\s*getUrl\s*\({1}\s*((?:.|\n|\s)*?)\s*(?:,\s*((?:.|\n|\s)*?)\s*)?\){1})\s*(?:\.|\$|->|;)#', $content, $matched)){
            $replace = $matched[1];
            $param1 = $matched[2];
            $param2 = $matched[3];
            foreach ($replace as $i => $s) {
                if($param2[$i] != '') {
                    $content = str_replace($s, '$this->_storeManager->getStore()->getBaseUrl('.$this->_config->getRouterPath($param1[$i]).', '.$param2[$i].')', $content);
                }else{
                    $content = str_replace($s, '$this->_storeManager->getStore()->getBaseUrl('.$this->_config->getRouterPath($param1[$i]).')', $content);
                }
            }
        }

        return $content;
    }

    //replace getUrl -> \Magento\Framework\UrlInterface $urlBuilder->getUrl
    public function replaceGetUrl($content){
        if(preg_match_all('#(Mage\s*\:\:\s*getUrl\s*\({1}\s*((?:.|\n|\s)*?)\s*(?:,\s*((?:.|\n|\s)*?)\s*)?\){1})\s*(?:\.|\$|->|;)#', $content, $matched)){
            $replace = $matched[1];
            $param1 = $matched[2];
            $param2 = $matched[3];
            foreach ($replace as $i => $s) {
                if($param2[$i] != '') {
                    $content = str_replace($s, '$this->_urlBilder->getUrl('.$this->_config->getRouterPath($param1[$i]).', '.$param2[$i].')', $content);
                }else{
                    $content = str_replace($s, '$this->_urlBilder->getUrl('.$this->_config->getRouterPath($param1[$i]).')', $content);
                }
            }
        }
        return $content;
    }

    //replace Mage::app()->getStore() to get store in magento 2 $this->_storeManager->getStore()
    public function replaceAppGetStore($content)
    {
        //to $this->_storeManager->getStore()
        //replace getStores
        if (preg_match_all('/Mage\s*::\s*app\s*\(\s*.*\s*\)\s*->getStores\s*\(\s*(["\']?.*?["\']?)\s*(,\s*(["\']?.*["\']?)\s*)?\)/', $content, $matched)) {
            $funcMatchs = $matched[0];
            $params1 = $matched[1];
            $params2 = $matched[3];
            foreach ($funcMatchs as $i => $func) {
                if ($params2[$i] != '') {
                    $content = str_replace($func, '$this->_storeManager->getStores(' . $params1[$i] . ', ' . $params2[$i] . ')', $content);
                } else {
                    $content = str_replace($func, '$this->_storeManager->getStores(' . $params1[$i] . ')', $content);
                }
            }
        }
        //replace getStore
        if (preg_match_all('/Mage\s*::\s*app\s*\(\s*.*\s*\)\s*->getStore\s*\(\s*(["\']?.*?["\']?)\s*\)/', $content, $matched)) {
            $funcMatchs = $matched[0];
            $params1 = $matched[1];
            foreach ($funcMatchs as $i => $func) {
                if ($params1[$i] != '') {
                    $content = str_replace($func, '$this->_storeManager->getStore(' . $params1[$i] . ')', $content);
                } else {
                    $content = str_replace($func, '$this->_storeManager->getStore()', $content);
                }
            }
        }

        return $content;
    }

    //Hai tran create -> Tit edit
    public function replaceVersion($content){
        //$content = str_replace('Mage::getVersion()', '\Magento\Framework\AppInterface::VERSION', $content);
        $content = preg_replace('#Mage\s*\s:\s:\s*getVersion\(\)#', '\Magento\Framework\AppInterface::VERSION', $content);
        return $content;
    }

    private function addObjectToConstruct($content, $objectName, $variable, $_prefix = false, $scope = 'protected')
    {
        $oldFunctionContentAll = $newFunctionContentAll = $content; //$this->_helper->getFunctionContentAll($content, '__construct');
        $tab = 8; //$this->getFunctionTab($content, '__construct');
        if ($oldFunctionContentAll) {
            $paramContent = $this->_helper->getFunctionParams($oldFunctionContentAll, '__construct');
            $functionContent = $this->_helper->getFunctionContent('<?php ' . $oldFunctionContentAll, '__construct');
            $newFunctionContentAll = str_replace($paramContent, PHP_EOL . $this->_helper->getNullStr($tab) . trim($paramContent) . ', ' . PHP_EOL . $this->_helper->getNullStr($tab) . $objectName . ' $' . $variable . PHP_EOL, $newFunctionContentAll);
            if($_prefix){
                $newFunctionContentAll = str_replace($functionContent, rtrim($functionContent) . PHP_EOL . $this->_helper->getNullStr($tab) . ' $this->_' . $variable . ' = $' . $variable . ';' . PHP_EOL, $newFunctionContentAll);
            }else{
                $newFunctionContentAll = str_replace($functionContent, rtrim($functionContent) . PHP_EOL . $this->_helper->getNullStr($tab) . ' $this->' . $variable . ' = $' . $variable . ';' . PHP_EOL, $newFunctionContentAll);
            }
            //add attribute to object allVariable
            if($_prefix){
                $this->_variables[] = $scope . ' $_' . $variable;
            }else{
                $this->_variables[] = $scope . ' $' . $variable;
            }
            //if(!$scope == '') $scope = 'protected';
            //$newFunctionContentAll = PHP_EOL.$this->_helper->getNullStr($tab).'/** '.$objectName.' */'.PHP_EOL.$this->_helper->getNullStr($tab).$scope.' $'.$variable.';'.PHP_EOL.$newFunctionContentAll;
            $content = str_replace($oldFunctionContentAll, $newFunctionContentAll, $content);
        }

        return $content;
    }

    //check is exist param in construct
    private function _isExistConstructParam($varType, $varName)
    {
        if (!isset($this->_functions['__construct'])) {
            $this->_functions['__construct'] = 'public function __construct(){}';
        }
        $paramsStr = $this->_helper->getFunctionParams($this->_functions['__construct'], '__construct');
        if (preg_match('/' . preg_quote($varType) . '\s+\$' . $varName . '/', $paramsStr)) {
            return TRUE;
        }

        return FALSE;
    }

    //check requiring objects in all functions and add them to construct function
    private function _checkObjectNeed()
    {
        $_storeManager = FALSE;
        $_scopeConfig = FALSE;
        $_objectManager = FALSE;
        $_urlBuilder = FALSE;
        $_eventManager = FALSE;
        $_registry = FALSE;
        foreach ($this->_functions as $name => $fcontent) {

            if (preg_match('/$this\s*->\s*_objectManager\s*->\s*(create|get)/', $fcontent)) {
                $_objectManager = TRUE;
            }

            if (preg_match('/$this\s*->\s*_storeManager\s*->\s*getStore(s?)/', $fcontent)) {
                $_storeManager = TRUE;
            }

            if(preg_match('/$this\s*->\s*_urlBuilder\s*->\s*getUrl\s*\(/', $fcontent)){
                $_urlBuilder = TRUE;
            }

            if (preg_match('/$this\s*->\s*_config/', $fcontent)) {
                $_scopeConfig = TRUE;
            }

            if (preg_match('/$this\s*->\s*_eventManager/', $fcontent)) {
                $_eventManager = TRUE;
            }

            if (preg_match('/$this\s*->\s*_registry/', $fcontent)) {
                $_registry = TRUE;
            }
        }

        /*check and add new object to construct function*/

        //\Magento\Framework\ObjectManagerInterface $objectManager
        if ($_objectManager && !$this->_isExistConstructParam('\Magento\Framework\ObjectManagerInterface', '_objectManager')) {
            $this->_functions['__construct'] = $this->addObjectToConstruct($this->_functions['__construct'], '\Magento\Framework\ObjectManagerInterface', '_objectManager');
        }

        //\Magento\Store\Model\StoreManagerInterface
        if ($_storeManager && !$this->_isExistConstructParam('\Magento\Store\Model\StoreManagerInterface', '_storeManager')) {
            $this->_functions['__construct'] = $this->addObjectToConstruct($this->_functions['__construct'], '\Magento\Store\Model\StoreManagerInterface', '_storeManager');
        }

        //\Magento\Framework\UrlInterface $urlBuilder
        if($_urlBuilder && !$this->_isExistConstructParam('\Magento\Framework\UrlInterface', 'urlBuilder')) {
            $this->_functions['__construct'] = $this->addObjectToConstruct($this->_functions['__construct'], '\Magento\Framework\UrlInterface', 'urlBuilder', true);
        }

        //\Magento\Framework\App\Config\ScopeConfigInterface
        if ($_scopeConfig && !$this->_isExistConstructParam('\Magento\Framework\App\Config\ScopeConfigInterface', '_scopeConfig')) {
            $this->_functions['__construct'] = $this->addObjectToConstruct($this->_functions['__construct'], '\Magento\Framework\App\Config\ScopeConfigInterface', '_scopeConfig');
        }

        //\Magento\Framework\Event\ManagerInterface
        if ($_eventManager && !$this->_isExistConstructParam('\Magento\Framework\Event\ManagerInterface', '_eventManager')) {
            $this->_functions['__construct'] = $this->addObjectToConstruct($this->_functions['__construct'], '\Magento\Framework\Event\ManagerInterface', '_eventManager');
        }

        //\Magento\Framework\Registry
        if ($_registry && !$this->_isExistConstructParam('\Magento\Framework\Registry', '_registry')) {
            $this->_functions['__construct'] = $this->addObjectToConstruct($this->_functions['__construct'], '\Magento\Framework\Registry', '_registry');
        }

        return $this;
    }

}