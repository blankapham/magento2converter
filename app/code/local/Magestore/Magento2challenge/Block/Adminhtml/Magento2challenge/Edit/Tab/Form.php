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
 * Magento2challenge Edit Form Content Tab Block
 * 
 * @category    Magestore
 * @package     Magestore_Magento2challenge
 * @author      Magestore Developer
 */
class Magestore_Magento2challenge_Block_Adminhtml_Magento2challenge_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * prepare tab form's information
     *
     * @return Magestore_Magento2challenge_Block_Adminhtml_Magento2challenge_Edit_Tab_Form
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        
        if (Mage::getSingleton('adminhtml/session')->getMagento2challengeData()) {
            $data = Mage::getSingleton('adminhtml/session')->getMagento2challengeData();
            Mage::getSingleton('adminhtml/session')->setMagento2challengeData(null);
        } elseif (Mage::registry('magento2challenge_data')) {
            $data = Mage::registry('magento2challenge_data')->getData();
        }
        $fieldset = $form->addFieldset('magento2challenge_form', array(
            'legend'=>Mage::helper('magento2challenge')->__('Item information')
        ));

        $fieldset->addField('title', 'text', array(
            'label'        => Mage::helper('magento2challenge')->__('Title'),
            'class'        => 'required-entry',
            'required'    => true,
            'name'        => 'title',
        ));

        $fieldset->addField('filename', 'file', array(
            'label'        => Mage::helper('magento2challenge')->__('File'),
            'required'    => false,
            'name'        => 'filename',
        ));

        $fieldset->addField('status', 'select', array(
            'label'        => Mage::helper('magento2challenge')->__('Status'),
            'name'        => 'status',
            'values'    => Mage::getSingleton('magento2challenge/status')->getOptionHash(),
        ));

        $fieldset->addField('content', 'editor', array(
            'name'        => 'content',
            'label'        => Mage::helper('magento2challenge')->__('Content'),
            'title'        => Mage::helper('magento2challenge')->__('Content'),
            'style'        => 'width:700px; height:500px;',
            'wysiwyg'    => false,
            'required'    => true,
        ));

        $form->setValues($data);
        return parent::_prepareForm();
    }
}