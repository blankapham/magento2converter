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
class Magestore_Magento2challenge_Model_ConvertCollection extends Mage_Core_Model_Abstract
{
    protected $_helper;

    //temp var
    private $_file;
    private $_moduleName;
    private $_namespace;
    private $_newNamespace;
    private $_resourceFolder;
    //private $_maintable;
    private $_content;
    private $_newContent;
    private $_construct;
    private $_newConstruct;
    private $_classType; //abstract or interface ...
    private $_oldClassName;
    private $_newClassName;
    private $_oldExtendsClass;
    private $_newExtendsClass;
    //private $_classContent;
    //private $_newClassContent;

    private $_use = array(); //use lines
    private $_allVars = array();
    private $_allFunctions = array();

    public function _construct()
    {
        $this->_helper = Mage::helper('magento2challenge/convertModel');
        parent::_construct();
        $this->_init('magento2challenge/convertCollection');
    }

    //use to convert model file magento 1 to model file magento 2
    public function convert($file, $resourceFolder){
        //check file exist
        if(!file_exists($file)){
            throw new Exception('file does not exist', E_WARNING);
        }
        $this->_resourceFolder = $resourceFolder;
        //$this->_maintable = $maintable;
        $this->_initFile($file);
        $this->_convertInclude();
        $this->_convertConstruct();
        $this->_compile();

        $tab = 4;
        //merge new content
        $this->_newContent = '<?php'.PHP_EOL;
        //add new namespace
        $this->_newContent .= 'namespace '.$this->_newNamespace.';'.PHP_EOL.PHP_EOL.PHP_EOL;
        //add use line
        foreach($this->_use as $line){
            $this->_newContent .= $line.PHP_EOL;
        }
        //add class
        if($this->_classType){
            $this->_newContent .= $this->_classType .' ';
        }
        $this->_newContent .= $this->_newClassName;
        //add extends if exist
        if($this->_newExtendsClass){
            $this->_newContent .= ' extends '.$this->_newExtendsClass;
        }
        //add open tag {
        $this->_newContent .= ' {'.PHP_EOL;
        //add variables
        foreach($this->_allVars as $var){
            $this->_newContent .= $this->_tab($tab).$var.PHP_EOL;
        }
        $this->_newContent .= PHP_EOL.PHP_EOL.$this->_tab($tab).'/**---Functions---*/'.PHP_EOL;
        //add functions
        foreach($this->_allFunctions as $func){
            $this->_newContent .= $this->_tab($tab).$func.PHP_EOL.PHP_EOL.PHP_EOL;
        }
        //close class by tag }
        $this->_newContent .= '}'.PHP_EOL;

        //write content to file
        $path = 'app'.DS.'code'.DS.$this->_namespace.DS.$this->_moduleName.DS.'Model'.DS.'Resource';
        $file = str_replace('\\', '/', $file);
        if(preg_match('#app\/code\/.+?\/.+?\/.+?\/Model\/.*?'.$resourceFolder.'(.*\.php)$#', $file, $matched))
        {
            $path .= str_replace('/', DS, $matched[1]);
            Mage::helper('magento2challenge')->writeContentToFile($path, $this->_newContent);
        }

        return $this->_newContent;
    }

    private function _initFile($file){
        $this->_file = $file;
        $this->_content = file_get_contents($file);
        $this->_moduleName = $this->_getModuleName();
        $this->_namespace = $this->_getNamespace();
        $this->_newNamespace = $this->_getMagento2Namespace();
        $this->_classType = $this->_getClassType();
        $this->_oldClassName = $this->_extractClassName($this->_content);
        $this->_newClassName = $this->_getNewClassName($this->_oldClassName);//$this->_extractClassName($this->_newContent);
        $this->_oldExtendsClass = $this->_extractExtendsClass($this->_content);
        $this->_newExtendsClass = $this->_getNewExtends();
        $this->_construct = $this->_helper->getFunctionContentAll($this->_content, '__construct');
        $this->_newConstruct = Mage::getModel('magento2challenge/config_construct')
            ->getConstruct($this->_newExtendsClass,
                           $this->_newNamespace,
                           $this->_newClassName);
        $this->_getAllVars(); //init all vars
        $this->_getAllFunctions();
        return $this;
    }

    private function _compile(){
        $compiler = Mage::getModel('magento2challenge/compiler');
        $compiler->setContent($this->_content);
        $compiler->setConstruct($this->_construct);
        $compiler->setMaintable($this->_maintable);
        $compiler->setNewConstruct($this->_newConstruct);
        $compiler->setFunctions($this->_allFunctions);
        $compiler->setVariables($this->_allVars);
        $this->_allFunctions = $compiler->run();
        $this->_allVars = $compiler->getVariables();
    }

