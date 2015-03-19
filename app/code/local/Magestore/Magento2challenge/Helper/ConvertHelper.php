<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Magestore_Magento2challenge_Helper_ConvertHelper extends Mage_Core_Helper_Abstract
{
    const OUTPUT_PATH = 'OUTPUT'; 
    const SAMPLE_PATH = 'lib/sample';
    public function createConstructContent($content,$parentClass,$className1x,$modulename){
        $constArray = array();
        $explodeClass = explode('_',$className1x);
        $funContent = 'public function __construct(';
        $funContent .= "\n\t".'\Magento\Framework\App\Helper\Context $context,';
        $funContent .= "\n\t".'\Magento\Framework\ObjectManagerInterface $objectManager,';
        $funContent .= "\n\t".'\Magento\Framework\Registry $registry,';
        $funContent .= "\n\t".'\Magento\Store\Model\StoreManagerInterface $storeManager,';
        $funContent .= "\n\t".'\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,';
        $funContent .= "\n\t".'\Magento\Framework\Event\ManagerInterface $eventManager,';
        preg_match_all('#Mage\:\:(getModel|getSingleton|getResourceModel)\s*\((.*?)\)#', $content, $matches);
        $modelPath = array();
        $count = 0;
        $trimArray = array();
        $trimArrayCp = array();
        $variablePointer = "\n";
        if(count($matches[2]) > 0){
            foreach($matches[2] as $value){
                $trimValue = trim($value,"'");
                if(strpos($trimValue,'(') !== false || strpos($trimValue,'$') !== false) continue;  
                if(count($explodeClass) < 3)
                    continue;
                if(strpos($trimValue,'getModel') === false && strpos($trimValue,'getSingleton') === false && strpos($trimValue,'getResourceModel') === false)
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
        }
        $funContent = rtrim($funContent,",");
        $funContent .= "\n\t ){";
        $funContent .= $variablePointer;
        $funContent .= "\n\t\$this->_objectManager = \$objectManager;";
        $funContent .= "\n\t\$this->_coreRegistry = \$registry;";
        $funContent .= "\n\t\$this->_storeManager = \$storeManager;";
        $funContent .= "\n\t\$this->_scopeConfig = \$scopeConfig;";
        $funContent .= "\n\t\$this->_eventManager = \$eventManager;";
        $funContent .= "\n\t parent::__construct(\$context); \n   }";
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
        $constArray['content'] = $content;
        $constArray['funContent'] = $funContent;
        return $constArray;
    }    
    public function writeContentHelperFile($file){
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
        $helperName = end($className1xEx);
        $allMethods = Mage::helper('magento2challenge/convertBlock')->getAllMethods($contentFile,$className1x);
        $i = 0;
        $parentClass = Mage::helper('magento2challenge')->getParentClassName($file);
        $constructFunContent = $this->createConstructContent($contentFile,$parentClass,$className1x,$modulename);
        $globalVariables = Mage::helper('magento2challenge/convertBlock')->getAllGlobalVariables($contentFile);
        $bodyContent = $globalVariables."\n";
        $contentFile = $constructFunContent['content'];
        $bodyContent = $constructFunContent['funContent']."\n";
        $allMethods = Mage::helper('magento2challenge/convertModel')->getAllFunctions($contentFile);
        foreach($allMethods as $method){
            if($i == 0)
                $bodyContent  .= $method."\n";
            else
                $bodyContent .= "    ".$method."\n";
            $i++;
        }
        $sampleHelper = self::SAMPLE_PATH.DS.'Block.php';
        $exClass = str_replace('_','\\',$parentClass);
        $exClass = str_replace('Mage_Adminhtml','Magento\\Backend',$exClass);
        $exClass = str_replace('Mage\_','Magento\\',$exClass);
        $newContent = Mage::helper('magento2challenge/convertBlock')->replaceBlockContent(file_get_contents($sampleHelper),$className2x,$helperName,$bodyContent,$exClass);
        $newContent = Mage::helper('magento2challenge')->translateText($newContent);
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