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
class Magestore_Magento2challenge_Helper_ConvertModel extends Mage_Core_Helper_Abstract
{
    protected  $_helperData;//helper/data

    public function __construct(){
        $this->_helperData = Mage::helper('magento2challenge');
    }

    public function getAllMethods($content, $className = ''){
        $methods = array();
        if($className){
            $classContent = $this->getContentInClass($content, $className);
            preg_match_all('/function\s+([A-z0-9_]*)\s*\(.*\)\s*\\n*{/',$classContent,$matched);
        }else{
            preg_match_all('/function\s+([A-z0-9_]*)\s*\(.*\)\s*\\n*{/',$content,$matched);
        }

        if(isset($matched[1])){
            $methods = $matched[1];
        }
        return $methods;
    }

    //extract class name from content
    public function extractClassName($content){
        if(preg_match('/(class|interface)\s+([A-z0-9_]+?)(\s+|\s*\{)/', $content, $matched)){
            return $matched[2];
        }
        return '';
        /*else{
            throw new Exception('Not found class name from file ', E_WARNING);
        }*/
    }

    public function getClassType($content){
        if(preg_match('/((abstract|static)?\s+class|interface)\s+([A-z0-9_]+?)(\s+|\s*\{)/', $content, $matched)){
            return $matched[1];
        }
        return '';
    }

    //get content in a class name
    //return string
    public function getContentInClass($content, $className){
        $allToken = token_get_all($content);
        $classContent = ''; //content in a class
        $is_in_class = false;
        $inGroup = 0;
        foreach($allToken as $tok){
            if(is_array($tok)){
                if($tok[1] == $className){
                    $is_in_class = true;
                }
            }
            if(is_string($tok) && $is_in_class){
                if($tok == '{') $inGroup++;
                else if($tok == '}') $inGroup--;
                //out of class and break loop when
                if( $tok == '}' && $inGroup == 0) {$is_in_class = false; break;}
            }
            //get content * when in class { * }
            if($is_in_class && $inGroup){
                if(is_array($tok)){
                    $classContent .= $tok[1];
                }
                else if(is_string($tok)){
                    $classContent .= $tok;
                }
            }
        }
        return $classContent;
    }

    //get all function from content <?php
    public function getAllFunctions($content){
        $tokens = token_get_all($content);
        $functions = array();
        $func = '';
        $functionName = '';
        $inClass = $inFunction = false;
        $inScope = $inFunctionName = $inParam = $inContent = $inTCurlyOpen = false;
        $inOther = $parenCount = 0;
        foreach($tokens as $token) {
            switch($token[0]) {
                case T_CLASS:
                    $inClass = true;
                    break;

                case T_PUBLIC:
                case T_PRIVATE:
                case T_PROTECTED:
                    if($inClass) {
                        $inScope = true;
                        $func .= $token[1];
                    }
                    break;

                case T_STATIC:
                    if($inClass && $inScope) {
                        $func .= $token[1];
                    }
                    break;

                case T_FUNCTION:
                    if($inClass && $inScope && $inOther <= 1) {
                        $inFunction = true;
                        $isNextStrIsFuncName = true;
                        $func .= $token[1];
                    }
                    break;

                case T_STRING:
                    if($inClass && $inFunction) {
                        $inFunctionName = true;
                        if($isNextStrIsFuncName){
                            $functionName = $token[1];
                            $isNextStrIsFuncName = false;
                        }
                        $func .= $token[1];
                    }
                    break;

                case ';':
                    if( $inClass && $inFunction && ($inParam || $inContent)) {
                        $func .= $token[0];
                    }else{
                        $func = '';
                        $inScope = $inFunctionName = $inParam = $inContent = false;
                        $inOther = 0;
                    }
                    break;

                // Anonymous functions
                case '(':
                    if( $inClass && $inFunction && !$inContent) {
                        $parenCount++;
                        $inParam = true;
                    }
                    if($inClass && $inFunction){
                        $func .= $token[0];
                    }
                    break;
                case ')':
                    if( $inClass && $inFunction && $inParam && !$inContent) {
                        $parenCount--;
                        if($parenCount === 0) $inParam = false;
                    }
                    if($inClass && $inFunction){
                        $func .= $token[0];
                    }
                    break;

                // Exclude Classes
                case T_CURLY_OPEN:
                    $inTCurlyOpen = true;
                case '{':
                    if( $inClass && $inFunction && !$inParam ) {
                        $parenCount++;
                        $inContent = true;
                        if($inTCurlyOpen){
                            $func .= $token[1];
                        }else{
                            $func .= $token[0];
                        }
                    }
                    break;

                case '}':
                    if( $inClass && $inFunction && $inContent && !$inParam ) {
                        $parenCount--;
                        $func .= $token[0];
                        $inTCurlyOpen = false;
                        if($parenCount === 0){
                            $inFunction = false;
                            $inContent = false;
                            $functions[$functionName] = trim($func);
                            $functionName = '';
                            $func = '';
                        }
                    }
                    break;

                //alway get T_WHITESPACE
                case '377':
                    if($inClass) $func .= $token[1];
                    break;

                case T_COMMENT:
                    //remove comment
                    break;

                default:
                    if($inClass) $inOther++;
                    if($inOther > 1 && !$inFunction) {
                        $func = '';
                        $inScope = false;
                        $inOther = 0;
                    }
                    if( $inClass && $inFunction && ($inParam || $inContent)) {
                        if(is_string($token)){
                            $func .= $token;
                        }else{
                            $func .= $token[1];
                        }
                    }

            }
        }

        return $functions;
    }