    private function _convertInclude(){
        //convert include code to "use" code
        $includes = $this->findCodeIncludedFiles($this->_content);
        foreach($includes as $incSyntaxStr){
            $this->_use[] = $this->convertIncludeFile($incSyntaxStr);
        }
        return $this;
    }

    private function _convertConstruct(){
        if(!isset($this->_allFunctions['__construct']) || $this->_allFunctions['__construct'] == ''){
            $this->_allFunctions['__construct'] = preg_replace('/(\(|\{|;)\s/', '$1'.PHP_EOL, $this->_newConstruct);
            $this->_allFunctions['__construct'] = preg_replace('/(,|\))\s{4}/', '$1'.PHP_EOL, $this->_allFunctions['__construct']);
        }
        //get extra content in old __construct
        $_construct_content = '';
        if($this->_construct){
            $_construct_content = $this->_helper->getFunctionContent('<?php '.$this->_construct, '__construct');
            //remove old parent::__construct
            $_construct_content = preg_replace('/parent\s*::\s*__construct\s*.*?\)\s*;/', '', $_construct_content);
            //remove _init function call
            $_init = $this->_helper->searchLine('_init', '<?php '.$_construct_content);
            $_construct_content = str_replace($_init, '', $_construct_content);
        }

        //get extra content in old _construct
        if(isset($this->_allFunctions['_construct'])){
            //remove _init function call
            $_init = $this->_helper->searchLine('_init', '<?php '.$this->_allFunctions['_construct']);
            //recreate _init function call
            $_newInit = '$this->_init(\''.$this->_getModelClass().'\', \''.$this->_getResourceClass().'\');';
            $this->_allFunctions['_construct'] = str_replace($_init, $_newInit, $this->_allFunctions['_construct']);

        }

        $this->_allFunctions['__construct'] = $this->_helper->appendContentFunction($this->_allFunctions['__construct'], $_construct_content);

        return $this;
    }

    //get model class name
    private function _getModelClass(){
        $file = str_replace('\\', '/', $this->_file);
        $class = '\\'.ucfirst($this->_namespace).'\\'.ucfirst($this->_moduleName).'\\'.'Model\\';
        if(preg_match('#app/code/.*?/.*?/.*?/Model/.*?('.$this->_resourceFolder.')(.*)\/(Collection)\.php$#', $file, $matched)){
            $class .= str_replace('/', '\\', trim($matched[2], '/'));
            return $class;
        }
        return '';
    }

    //get resource class name
    private function _getResourceClass(){
        $file = str_replace('\\', '/', $this->_file);
        $class = '\\'.ucfirst($this->_namespace).'\\'.ucfirst($this->_moduleName).'\\'.'Model\\';
        if(preg_match('#app/code/.*?/.*?/.*?/Model/.*?('.$this->_resourceFolder.')(.*)\/(Collection)\.php$#', $file, $matched)){
            $class .= str_replace('/', '\\', 'Resource\\'.trim($matched[2], '/'));
            return $class;
        }
        return '';
    }

    private function _getAllVars(){
        if(!$this->_allVars){
            if(preg_match_all('/((public)|(private)|(protected))\s+(static)?\s*(.*)\s*;/', $this->_content, $matched)){
                $this->_allVars = $matched[0];
            }
        }
        return $this->_allVars;
    }

    private function _getAllFunctions(){
        $allMethods = $this->_helper->getAllMethods($this->_content);
        foreach($allMethods as $method){
            $this->_allFunctions[$method] = $this->_helper->getFunctionContentAll($this->_content, $method);
        }
    }

    public function getModuleName(){
        return $this->_getModuleName();
    }

    private function _getClassType(){
        return $this->_helper->getClassType($this->_content);
    }

    //old class name is in correctly when it never translated ton new class name
    private function _extractClassName($content){
        return $this->_helper->extractClassName($content);
    }

    private function _extractExtendsClass($content){
        $extendsClass = '';
        if(preg_match('/(extends\s+([A-z0-9_]+?)\s*)\{/', $content, $matched)){
            $extendsClass = $matched[2];
        }
        return $extendsClass;
    }

    private function _getOldClassName(){
        if(!$this->_oldClassName){
            $this->_oldClassName = $this->_extractClassName($this->_content);
        }
        return $this->_oldClassName;
    }

    private function _getNewClassName($_oldClassName){
        if(preg_match('/[^_]*$/', $_oldClassName, $matched)){
            return $matched[0];
        }else{
            return '';
        }
    }

