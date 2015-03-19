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
class Magestore_Magento2challenge_ResourceController extends Mage_Core_Controller_Front_Action
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
        Mage::getModel('magento2challenge/convertResource')->convert(
            Mage::getBaseDir().'/INPUT/affiliateplus/app/code/local/Magestore/Affiliateplus/Model/Mysql4/Account.php',
            'Mysql4'
        );
    }

    public function collectionAction()
    {
        //start convert
        die(Mage::getModel('magento2challenge/convertCollection')->convert(
            Mage::getBaseDir().'/INPUT/affiliateplus/app/code/local/Magestore/Affiliateplus/Model/Mysql4/Account/Collection.php',
            'Mysql4'
        ));
    }
}