    //get all function content by name
    public function getFunctionContentAll($content, $funcName){
        /*$allFuncs = $this->getAllFunctions($content);
        if(isset($allFuncs[$funcName])){
            return $allFuncs[$funcName];
        }
        return '';*/
        $tokens = token_get_all($content);
        //$functions = array();
        $func = '';
        $functionName = '';
        $inClass = $inFunction = false;
        $inScope = $inFunctionName = $inParam = $inContent = $inTCurlyOpen = false;
        $inOther = $parenCount = 0;
        foreach($tokens as $token) {
            switch($token[0]) {
                case T_CLASS:
                    $inClass = true;
                    break;

                case T_PUBLIC:
                case T_PRIVATE:
                case T_PROTECTED:
                    if($inClass) {
                        $inScope = true;
                        $func .= $token[1];
                    }
                    break;

                case T_STATIC:
                    if($inClass && $inScope) {
                        $func .= $token[1];
                    }
                    break;

                case T_FUNCTION:
                    if($inClass && $inScope && $inOther <= 1) {
                        $inFunction = true;
                        $isNextStrIsFuncName = true;
                        $func .= $token[1];
                    }
                    break;

                case T_STRING:
                    if($inClass && $inFunction) {
                        $inFunctionName = true;
                        if($isNextStrIsFuncName){
                            $functionName = $token[1];
                            $isNextStrIsFuncName = false;
                        }
                        $func .= $token[1];
                    }
                    break;

                case ';':
                    if( $inClass && $inFunction && ($inParam || $inContent)) {
                        $func .= $token[0];
                    }else{
                        $func = '';
                        $inScope = $inFunctionName = $inParam = $inContent = false;
                        $inOther = 0;
                    }
                    break;

                // Anonymous functions
                case '(':
                    if( $inClass && $inFunction && !$inContent) {
                        $parenCount++;
                        $inParam = true;
                    }
                    if($inClass && $inFunction){
                        $func .= $token[0];
                    }
                    break;
                case ')':
                    if( $inClass && $inFunction && $inParam && !$inContent) {
                        $parenCount--;
                        if($parenCount === 0) $inParam = false;
                    }
                    if($inClass && $inFunction){
                        $func .= $token[0];
                    }
                    break;

                // Exclude Classes
                case T_CURLY_OPEN:
                    $inTCurlyOpen = true;
                case '{':
                    if( $inClass && $inFunction && !$inParam ) {
                        $parenCount++;
                        $inContent = true;
                        if($inTCurlyOpen){
                            $func .= $token[1];
                        }else{
                            $func .= $token[0];
                        }
                    }
                    break;

                case '}':
                    if( $inClass && $inFunction && $inContent && !$inParam ) {
                        $parenCount--;
                        $func .= $token[0];
                        $inTCurlyOpen = false;
                        if($parenCount === 0){
                            $inFunction = false;
                            $inContent = false;
                            //$functions[$functionName] = trim($func);
                            if($functionName == $funcName){
                                return $func; //return function content and finish loop
                            }
                            $functionName = ''; //reset func name to next new func name
                            $func = ''; //reset func content to next new func content
                        }
                    }
                    break;

                //alway get T_WHITESPACE
                case '377':
                    if($inClass) $func .= $token[1];
                    break;

                case T_COMMENT:
                    //remove comment
                    break;

                default:
                    if($inClass) $inOther++;
                    if($inOther > 1 && !$inFunction) {
                        $func = '';
                        $inScope = false;
                        $inOther = 0;
                    }
                    if( $inClass && $inFunction && ($inParam || $inContent)) {
                        if(is_string($token)){
                            $func .= $token;
                        }else{
                            $func .= $token[1];
                        }
                    }

            }
        }

        return ''; //fun finish and not found function name
    }

