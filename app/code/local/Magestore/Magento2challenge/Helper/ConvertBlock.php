<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Magestore_Magento2challenge_Helper_ConvertBlock extends Mage_Core_Helper_Abstract
{
    const OUTPUT_PATH = 'OUTPUT'; 
    const SAMPLE_PATH = 'lib/sample';
    public function getAllGlobalVariables($contentFile){
        preg_match("/class([^`]*?)(public function | protected function | public static function)/", $contentFile, $matches);
        if(!isset($matches[1])) return '';
        $globalVariables = explode('{',$matches[1]);
        return $globalVariables[1];
    }
    public function getAllMethods($content,$className = null){  // function name
        preg_match_all('#(public|private|static|protected|\s) function (.+?)\(#', $content,$matches);
        return $matches[2];
    }
    function replaceBlockContent($content,$className,$blockName,$bodyContent,$exClass)
    {  // replace content of function from magento 1.x to magento 2.x
        if($exClass == "Magento\Backend\Block\Widget\Grid")
            $exClass = "Magento\Backend\Block\Widget\Grid\Extended";
        if($exClass == "Magento\Backend\Block\Widget\Form")
            $exClass = "Magento\Backend\Block\Widget\Form\Generic";
        if($exClass == "Mage\Core\Helper\Abstract"){
            $exClass = "Magento\Framework\App\Helper\AbstractHelper" ;  
        }
        $search = array(
            '/<ClassName>/',
            '/<BlockName>/',
            '/<BodyContent>/',
            '/<ExtendsClass>/',
               );

        $replace = array(
            $className,
            $blockName,
            $bodyContent,
            '\\'.$exClass,
            );
        //
        return preg_replace($search, $replace, $content);
    }
    public function replaceConstruct($content){
        $content = str_replace('__construct()','_construct()',$content);
        return $content;
    }
    public function getMainModelGrid($content){
        $bodyContentPrepareLayoutMethod = Mage::helper('magento2challenge/convertModel')->getFunctionContentAll($content,'_prepareCollection');
        preg_match_all('#Mage\:\:(getModel|getSingleton)\s*\((.*?)\)#', $bodyContentPrepareLayoutMethod, $matches);
        return $matches[2];
    }
    public function replaceEditForm($content){
        $content = str_replace('new Varien_Data_Form()','$this->_formFactory->create()',$content);
        $content = str_replace("Mage::registry","\$this->_coreRegistry->registry",$content);
        $content = str_replace("Mage::register","\$this->_coreRegistry->register",$content);
        return $content;
    }
    public function createConstructContent($content,$parentClass,$className1x){
        $constArray = array();
        $explodeClass = explode('_',$className1x);
        $mainModels = $this->getMainModelGrid($content);
        $funContent = 'public function __construct(';
        $funContent .= "\n\t".'\Magento\Backend\Block\Template\Context $context,';
        $funContent .= "\n\t".'\Magento\Framework\ObjectManagerInterface $objectManager,';
        if($parentClass == 'Mage_Adminhtml_Block_Widget_Grid'){
            $funContent .= "\n\t".'\Magento\Backend\Helper\Data $backendHelper,';
        }
        else if($parentClass == 'Mage_Adminhtml_Block_Widget_Form'){
            $funContent .= "\n\t".'\Magento\Framework\Registry $registry,';
            $funContent .= "\n\t".'\Magento\Framework\Data\FormFactory $formFactory,';
        }
        $funContent .= "\n\t".'\Magento\Framework\Registry $registry,';
        $funContent .= "\n\t".'\Magento\Store\Model\StoreManagerInterface $storeManager,';
        $funContent .= "\n\t".'\Magento\Framework\Event\ManagerInterface $eventManager,';
        $funContent .= "\n\t".'\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,';
        $content = $this->replaceEditForm($content);
        preg_match_all('#Mage\:\:(getModel|getSingleton|getResourceModel)\s*\((.*?)\)#', $content, $matches);
        $modelPath = array();
        $count = 0;
        $trimArray = array();
        $variablePointer = "\n";
        $trimArrayCp = array();
        if(count($matches[2]) > 0){
             foreach($matches[2] as $value){
                $trimValue = trim($value,"'");
                if(strpos($trimValue,'(') !== false) continue;    
                if(count($explodeClass) < 3)
                    continue;
                if(strpos($trimValue,'getModel') === false && strpos($trimValue,'getSingleton') === false &&  strpos($trimValue,'getResourceModel') === false)
                    $modelObj_1x = Mage::helper('magento2challenge')->getClassNameFromConfigPath($trimValue, 'model');    
                else
                    continue;
               // } 
                if(strpos($modelObj_1x,'Mage_Adminhtml') !== false)	$modelObj_1x = str_replace('Mage_Adminhtml','Magento_Backend',$modelObj_1x);
                if(strpos($modelObj_1x,'Mage_') !== false)	$modelObj_1x = str_replace('Mage_','Magento_',$modelObj_1x);
                $modelObj_1xEx = explode('\\',$modelObj_1x);
                if(!isset($modelObj_1xEx[1])) continue;
                $variable = "$".  strtolower($modelObj_1xEx[1]).strtolower(end($modelObj_1xEx));
                if(!in_array($trimValue,$trimArray) && $modelObj_1x){
                        $funContent .= "\n\t\\".str_replace('_','\\',$modelObj_1x).'Factory '.$variable.'Factory,';
                        $funContent .= "\n\t\\".str_replace('_','\\',$modelObj_1x).' '.$variable.',';
                }
                $trimArray[] = $trimValue;
                $trimValue = str_replace('_','/',$trimValue);
                $trimExplode = explode('/',$trimValue);
                    if(!isset($trimExplode[1]))
                        continue;
                    $modelName = end($trimExplode);
                    $variableObj = "$".strtolower($modelObj_1xEx[1]).strtolower($modelName);	
                    if(strpos($trimValue,'getModel') === false && strpos($trimValue,'getSingleton') === false){	
                            $strClass = 'Mage::getModel('.$value.')';  // magento 1.x
                            $strClassSpace = 'Mage::getModel ('.$value.')';  // magento 1.x
                            $strClassSingleton = 'Mage::getSingleton('.$value.')';  // magento 1.x
                            $strClassResource = 'Mage::getResourceModel('.$value.')';  // magento 1.x
                    }
                    // replace getModel/getSingleton in block grid
                        $newObjectModel = "\$this->_".strtolower($modelObj_1xEx[1]).$modelName."Factory->create()";
                        $newObject = "\$this->_".strtolower($modelObj_1xEx[1]).$modelName;
                        if($strClass){
                                $content = str_replace($strClass.'->getCollection()',$newObjectModel.'->getCollection()',$content);
                                $content = str_replace($strClass.' ->getCollection()',$newObjectModel.'->getCollection()',$content);
                                $content = str_replace($strClass,$newObject,$content);
                        }
                        if($strClassSingleton){	
                                $content = str_replace($strClassSingleton.'->getCollection()',$newObjectModel.'->getCollection()',$content);
                                $content = str_replace($strClassSingleton.' ->getCollection()',$newObjectModel.'->getCollection()',$content);
                                $content = str_replace($strClassSingleton,$newObject,$content);
                        }
                        if($strClassSpace){
                                $content = str_replace($strClassSpace.'->getCollection()',$newObjectModel.'->getCollection()',$content);
                                $content = str_replace($strClassSpace.' ->getCollection()',$newObjectModel.'->getCollection()',$content);
                                $content = str_replace($strClassSpace,$newObject,$content);
                        }
                        if($strClassResource){
                                $content = str_replace($strClassResource,$newObject,$content);
                        }
                    //
                    if(!in_array($trimValue,$trimArrayCp) && $modelName){
                            $variablePointer .= "\t\$this->_".strtolower($modelObj_1xEx[1]).$modelName."Factory = ".$variableObj."Factory;\n";
                            $variablePointer .= "\t\$this->_".strtolower($modelObj_1xEx[1]).$modelName." = ".$variableObj.";\n";
                    }
                    $trimArrayCp[] = $trimValue;
            }
           // die;
        }
            $funContent .= "\n\t array \$data = []\n    ){";  
            $funContent .= $variablePointer;
            $funContent .= "\n\t\$this->_objectManager = \$objectManager;";
            $funContent .= "\n\t\$this->_coreRegistry = \$registry;";
            $funContent .= "\n\t\$this->_storeManager = \$storeManager;";
            $funContent .= "\n\t\$this->_eventManager = \$eventManager;";
            $funContent .= "\n\t\$this->_scopeConfig = \$scopeConfig;";
            if($parentClass == 'Mage_Adminhtml_Block_Widget_Grid')
                    $funContent .= "\n\t parent::__construct(\$context, \$backendHelper, \$data); \n   }";
            else if($parentClass == 'Mage_Adminhtml_Block_Widget_Form')
                    $funContent .= "\n\t parent::__construct(\$context, \$registry, \$formFactory,\$data); \n   }";
            else{
                    $funContent .= "\n\t parent::__construct(\$context,\$data); \n   }";
            }
             // replace resource model
            foreach($matches[0] as $match){
                if(strpos($match,'getResourceModel') !== false){
                   if(preg_match_all('#Mage\:\:(getResourceModel)\s*\((.*?)\)#s', $match, $splitMatch)){
                       $paramResourceModel = trim($splitMatch[2][0],"'");
                       if(strpos($paramResourceModel,'getResourceModel') === false){
                           $modelClassResource = Mage::helper('magento2challenge')->getClassNameFromConfigPath($paramResourceModel, 'model_resource');
                           $realModel = str_replace('\\Resource\\','\\',$modelClassResource);
                           $realModelFactory = $realModel.'Factory';
                           $funContent = str_replace($realModelFactory,'special_model_magento2',$funContent);
                           $funContent = str_replace($realModel,$modelClassResource,$funContent);
                           $funContent = str_replace('special_model_magento2',$realModelFactory,$funContent);
                       }
                   }
                }
            }
            //
            if($parentClass == 'Mage_Adminhtml_Block_Widget_Grid_Container' || $parentClass == 'Mage_Adminhtml_Block_Widget_Form_Container' || $parentClass == 'Mage_Adminhtml_Block_Widget_Form'){
                $funContent = '';
                if(preg_match_all('#\$this->_blockGroup(.*?)\;#s', $content, $match)){
                    $oldString = $match[1][0];
                    $newString = " = '".$explodeClass[0]."_".$explodeClass[1]."'";
                    $content = str_replace($oldString,$newString,$content);
                }
            }    
            $constArray['content'] = $content;
            $constArray['funContent'] = $funContent;
            return $constArray;
    }
    public function containConstructFunction(){
            $classNames = array(
                    0 => 'Mage_Adminhtml_Block_Widget_Form',
                    1 => 'Mage_Adminhtml_Block_Widget_Grid_Container',
            );
            return $classNames;
    }
    public function additionMethodGrid(){
        $additionMethos = 
        "\n"."    public function getTabLabel()
        {
                return __('Item Information');
        }

        public function getTabTitle()
        {
                return __('Item Information');
        }

        public function canShowTab()
        {
                return true;
        }

        public function isHidden()
        {
                return false;
        }
        protected function _isAllowedAction(\$resourceId)
        {
                return \$this->_authorization->isAllowed(\$resourceId);
        }";
        return $additionMethos;
    }
    public function writeContentBlockFile($file){
        $contentFile = file_get_contents($file);
        $className1x = Mage::helper('magento2challenge')->getClassName($file);
        $className1xEx = explode('_',$className1x);
        $namespace = $className1xEx[0];
        $modulename = $className1xEx[1];
        $className2x = '';
        for($i = 0; $i < (count($className1xEx) -1);$i++){
            if($i < (count($className1xEx) -2))
                $className2x .= $className1xEx[$i].DS;
            else
                $className2x .= $className1xEx[$i];
        }
        $blockName = end($className1xEx);
        $i = 0;
        $parentClass = Mage::helper('magento2challenge')->getParentClassName($file);
        $constructFunContent = $this->createConstructContent($contentFile,$parentClass,$className1x);
        $globalVariables = $this->getAllGlobalVariables($contentFile);
        $bodyContent = $globalVariables."\n";
        $contentFile = $constructFunContent['content'];
        $bodyContent .= $constructFunContent['funContent']."\n";
        $allMethods = Mage::helper('magento2challenge/convertModel')->getAllFunctions($contentFile);
        foreach($allMethods as $method){
            if($i == 0)
                $bodyContent  .= $method."\n";
            else
                $bodyContent .= "    ".$method."\n";
            $i++;
        }
        if($parentClass == 'Mage_Adminhtml_Block_Widget_Grid')
            $bodyContent = $bodyContent.$this->additionMethodGrid();
        $bodyContent = preg_replace('/\t/', '  ', $bodyContent);
        $sampleBlock = self::SAMPLE_PATH.DS.'Block.php';
        $exClass = str_replace('_','\\',$parentClass);
        $exClass = str_replace('Mage\\Adminhtml','Magento\\Backend',$exClass);
        $exClass = str_replace('Mage\\','Magento\\',$exClass);
        $newContent = $this->replaceBlockContent(file_get_contents($sampleBlock),$className2x,$blockName,$bodyContent,$exClass);
        $newContent = Mage::helper('magento2challenge')->translateText($newContent);
        $newContent = $this->replaceConstruct($newContent);
        $newContent = Mage::helper('magento2challenge')->run($newContent);
        $newContent = Mage::helper('magento2challenge/convertController')->replaceCreateBlock($newContent, $namespace, $modulename,'other');
        $newContent = Mage::helper('magento2challenge/convertController')->replaceLoadRenderLayout($newContent);  
        //write file
        $handle = fopen ($file, 'w');
         fputs($handle, $newContent);    
        fclose($handle);
        //
    }
}