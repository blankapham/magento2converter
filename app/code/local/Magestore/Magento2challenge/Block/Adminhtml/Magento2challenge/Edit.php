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
 * Magento2challenge Edit Block
 * 
 * @category     Magestore
 * @package     Magestore_Magento2challenge
 * @author      Magestore Developer
 */
class Magestore_Magento2challenge_Block_Adminhtml_Magento2challenge_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        
        $this->_objectId = 'id';
        $this->_blockGroup = 'magento2challenge';
        $this->_controller = 'adminhtml_magento2challenge';
        
        $this->_updateButton('save', 'label', Mage::helper('magento2challenge')->__('Save Item'));
        $this->_updateButton('delete', 'label', Mage::helper('magento2challenge')->__('Delete Item'));
        
        $this->_addButton('saveandcontinue', array(
            'label'        => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'    => 'saveAndContinueEdit()',
            'class'        => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('magento2challenge_content') == null)
                    tinyMCE.execCommand('mceAddControl', false, 'magento2challenge_content');
                else
                    tinyMCE.execCommand('mceRemoveControl', false, 'magento2challenge_content');
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }
    
    /**
     * get text to show in header when edit an item
     *
     * @return string
     */
    public function getHeaderText()
    {
        if (Mage::registry('magento2challenge_data')
            && Mage::registry('magento2challenge_data')->getId()
        ) {
            return Mage::helper('magento2challenge')->__("Edit Item '%s'",
                                                $this->htmlEscape(Mage::registry('magento2challenge_data')->getTitle())
            );
        }
        return Mage::helper('magento2challenge')->__('Add Item');
    }
}