    public function getFunctionContent($content, $funcName){
        $allToken = token_get_all($content);
        $functionContent = ''; //content of function
        $is_in_function = false;
        $inGroup = 0;
        foreach($allToken as $tok){
            if(is_array($tok)){
                if($tok[1] == $funcName){
                    $is_in_function = true;
                }
            }
            if(is_string($tok) && $is_in_function){
                if($tok == '{') $inGroup++;
                else if($tok == '}') $inGroup--;
                //out of class and break loop when
                if( $tok == '}' && $inGroup == 0) {$is_in_function = false; break;}
            }
            //get content * when in class { * }
            if($is_in_function && $inGroup){
                if(is_array($tok)){
                    $functionContent .= $tok[1];
                }
                else if(is_string($tok)){
                    $functionContent .= $tok;
                }
            }
        }
        return substr($functionContent, 1);
    }

    //export params of a function
    //param: $name is function name
    public function getFunctionTab($content, $name){
        if(preg_match('/(\s*(public|protected|private)\s+(.*?\s+)?function\s+)'.$name.'\s*\(/', $content, $matched)){
            return strlen($matched[1]);
        }
        return 0;
    }

    //export params of a function
    //param: $name is function name
    public function getFunctionParams($content, $name){
        if(preg_match('/(public|protected|private)\s+(.*?\s+)?function\s+'.$name.'\s*\((.*?|(\s*.*[\\\].*\s*)*)\)/', $content, $matched)){
            return $matched[3];
        }
        return '';
    }

    //put string to head content of function
    //param: $functionText is all text of function
    //return string new function
    public function prependContentFunction($functionText, $content_add){
        $pos = strpos($functionText, '{');
        $preContent = substr($functionText, 0, $pos + 1);
        $midContent = preg_replace('/}\s*$/','',substr($functionText, $pos + 1));
        $content_add .= PHP_EOL.$midContent.PHP_EOL.'}';
        return $preContent.$content_add;
    }

    //put string to tail content of function
    //param: $functionText is all text of function
    //return string new function
    public function appendContentFunction($functionText, $content_add){
        if($functionText){
            $pos = strpos($functionText, '{');
            $preContent = substr($functionText, 0, $pos + 1);
            $midContent = preg_replace('/}\s*$/', '', substr($functionText, $pos + 1));
            if($preContent)
                $midContent .= PHP_EOL.rtrim($content_add, " \t\n\r\0\x0B").PHP_EOL.$this->getNullStr(4).'}';
            return $preContent.$midContent;
        }
        return $functionText;
    }

    //param: $content is content of all php code file
    //return: string content
    public function appendContentToConstruct($content, $addText){
        $contentFunction = $this->getFunctionContentAll($content, '__construct');
        $newContentFunction = $this->appendContentFunction($contentFunction, $addText);
        return str_replace($contentFunction, $newContentFunction, $content);
    }

