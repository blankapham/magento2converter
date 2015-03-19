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
class Magestore_Magento2challenge_Model_Config_Construct extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('magento2challenge/config_construct');
    }

    //get construct content for extends specialy class
    //param: $namespace is namespace of file contrain your model
    //param: $className is name of your class model
    //param: $classExtendsName is name of class that will extends
    //return string a construct function syntax
    public function getConstruct($classExtendsName, $namespace, $className){
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

            '\Magento\Framework\Model\Resource\Db\AbstractDb' =>
            'public function __construct(\Magento\Framework\App\Resource $resource) {
                parent::__construct($resource);
            }',

            '\Magento\Framework\Model\Resource\Db\Collection\AbstractCollection' =>
            'public function __construct(
                    \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
                    \Magento\Framework\Logger $logger,
                    \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
                    \Magento\Framework\Event\ManagerInterface $eventManager,
                    $connection = null,
                    \Magento\Framework\Model\Resource\Db\AbstractDb $resource = null
            )
            {
                parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
            }',

            '\Magento\Framework\ObjectManager\ObjectManager' =>
            'public function __construct(
                    \Magento\Framework\ObjectManagerInterface $objectManager,
                    \Magento\Framework\ObjectManager\FactoryInterface $factory,
                    \Magento\Framework\ObjectManager\ConfigInterface $config
            )
            {
                parent::__construct($factory, $config);
                $this->_objectManager = $objectManager;
            }'

        );
    }

    private function getResourceClass($namespace, $className){
        return '\\'.$namespace.'\\Resource\\'.$className;
    }

    private function getResourceCollectionClass($namespace, $className){
        return '\\'.$namespace.'\\Resource\\'.$className.'\\Collection';
    }
}