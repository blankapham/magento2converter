<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Magestore_Magento2challenge_Helper_ConvertController extends Mage_Core_Helper_Abstract
{
    const OUTPUT_PATH = 'OUTPUT'; 
    const SAMPLE_PATH = 'lib/sample';
    public function getFunctionsName($content,$className = null){  // function name of controller
       return Mage::helper('magento2challenge/convertBlock')->getAllMethods($content);
    }
    public function replaceMessage($filePath){   // replace success/error message from magento1.x to magento 2.x
            $content = file_get_contents($filePath);
            $content = str_replace("Mage::getSingleton('adminhtml/session')->addSuccess","\$this->messageManager->addSuccess",$content);
            $content = str_replace("Mage::getSingleton('adminhtml/session')->addError","\$this->messageManager->addError",$content);
            $content = str_replace("\$this->_getSession()->addSuccess","\$this->messageManager->addSuccess",$content);
            $content = str_replace("\$this->_getSession()->addError","\$this->messageManager->addError",$content);
            $content = str_replace("now()","date('y-m-d h:i:s')",$content);
            return $content;
    }
    public function replaceRegister($content){ // replace register/registry from magento1.x to magento 2.x
            $content = str_replace("Mage::register","\$this->_objectManager->get('Magento\Framework\Registry')->register",$content);
            $content = str_replace("Mage::registry","\$this->_objectManager->get('Magento\Framework\Registry')->registry",$content);
            return $content;
    }
    public function removeAddTabsContent($content){  //remove add tab content
        $searchfor = array();
        $searchfor[] = "\$this->getLayout()->getBlock('head')";
//        $searchfor[] = "\$this->_addContent(\$this->getLayout()->createBlock(";
//        $searchfor[] = "->_addLeft(\$this->getLayout()->createBlock(";
        for($i = 0; $i < count($searchfor);$i++){
            $pattern = preg_quote($searchfor[$i], '/');
            $pattern = "/^.*$pattern.*\$/m";
            if(preg_match_all($pattern, $content, $matches)){
                foreach($matches as $match){
                    $content = str_replace($match[0],'',$content);
                }
            }
        }
        return $content;
    }
    public function replaceLoadRenderLayout($content){   
            $content = str_replace("\$this->loadLayout(","\$this->_view->loadLayout(",$content);
            $content = str_replace("\$this->renderLayout(","\$this->_view->renderLayout(",$content);
            $content = str_replace("\$this->getLayout(","\$this->_view->getLayout(",$content);
            return $content;
    }
    public function replaceModel($content,$functionName,$className,$isAbstract = null){  // replace Mage::getModel to magento 2.x
        if($isAbstract)
            $content = Mage::helper('magento2challenge/convertModel')->getFunctionContentAll($content,$functionName);  // get body of function
        else
            $content = Mage::helper('magento2challenge/convertModel')->getFunctionContent($content,$functionName);  
        preg_match_all('#Mage\:\:(getModel|getSingleton|getResourceModel)\((.*?)\)#', $content, $matches);
        if(!isset($matches[0]) && $matches[0]) return $content;
        $explodeClass = explode('_',$className);  // class name of controller file
        foreach($matches as $match){
            foreach($match as $value){
                    $trimValue = trim($value,"'");
                    if(count($explodeClass) < 3)
                            continue;
                    $modelObj_1x = '';
                    if(strpos($trimValue,'getModel') === false && strpos($trimValue,'getSingleton') === false &&  strpos($trimValue,'getResourceModel') === false){
                         $modelObj_1x = Mage::helper('magento2challenge')->getClassNameFromConfigPath($trimValue, 'model');
                    }
                    $strClass = 'Mage::getModel('.$value.')';  // magento 1.x
                    $strClassSingleton = 'Mage::getSingleton('.$value.')';  // magento 1.x
                    $strResouceModel = 'Mage::getResourceModel('.$value.')';  // magento 1.x
                    $classModel = "'".str_replace('_','\\',$modelObj_1x)."'";
                    if(strpos($classModel,'Mage\Adminhtml') !== false){
                        $classModel = str_replace('Mage\Adminhtml','Magento\Backend',$classModel);
                    }
                    if(strpos($classModel,'Mage\\') !== false){
                        $classModel = str_replace('Mage\\','Magento\\',$classModel);
                    }
                    $modelObj_2x = '$this->_objectManager->create('.$classModel.')';  // magento 2.x
                    $modelObjSingleton_2x = '$this->_objectManager->get('.$classModel.')';  // magento 2.x
                    $modelObjResource_2x = '$this->_objectManager->get('.$classModel.')';  // magento 2.x
                    $content = str_replace($strClass,$modelObj_2x,$content);
                    $content = str_replace($strClassSingleton,$modelObjSingleton_2x,$content);
                    $content = str_replace($strResouceModel,$modelObjResource_2x,$content);
            }		
        }
        return $content;
    }
    public function replaceFunctionUploadFile($content){
            preg_match_all("#Varien_File_Uploader\(\'(.*?)\'#",$content,$matches);
            if(!isset($matches[1][0]))
                    return $content;
            $paramName = $matches[1][0];
            $content = str_replace("new Varien_File_Uploader('".$paramName."')","\$this->_objectManager->create('Magento\Core\Model\File\Uploader', array('fileId' => '".$paramName."'))",$content);
            $content = str_replace("Mage::getBaseDir('media')","\$this->_objectManager->get('Magento\Framework\Filesystem')
                                                    ->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath()",$content);
            $content = str_replace('DS','"/"',$content);
            return $content;
    }
    function replaceExport($content,$className,$actionName,$fileName,$blockName,$functionName)
    {  // replace content of function from magento 1.x to magento 2.x
            $search = array(
                '/<ClassName>/',
                '/<ActionName>/',
                '/<FileName>/',
                '/<BlockName>/',
                '/<FunctionName>/',
                   );

            $replace = array(
                $className,
                $actionName,
                $fileName,
                $blockName,
                $functionName,
                );
            //
            return preg_replace($search, $replace, $content);
    }
    public function replaceContentFunction($filePath,$func,$className,$isAbstract){ // class name of controller file
            $content = $this->replaceMessage($filePath);  // get content of file
            $content = $this->replaceModel($content,$func,$className,$isAbstract);  // get content of a function
            $content = $this->replaceRegister($content);  
            $content = $this->removeAddTabsContent($content);
//            $content = $this->replaceLoadRenderLayout($content);
            $content = Mage::helper('magento2challenge')->translateText($content);
            $content = $this->replaceFunctionUploadFile($content);
            $content = Mage::helper('magento2challenge/convertModel')->replaceHelper($content);
            $content = Mage::helper('magento2challenge')->run($content);
            return $content;  // return content of a function
    }
    function replacePhp($content,$className,$actionName,$abstractClass,$actionContent)
    {  // replace content of function from magento 1.x to magento 2.x
            if($actionName == 'New')
                $actionName = 'NewAction';
            $search = array(
                '/<ClassName>/',
                '/<ActionName>/',
                '/<AbstractClass>/',
                '/<Content>/',
                   );

            $replace = array(
                $className,
                $actionName,
                '\\'.$abstractClass,
                $actionContent,
                );
            //
            return preg_replace($search, $replace, $content);
    }
    function replaceAbstractContent($content,$className,$actionName,$bodyContent)
    {  // replace content of function from magento 1.x to magento 2.x
        $search = array(
            '/<ClassName>/',
            '/<ActionName>/',
            '/<BodyContent>/',
        );   
        $replace = array(
            $className,
            $actionName,
            $bodyContent,
        );
        //
        return preg_replace($search, $replace, $content);
    }
    public function replaceCreateBlock($fileContent,$namespace,$modulename,$type){
        preg_match_all("#createBlock\(\'(.*?)\'#",$fileContent,$matches);
        foreach($matches[0] as $key => $value){
            if(!isset($matches[1][$key]))
                return $fileContent;
            $stringClass = $matches[1][$key];
           // $stringClass = str_replace('/','_',$stringClass);
            //Zend_Debug::dump($stringClass); die();
            $blockclassName = Mage::helper('magento2challenge')->getClassNameFromConfigPath($stringClass, 'block');
    //        $blockclassName = $namespace.DS.$modulename.DS.'Block'.DS;
    //        for($i = 1; $i < count(explode('_',$stringClass));$i++){
    //          if($i != count(explode('_',$stringClass)) -1){	
    //                $stringClassEx = explode('_',$stringClass);
    //                $blockclassName .= ucwords($stringClassEx[$i]).DS;
    //          }
    //          else{
    //                $stringClassEx = explode('_',$stringClass);
    //                $blockclassName .= ucwords($stringClassEx[$i]);
    //          }	
    //        }
    //        $blockclassName = "'".$blockclassName."'";
            switch($type){
              case 'export':
                $fileContent = "'".$blockclassName."'";    
                break;  
              case 'other':  
                $fileContent = str_replace("createBlock('".$matches[1][$key]."')","createBlock('".$blockclassName."')",$fileContent) ;
                 
                break;
            }    
        }
        $fileContent = $this->replaceLoadRenderLayout($fileContent);
        return $fileContent;
    }
    public function writeContentControllerFile($file){ // create folder and create/write content of action file  
        /*sample file*/ 
        $sampleFile = self::SAMPLE_PATH.DS.'Action.php';  
        $sampleAbstractFile = self::SAMPLE_PATH.DS.'Abstract.php';
        $sampleExport = self::SAMPLE_PATH.DS.'ExportData.php';
       /* */ 
        $class_name = Mage::helper('magento2challenge')->getClassName($file);
        $explodeClass = explode('_',$class_name);
        $namespace = $explodeClass[0];
        $modulename = $explodeClass[1];
        $exController = explode('Controller',end($explodeClass));
        $className = $exController[0];
        $exFile = explode('Controller.php',$file);
        $controllerPath = $exFile[0];
        if(strpos($file,'Adminhtml') !== false) {   
            $class = $namespace.DS.$modulename.DS.'Controller'.DS.'Adminhtml'.DS.$className; 
            $absClass = $namespace.DS.$modulename.DS.'Controller'.DS.'Adminhtml';
        }
        else{
            $class = $namespace.DS.$modulename.DS.'Controller'.DS.$className; 
            $absClass = $namespace.DS.$modulename.DS.'Controller';
        }
        $abstractClass = $controllerPath.'.php';
        if(!file_exists($controllerPath))
                 mkdir($controllerPath,0777,true);	
        // write content file
        $functionNames = $this->getFunctionsName(file_get_contents($file),$class_name);	
        $funcArray = array();
        $functionContent = '';
        foreach($functionNames as $func){
                //create abstract action
                $abstractContent = file_get_contents($sampleAbstractFile);
                if(substr_count(file_get_contents($file),$func) > 1){
                    if(!in_array($func,$funcArray)){
                        $functionContent .= 	$this->replaceContentFunction($file,$func,$class_name,true);
                        $sampleContent = file_get_contents($sampleAbstractFile);
                        $action = $className;
                        $fileContent = $this->replaceAbstractContent($sampleContent,$absClass,$action,$functionContent);
                        $fileContent = $this->replaceCreateBlock($fileContent, $namespace, $modulename,'other');
                        $filePath = $abstractClass;
                        $handle = fopen ($filePath, 'w');
                        fputs($handle, $fileContent); 
                    }
                        $funcArray[] = $func; 
                }
                //
                //create action file and write content 
                $sampleContent = file_get_contents($sampleFile);
                $funcEx = explode('Action',$func);
                $action = ucwords($funcEx[0]);
                $newContent = $this->replaceContentFunction($file,$func,$class_name,false);
                $fileContent = $this->replacePhp($sampleContent,$class,$action,$class,$newContent);
                if($action == 'New') $action = 'NewAction';
                $filePath = $controllerPath.DS.$action.'.php';
                $handle = fopen ($filePath, 'w');
                // replace export csv/excel
                if(strpos($fileContent,'_prepareDownloadResponse') !== false){
                        $funcEx = explode('Action',$func);
                        $actionName = ucwords($funcEx[0]);
                        $fileName = strtolower($modulename);
                        if(strpos($fileContent,'getCsv()')){
                                $fileName = $fileName.'.csv';
                                $fileName = "'".$fileName."'";
                                $functionName = 'getCsvFile';
                        }
                        if(strpos($fileContent,'getXml()')){
                                $fileName = $fileName.'.xml';
                                $fileName = "'".$fileName."'";
                                $functionName = 'getExcelFile';
                        }
                    if(!isset($functionName)) continue;    
                    $blockclassName = $this->replaceCreateBlock($fileContent, $namespace, $modulename,'export');  
                    $fileContent = $this->replaceExport(file_get_contents($sampleExport),$class,$actionName,$fileName,$blockclassName,$functionName);
                }
                else{
                    $fileContent = $this->replaceCreateBlock($fileContent, $namespace, $modulename,'other');
                }
                //
                fputs($handle, $fileContent);    
                fclose($handle);
                //
        }
        // delete file
            unlink($file);
        //
        return true;	
    }
}
