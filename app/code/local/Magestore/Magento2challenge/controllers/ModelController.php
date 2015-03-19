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
 * Magento2challenge Index Controller
 *
 * @category    Magestore
 * @package     Magestore_Magento2challenge
 * @author      Magestore Developer
 */
class Magestore_Magento2challenge_ModelController extends Mage_Core_Controller_Front_Action
{
    /**
     * index action
     */
    public function indexAction()
    {
        //$path = 'mag/fasdfads/fdfadsf\fsdfad/fasdfa/ento1.9/app/code/community/Magestore/Convertext/Model/Convertext.php';
        //$replaced = preg_replace('/^.*\/app\/code\/.+?\//','',$path);
        //preg_match('/^.*\/app\/code\/.+?\//',$path,$matched);
        //die($replaced);




        //$syntaxString = "  include_one (  'abc.php')     ";
        //preg_match('/^\s*(inc.*?|req.*?)\s*\(?\s*["\'](.*?)["\']\s*\)?.*?$/', $syntaxString, $matcheds);
        //var_dump($matcheds);die;

        //start convert
        Mage::getModel('magento2challenge/convertModel')->convert(
            Mage::getBaseDir().'/INPUT/app/code/local/Magestore/Affiliateplus/Model/Account.php'
        );

        Mage::getModel('magento2challenge/convertModel')->convert(
            Mage::getBaseDir().'/INPUT/app/code/local/Magestore/Affiliateplus/Model/Mysql4/Account.php'
        );

        Mage::getModel('magento2challenge/convertModel')->convert(
            Mage::getBaseDir().'/INPUT/app/code/local/Magestore/Affiliateplus/Model/Mysql4/Account/Collection.php'
        );
    }

    public function testAction(){
        zend_debug::dump(explode('_', 'ABC'));die;

        zend_debug::dump(Mage::getUrl('catalog/product_type/view'));die;

        $content = file_get_contents('E:\www\magento1.9\var\test.php');

        preg_match_all('#(Mage\s*\:\:\s*getUrl\s*\({1}\s*((?:.|\n|\s)*?)\s*(?:,\s*((?:.|\n|\s)*?)\s*)?\){1})\s*(?:\.|\$|->|;)#', $content, $matched);
        zend_debug::dump($matched);die;

        $allFunctions = Mage::helper('magento2challenge/convertModel')->getClassType($content);
        zend_debug::dump($allFunctions);die;
    }



}