    //get one line of code
    //return string
    public function searchLine($searchby, $content, $global = false, $inline = false){
        $allToken = token_get_all($content);
        $allMatched = array();
        $lineContent = $newLine = ''; //content of function
        $is_match = false;
        $is_end_line = false;
        foreach($allToken as $tok){
            if(is_string($tok) && ($tok == ';' || $tok == '{' || $tok == '}')){
                $is_end_line = true;
                $lineContent = $newLine.$tok;
                $newLine = '';
                continue;
            }
            if(is_array($tok)){
                if($inline){
                    if(preg_match('/<\?php|\s+|\\n/', $tok[1])){
                        continue;
                    }
                }else{
                    if(preg_match('/<\?php/', $tok[1])){
                        continue;
                    }
                }
                if($tok[1] == $searchby){
                    $is_match = true;
                }
            }
            //add new line content
            if(is_array($tok)){
                $newLine .= $tok[1];
            }else if(is_string($tok)){
                $newLine .= $tok;
            }
            if($is_end_line && $is_match){
                if($global){
                    $allMatched[] = $lineContent;
                    $lineContent = $newLine = '';
                    $is_match = false;
                    $is_end_line = false;
                }else{
                    break;
                }
            }else{
                $is_end_line = false;
            }
        }
        if($global){
            return $allMatched;
        }
        return $lineContent;
    }

    //replace Mage::getModel or getSingleton to new method
    /*public function replaceCreateModelMethod($content, $className = ''){
        $replaceContent = $beforeRepContent = '';
        if($className){
            $replaceContent = $beforeRepContent = $this->getContentInClass($content, $className);
        }
        preg_match_all('/Mage::(getModel|getSingleton)\s*\(\s*["\'](.*?)["\']\s*\)/', $replaceContent, $matches);
        if(!isset($matches[0])) return $content;
        $allFunctions = $matches[0];
        $allTextParams = $matches[2];
        $allMethodTypes = $matches[1];
        foreach($allFunctions as $index => $strFunc){
            $method = '';
            if($allMethodTypes[$index] == 'getModel'){
                $method = 'create';
            }elseif($allMethodTypes[$index] == 'getSingleton'){
                $method = 'get';
            }
            if($method){
                $newClassEntity = $this->getNewObjectType($allTextParams[$index], 'model'); //get new object type from old type
                $newFunctionContent = '$this->_objectManager->'.$method.'(\''.$newClassEntity.'\')';
                $replaceContent = str_replace($strFunc, $newFunctionContent, $replaceContent);
            }
        }
        $content = str_replace($beforeRepContent, $replaceContent, $content);
        return $content;
    }*/

    //replace old registry and register with new registry and new register
    //param: $content is string to replace on
    //return: string new content
//    public function replaceRegistry($content){
//        //get old registrys and replace
//        if(preg_match_all('/Mage\s*::\s*registry\s*\(\s*["\'](.*?)["\']\s*\)\s*/', $content, $matched)){
//            $registrys = $matched[0];
//            $registryKey = $matched[1];
//            foreach($registrys as $i => $reg){
//                $content = str_replace($reg, '$this->_registry->registry(\''.$registryKey[$i].'\')', $content);
//            }
//        }
//        //get old registers and replace
//        if(preg_match_all('/Mage\s*::\s*register\s*\(\s*["\'](.*?)["\']\s*,\s*(.*?)\s*\)\s*/', $content, $matched)){
//            $registers = $matched[0];
//            $registerKeys = $matched[1];
//            $registerDatas = $matched[2];
//            foreach($registers as $i => $reg){
//                $content = str_replace($reg, '$this->_registry->register(\''.$registerKeys[$i].'\', '.$registerDatas[$i].')', $content);
//            }
//        }
//        return $content;
//    }