    private function _getOldExtends(){
        if(!$this->_oldExtendsClass){
            $this->_oldExtendsClass = $this->_extractExtendsClass($this->_content);
        }
        return $this->_oldExtendsClass;
    }

    private function _getNewExtends(){
        return Mage::getModel('magento2challenge/config_extends')->getExtendsClass(
            $this->_getOldExtends());
    }

    public function getNewNamespace(){
        if(!$this->_newNamespace){
            if(!$this->_getMagento2Namespace()){
                throw new Exception("No magento 2 namespace", E_WARNING);
            }
        }
        return $this->_newNamespace;
    }

    private function _getMagento2Namespace(){
        $dirName = dirname($this->_file);
        $path = str_replace('\\', '/', $dirName);
        $path = trim($path, '/');
        //$path = substr($path, 0, -4);
        //remove real path
        $path = preg_replace('/^.*\/app\/code\/.+?\//', '', $path);
        $pArray = explode('/', $path);
        foreach($pArray as $key => $p){
            $pArray[$key] = ucfirst($p);
        }
        $pArray[3] = ucfirst('Resource'); //replace Mysql4 by Resource
        return implode('\\', $pArray);

        /*$path = str_replace('_', '/', $this->_oldClassName);
        $fullNspace = $this->convertPathToClassSpace($path.'.php');
        preg_match('/^(.*?)\\\\'.$this->getMagento2ClassName($className).'$/', $fullNspace, $matched);
        return $matched[1];

        $namespace = $this->getMagento2Namespace($this->_getOldClassName($this->_content));*/
    }

    //detect model file infomations from $path
    //return array('namespace','modulename','modelpath')
    public function detectModelFile($path){
        $path = str_replace('\\','/',$path);
        if(preg_match('/.+\/app\/code\/(.+)\/(.+)\/(.+)/',$path, $matcheds)){
            return array($matcheds[1], $matcheds[2], $matcheds[3]);
        }
        return array();
    }

    //detect and return namespace or extension converting
    private function _getNamespace(){
        $path = str_replace('\\','/', $this->_file);
        if(preg_match('/.+\/app\/code\/.+?\/(.+?)\/.+/', $path, $matcheds)){
            $this->_namespace = $matcheds[1];
        }
        return $this->_namespace;
    }

    private function _getModuleName(){
        $path = str_replace('\\','/', $this->_file);
        preg_match('/.+\/app\/code\/.+?\/.+?\/(.+?)\/.+/', $path, $matcheds);
        if($matcheds[1]){
            $this->_moduleName = $matcheds[1];
        }
        return $this->_moduleName;
    }


    //input may be have .php ext
    //convert from path file to class path for magento 2
    //ex: */app/code/*/Namespace/ExtensionName/Model/fileName.php to Namespace\ExtensionName\Model\FileName
    public function convertIncludePath($path){
        $path = str_replace('\\','/',$path);
        $path = trim($path,'/');
        $path = substr($path,0,-4);
        //remove real path
        $path = preg_replace('/^.*\/app\/code\/.+?\//','',$path);
        $pArray = explode('/',$path);
        foreach($pArray as $key => $p){
            $pArray[$key] = ucfirst($p);
        }
        return implode('\\', $pArray);
    }

    //convert include, require, include_one, require_one syntax to php5 syntax
    //ex: include ("magento1.9/app/code/local/Magestore/Convertext/Model/Convertext.php" to use Magestore\Convertext\Model\Convertext;
    private function convertIncludeFile($syntaxString){
        preg_match('/^\s*(inc.*?|req.*?)\s*\(?\s*["\'](.*?)["\']\s*\)?.*?$/', $syntaxString, $matcheds);
        $func_name = trim($matcheds[1]);
        $include_file = trim($matcheds[2]);

        switch($func_name){
            case 'include':
            case 'include_once':
            case 'require':
            case 'require_once':
                $class = $this->convertIncludePath($include_file);
                if($class != ''){
                    return 'use ' . $class . ';';
                }
                return '';
        }
    }

    //find includes in all code
    //return array includeds
    public function findCodeIncludedFiles($codeText){
        preg_match_all('/(inc.*?|req.*?)\s*\(?\s*["\'].*?["\']\s*\)?\s*?;/', $codeText, $matcheds);
        return $matcheds[0];
    }

    //get namespace of file
    public function getMagento2Namespace(){
        return $this->_getMagento2Namespace();
    }


    private function _tab($n){
        return $this->_helper->getNullStr($n);
    }
}