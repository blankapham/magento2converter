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
 * @package     Magestore_RewardPoints
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * Rewardpoints Setup Resource Model
 * 
 * @category    Magestore
 * @package     Magestore_RewardPoints
 * @author      Magestore Developer
 */
class Magestore_Magento2challenge_Model_Mysql4_Setup extends Mage_Core_Model_Resource_Setup
{
    public function addMagento2ClassName(){
        $fileName = Mage::getBaseDir().DS.'includes'.DS.'magento2challenge'.DS.'magento2challenge_class.csv';
        $csvObject = new Varien_File_Csv();
        $dataFile = $csvObject->getData($fileName);        
        try{
            $data = 'insert into '.$this->getTable('magento2challenge/class').' (`id`, `class`) values ';
            foreach($dataFile as $entry){
                $data .= "(null, '".preg_quote($entry[1])."'),";
            }
            $data = trim($data, ',');
            $this->_conn->query($data);
        } catch (Exception $ex) {
        }
        return;
    }
}