    //Called by app/code/local/Magestore/Magento2challenge/Helper/ConvertBlock.php
    //Called by app/code/local/Magestore/Magento2challenge/Helper/ConvertHelper.php
    //replace an dispatch event from old event to new event
    public function replaceEvent($content){
        //find and replace Mage::dispatchEvent('catalog_block_product_status_display', array('status' => $statusInfo));
        if(preg_match_all('/Mage\s*::\s*dispatchEvent\s*\(\s*(["\']?.*?["\']?)\s*(,\s*(["\']?.*["\']?)\s*)?\)/', $content, $matched)){
            $funcMatchs = $matched[0];
            $params1 = $matched[1];
            $params2 = $matched[3];
            foreach($funcMatchs as $i => $func){
                if($params2[$i] != ''){
                    $content = str_replace($func, '$this->_eventManager->dispatch('.$params1[$i].', '.$params2[$i].')', $content);
                }else{
                    $content = str_replace($func, '$this->_eventManager->dispatch('.$params1[$i].')', $content);
                }
            }
        }
        return $content;
    }

    //called by app/code/local/Magestore/Magento2challenge/Helper/ConvertBlock.php
    //replace Mage::getBlockSingleton
    public function replaceBlockSingleton($content){
        if(preg_match_all('/Mage\s*::\s*getBlockSingleton\s*\(\s*["\'](.*?)["\']\s*\)/', $content, $matched)){
            $blocks = $matched[0];
            $blockNames = $matched[1];
            foreach($blocks as $i => $blk){
                $blockType = $this->_helperData->getClassNameFromConfigPath($blockNames[$i], 'block');
                $content = str_replace($blk, '$this->_objectManager->get(\''.$blockType.'\')', $content);
            }
        }
        return $content;
    }

    //called by app/code/local/Magestore/Magento2challenge/Helper/ConvertHelper.php
    //called by app/code/local/Magestore/Magento2challenge/Helper/ConvertBlock.php
    //replace Mage::helper
    public function replaceHelper($content){
        if(preg_match_all('/Mage\s*::\s*helper\s*\(\s*["\'](.*?)["\']\s*\)/', $content, $matched)){
            $helpers = $matched[0];
            $helperNames = $matched[1];
            foreach($helpers as $i => $hlp){
                $helperType = $this->_helperData->getClassNameFromConfigPath($helperNames[$i], 'helper');
                $content = str_replace($hlp, '$this->_objectManager->get(\''.$helperType.'\')', $content);
            }
        }
        return $content;
    }

    //called by app/code/local/Magestore/Magento2challenge/Helper/ConvertBlock.php
    //called by app/code/local/Magestore/Magento2challenge/Helper/ConvertHelper.php
    //replace Mage::helper
    public function replaceStoreConfig($content){
        //check is exist \Magento\Framework\App\Config\ScopeConfigInterface in construct
        $constructParam = $this->getFunctionParams($content, '__construct');
        if(!preg_match('/\\\Magento\\\Framework\\\App\\\Config\\\ScopeConfigInterface/', $constructParam)){
            $content = $this->addObjectToConstruct($content, '\Magento\Framework\App\Config\ScopeConfigInterface', 'scopeConfig');
        }
        if(preg_match_all('/Mage\s*::\s*getStoreConfig\s*\(\s*(["\']?.*?["\']?)\s*(,\s*(["\']?.*["\']?)\s*)?\)/', $content, $matched)){
            $funcMatchs = $matched[0];
            $params1 = $matched[1];
            $params2 = $matched[3];
            foreach($funcMatchs as $i => $func){
                //TODO: can Bien dich config path $params1
                if($params2[$i] != ''){
                    $content = str_replace($func, '$this->_config->getValue('.$params1[$i].', '.$params2[$i].')', $content);
                }else{
                    $content = str_replace($func, '$this->_config->getValue('.$params1[$i].')', $content);
                }
            }
        }
        return $content;
    }

    public function replaceGetBaseUrl($content){
        //check is exist \Magento\Store\Model\StoreManagerInterface in construct
        $constructParam = $this->getFunctionParams($content, '__construct');
        if(!preg_match('/\\\Magento\\\Store\\\Model\\\StoreManagerInterface/', $constructParam)){
            $content = $this->addObjectToConstruct($content, '\Magento\Store\Model\StoreManagerInterface', 'storeManager');
        }
        if(preg_match_all('/Mage\s*::\s*getBaseUrl\s*\(\s*(["\']?.*?["\']?)\s*(,\s*(["\']?.*["\']?)\s*)?\)/', $content, $matched)){
            $funcMatchs = $matched[0];
            $params1 = $matched[1];
            $params2 = $matched[3];
            foreach($funcMatchs as $i => $func){
                if($params2[$i] != ''){
                    $content = str_replace($func, '$this->_storeManager->getStore()->getBaseUrl('.$params1[$i].', '.$params2[$i].')', $content);
                }else{
                    $content = str_replace($func, '$this->_storeManager->getStore()->getBaseUrl('.$params1[$i].')', $content);
                }
            }
        }
        return $content;
    }

