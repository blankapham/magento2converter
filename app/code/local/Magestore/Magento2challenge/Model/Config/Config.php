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
 * @package     Magestore_Convertext
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * Convertext Model
 *
 * @category    Magestore
 * @package     Magestore_Convertext
 * @author      Magestore Developer Tit
 */
class Magestore_Magento2challenge_Model_Config_Config extends Mage_Core_Model_Abstract
{
    protected $_routerPath; //array router map path

    public function _construct()
    {
        parent::_construct();
        $this->_init('magento2challenge/config_config');
        $this->_routerPath = Mage::getModel('magento2challenge/config_routerPath')->getRouterMap();
    }

    //get magento 2 router path from old router path
    public function getRouterPath($oldRouterPath, $quote = true){
        $oldRouterPath = trim($oldRouterPath, '"\'');
        $newRouterPath = '';
        if(isset($this->_routerPath[$oldRouterPath]) && $this->_routerPath[$oldRouterPath] != null){
            if($quote){
                return '\''.$this->_routerPath[$oldRouterPath].'\'';
            }else{
                return $this->_routerPath[$oldRouterPath];
            }
        }else{
            if(preg_match('#adminhtml\/{1}(.*?)\/{1}(.*)#', $oldRouterPath, $matched)){
                //$router = explode('_', $matched[1]);
                $controller = $matched[1];
                $action = $matched[2] ? $matched[2] : 'index';
                //$routerName = $router[0];
                //$folder = $matched[1];
                //if(count(array_shift($router))) {
                //    $folder = implode('_', $router);
                //}
                //if($folder == ''){
                //    $folder = $controller;
                //    $controller = $action;
                //    $action = 'index';
                //}
                $newRouterPath = 'admin/'.$controller.'/'.$action;
            }else if(preg_match('#(.*?)\/adminhtml_(.*)\/?(.*)$#', $oldRouterPath, $matched)){
                $routerName = $matched[1];
                $controller = $matched[2];
                $action = $matched[3] ? $matched[3] : 'index';
                if($controller == ''){
                    $controller = $action;
                    $action = 'index';
                }
                $newRouterPath = $routerName.'/'.$controller.'/'.$action;
            }else if(preg_match('#(.*?)\/(.*)\/(.*)$#', $oldRouterPath, $matched)){
                $routerName = $matched[1];
                $controller = $matched[2] ? $matched[2] : 'index';
                $action = $matched[3] ? $matched[3] : 'index';
                $newRouterPath = $routerName.'/'.$controller.'/'.$action;
            }
            //check
            if(!$this->_isRouterPath($newRouterPath)){
                $this->_insertRequireRouterPath($newRouterPath);
            }
        }
        if($quote){
            return '\''.$newRouterPath.'\'';
        }else{
            return $newRouterPath;
        }
    }

    //check is magento 2 router path
    //return bool
    private function _isRouterPath($routerPath){
        //get router list from magento 2
        //function ...
        if($routerPath){
            return true;
        }
        return false;
    }

    private function _insertRequireRouterPath($routerPath){
        //inserting

        //insert complete
        return $routerPath;
    }
}