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
 * @author      Magestore Developer
 */
class Magestore_Magento2challenge_Model_Config_ConstructModel extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('magento2challenge/config_constructModel');
    }

    //get construct content for extends specialy class
    //param: $namespace is namespace of file contrain your model
    //param: $className is name of your class model
    //param: $classExtendsName is name of class that will extends
    //return string a construct function syntax
    public function getConstruct($namespace, $className, $classExtendsName){
        $configs = $this->getConfigs($namespace, $className);
        if(isset($configs[$classExtendsName]) && $configs[$classExtendsName]){
            return $configs[$classExtendsName];
        }
        return '';
    }

    //config contruct for each model type extended
    private function getConfigs($namespace, $className){
        $resource = $this->getResourceClass($namespace, $className);
        $resourceCollection = $this->getResourceCollectionClass($namespace, $className);
        return array(
            '\Magento\Framework\Model\AbstractModel' =>
                'public function __construct(
                    \Magento\Framework\Model\Context $context,
                    \Magento\Framework\Registry $registry,
                    '.$resource.' $resource,
                    '.$resourceCollection.' $resourceCollection
                ) {
                    parent::__construct(
                        $context,
                        $registry,
                        $resource,
                        $resourceCollection
                    );
                }',
            '' => '',
        );
    }

    private function getResourceClass($namespace, $className){
        return '\\'.$namespace.'\\Resource\\'.$className;
    }

    private function getResourceCollectionClass($namespace, $className){
        return '\\'.$namespace.'\\Resource\\'.$className.'\\Collection';
    }
}