    //replace Mage::app()->getStore() to get store in magento 2 $this->_storeManager->getStore()
    public function replaceAppGetStore($content){
        //$this->_storeManager->getStore()
        //check is exist \Magento\Store\Model\StoreManagerInterface in construct
        $constructParam = $this->getFunctionParams($content, '__construct');
        if(!preg_match('/\\\Magento\\\Store\\\Model\\\StoreManagerInterface/', $constructParam)){
            $content = $this->addObjectToConstruct($content, '\Magento\Store\Model\StoreManagerInterface', 'storeManager');
        }
        //replace getStores
        if(preg_match_all('/Mage\s*::\s*app\s*\(\s*.*\s*\)\s*->getStores\s*\(\s*(["\']?.*?["\']?)\s*(,\s*(["\']?.*["\']?)\s*)?\)/', $content, $matched)){
            $funcMatchs = $matched[0];
            $params1 = $matched[1];
            $params2 = $matched[3];
            foreach($funcMatchs as $i => $func){
                if($params2[$i] != ''){
                    $content = str_replace($func, '$this->_storeManager->getStores('.$params1[$i].', '.$params2[$i].')', $content);
                }else{
                    $content = str_replace($func, '$this->_storeManager->getStores('.$params1[$i].')', $content);
                }
            }
        }
        //replace getStore
        if(preg_match_all('/Mage\s*::\s*app\s*\(\s*.*\s*\)\s*->getStore\s*\(\s*(["\']?.*?["\']?)\s*\)/', $content, $matched)){
            $funcMatchs = $matched[0];
            $params1 = $matched[1];
            foreach($funcMatchs as $i => $func){
                if($params1[$i] != ''){
                    $content = str_replace($func, '$this->_storeManager->getStore('.$params1[$i].')', $content);
                }else{
                    $content = str_replace($func, '$this->_storeManager->getStore()', $content);
                }
            }
        }
        return $content;
    }

    //get new object type
    //in this function will checkt with config to get new class name correctly where is that class file locate in
    //ex: from 'core/store' => check with config => get 'Magento\Store\Model\Store'
    //param $objectType is string of old object type
    //param $type (model | block | helper)
    //return string
    //Tit: can replace by function of Brian
    public function getNewObjectType($objectName, $type){
        $objPaths = explode('/', $objectName);
        if(count($objPaths)==1 && $objPaths[0] == '') return '';
        $moduleName = isset($objPaths[0])? $objPaths[0]:'';
        $pathName = isset($objPaths[1])? $objPaths[1]:'';
        switch($type){
            case 'model':
                $config = Mage::getModel('magento2challenge/config_objectTypeModel')->getConfig();
                $configName = isset($config[$moduleName])? $config[$moduleName]:$moduleName;
                //convert slash
                $folders = explode('_', $pathName);
                foreach ($folders as &$val) {
                    $val = ucfirst($val);
                }
                $nomalPath = 'Magento\\'.ucfirst($configName).'\\Model\\'.implode('\\', $folders);
                $idx = $this->findArrIdxMaxStrlen($nomalPath, $config);
                return str_replace($idx, $config[$idx], $nomalPath);
            case 'block':
                $config = Mage::getModel('magento2challenge/config_objectTypeBlock')->getConfig();
                $configName = isset($config[$moduleName])? $config[$moduleName]:$moduleName;
                //convert slash
                $folders = explode('_', $pathName);
                foreach ($folders as &$val) {
                    $val = ucfirst($val);
                }
                $nomalPath = 'Magento\\'.ucfirst($configName).'\\Block\\'.implode('\\', $folders);
                $idx = $this->findArrIdxMaxStrlen($nomalPath, $config);
                return str_replace($idx, $config[$idx], $nomalPath);
            case 'helper':
                $config = Mage::getModel('magento2challenge/config_objectTypeHelper')->getConfig();
                $configName = isset($config[$moduleName])? $config[$moduleName]:$moduleName;
                if($pathName == ''){
                    $pathName = 'Data';
                }
                //convert slash
                $folders = explode('_', $pathName);
                foreach ($folders as &$val) {
                    $val = ucfirst($val);
                }
                $nomalPath = 'Magento\\'.ucfirst($configName).'\\Helper\\'.implode('\\', $folders);
                $idx = $this->findArrIdxMaxStrlen($nomalPath, $config);
                if(!isset($config[$idx])) $config[$idx] = '';
                return str_replace($idx, $config[$idx], $nomalPath);
        }
        return '';
    }

