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
/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

/**
 * create magento2challenge table
 */
$installer->run("

DROP TABLE IF EXISTS {$this->getTable('magento2challenge')};

CREATE TABLE {$this->getTable('magento2challenge')} (
  `id` int(11) unsigned NOT NULL auto_increment,
  `origin` varchar(255) NOT NULL default '',
  `target` varchar(255) NOT NULL default '',
  `type` varchar(255) NOT NULL default '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('magento2challenge/class')};

CREATE TABLE {$this->getTable('magento2challenge/class')} (
  `id` int(11) unsigned NOT NULL auto_increment,
  `class` varchar(255) NOT NULL default '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

insert into `magento2challenge` (`id`, `origin`, `target`, `type`) values('3','adminhtml/system_config_source_email_template','Magento\\Backend\\Model\\Config\\Source\\Email\\Template','model');
insert into `magento2challenge` (`id`, `origin`, `target`, `type`) values('4','adminhtml/system_store','Magento\\Store\\Model\\System\\Store','model');
insert into `magento2challenge` (`id`, `origin`, `target`, `type`) values('5','adminhtml/config_data','Magento\\Core\\Model\\Resource\\Config\\Data','model');
insert into `magento2challenge` (`id`, `origin`, `target`, `type`) values('6','customer','Magento\\Customer\\Model\\Session','helper');
insert into `magento2challenge` (`id`, `origin`, `target`, `type`) values('7','adminhtml/system_config_source_order_status','Magento\\Sales\\Model\\Config\\Source\\Order\\Status','model');
insert into `magento2challenge` (`id`, `origin`, `target`, `type`) values('8','adminhtml/system_config_source_email_identity','Magento\\Backend\\Model\\Config\\Source\\Email\\Identity','model');
insert into `magento2challenge` (`id`, `origin`, `target`, `type`) values('9','core/template','Magento\\Framework\\View\\Element\\Template','block');
insert into `magento2challenge` (`id`, `origin`, `target`, `type`) values('10','Mage_Core_Helper_Abstract','Magento\\Framework\\App\\Helper\\AbstractHelper','block');

");
$installer->addMagento2ClassName();

$installer->endSetup();