    public function addObjectToConstruct($content, $objectName, $variable, $scope = 'protected'){
        $oldFunctionContentAll = $newFunctionContentAll = $this->getFunctionContentAll($content, '__construct');
        $tab = $this->getFunctionTab($content, '__construct');
        if($oldFunctionContentAll){
            $paramContent = $this->getFunctionParams($oldFunctionContentAll, '__construct');
            $functionContent = $this->getFunctionContent('<?php '.$oldFunctionContentAll, '__construct');
            $newFunctionContentAll = str_replace($paramContent, PHP_EOL.$this->getNullStr($tab).trim($paramContent) .', '.PHP_EOL.$this->getNullStr($tab).$objectName.' $'.$variable.PHP_EOL, $newFunctionContentAll);
            $newFunctionContentAll = str_replace($functionContent, rtrim($functionContent).PHP_EOL.$this->getNullStr($tab).' $this->_'.$variable.' = $'.$variable.';'.PHP_EOL, $newFunctionContentAll);
            //add attribute to object
            if(!$scope == '') $scope = 'protected';
            $newFunctionContentAll = PHP_EOL.$this->getNullStr($tab).'/** '.$objectName.' */'.PHP_EOL.$this->getNullStr($tab).$scope.' $_'.$variable.';'.PHP_EOL.$newFunctionContentAll;
            $content = str_replace($oldFunctionContentAll, $newFunctionContentAll, $content);
        }
        return $content;
    }

    //get null string by length
    public function getNullStr($length){
        $sNull = '';
        for($i=0; $i<$length; $i++) $sNull .= ' ';
        return $sNull;
    }

    //find and get index of array element have index key matched longest
    //return string array key
    public function findArrIdxMaxStrlen($searchString, $inArray){
        $strMatchLen = 0;
        $arrIdx = '';
        //zend_debug::dump($inArray);die;
        foreach($inArray as $key => $val){
            if(preg_match('/'.preg_quote($key).'/', $searchString, $matched)){
                if(strlen($matched[0]) > $strMatchLen){
                    $strMatchLen = strlen($matched[0]);
                    $arrIdx = $key;
                }
            }
        }
        return $arrIdx;
    }
    public function getMainTable($tablePath){
        if($tablePath == '') return null;
        list($module, $table) = explode('/', $tablePath);
        $modelConfig = $this->getModelPathInConfig();
        if(isset($modelConfig['entities'][$table])){
            return $modelConfig['entities'][$table]['table'];
        }
        return null;
    }
    public function getResourceClass(){
        $modelConfig = $this->getModelPathInConfig();
        if(isset($modelConfig['class'])){
            return $modelConfig['class'];
        }
        return null;
    }
    public function getModelPathInConfig(){
        $moduleData = Mage::helper('magento2challenge')->getModuleData();
        if(array_key_exists('config', $moduleData)){
            $configData = $moduleData['config'];
            $modelData = $configData['global']['models'];
            if(isset($modelData[$moduleData['lower_module_name']]['resourceModel'])){
                $resourcePath = $modelData[$moduleData['lower_module_name']]['resourceModel'];
                return $modelData[$resourcePath];
            }
        }
        return null;
    }

}