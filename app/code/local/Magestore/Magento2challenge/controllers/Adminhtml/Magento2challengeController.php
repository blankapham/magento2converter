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
 * Magento2challenge Adminhtml Controller
 *
 * @category    Magestore
 * @package     Magestore_Magento2challenge
 * @author      Magestore Developer
 */
class Magestore_Magento2challenge_Adminhtml_Magento2challengeController extends Mage_Adminhtml_Controller_Action
{
    /**
     * init layout and set active for current menu
     *
     * @return Magestore_Magento2challenge_Adminhtml_Magento2challengeController
     */

    /**
     * index action
     */
    public function indexAction()
    {
        $this->loadLayout()
            ->renderLayout();
    }

    public function downloadAction()
    {
        $filename = $this->getRequest()->getParam('file');
        $filepath = Mage::getBaseDir('base') . '/OUTPUT/' . $filename;

        if (!is_file($filepath) || !is_readable($filepath)) {
            throw new Exception ();
        }
        $this->getResponse()
            ->setHttpResponseCode(200)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', TRUE)
            ->setHeader('Pragma', 'public', TRUE)
            ->setHeader('Content-type', 'application/force-download')
            ->setHeader('Content-Length', filesize($filepath))
            ->setHeader('Content-Disposition', 'attachment' . '; filename=' . basename($filepath));
        $this->getResponse()->clearBody();
        $this->getResponse()->sendHeaders();
        readfile($filepath);
        exit;
    }

    public function downloadExampleAction()
    {
        $filename = $this->getRequest()->getParam('file');
        $filepath = Mage::getBaseDir('base') . '/INPUT/' . $filename;

        if (!is_file($filepath) || !is_readable($filepath)) {
            throw new Exception ();
        }
        $this->getResponse()
            ->setHttpResponseCode(200)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', TRUE)
            ->setHeader('Pragma', 'public', TRUE)
            ->setHeader('Content-type', 'application/force-download')
            ->setHeader('Content-Length', filesize($filepath))
            ->setHeader('Content-Disposition', 'attachment' . '; filename=' . basename($filepath));
        $this->getResponse()->clearBody();
        $this->getResponse()->sendHeaders();
        readfile($filepath);
        exit;
    }

    /**
     * view and edit item action
     */
    public function unZip($file)
    {
        $zip = new ZipArchive;
        $res = $zip->open($file);
        if ($res === TRUE) {
            $zip->extractTo(Mage::getBaseDir() . DS . 'INPUT');
            $zip->close();
        }
    }

    public function createZip()
    {
        $zip = new ZipArchive;
        $moduleData = $this->_helper()->getModuleData();
        $result = $zip->open('OUTPUT' . DS . $moduleData['lower_module_name'] . '.zip', ZipArchive::CREATE);
        $dir = Mage::getBaseDir() . DS . 'OUTPUT';
        $base = Mage::getBaseDir() . DS . 'OUTPUT';
        if ($result === TRUE) {
            $this->addDirectoryToZip($zip, $dir, $base);
            $zip->close();
        }
        Mage::getSingleton('admin/session')->setData('magento2convert_zipfile', $moduleData['lower_module_name'] . '.zip');

        return;
    }

    public function addDirectoryToZip($zip, $dir, $base)
    {
        $newFolder = str_replace($base, '', $dir);
        $zip->addEmptyDir(ltrim($newFolder, DS));
        foreach (glob($dir . DS . '*') as $file) {
            if (is_dir($file)) {
                $zip = $this->addDirectoryToZip($zip, $file, $base);
            } else {
                $newFile = str_replace($base, '', $file);
                $zip->addFile($file, ltrim($newFile, DS));
            }
        }

        return $zip;
    }

    function mainConvert($dir)
    {
        $moduleData = $this->_helper()->getModuleData();
        $nameSpace = $moduleData['name_space'];
        $moduleName = $moduleData['module_name'];
        $listing = opendir($dir);
        while (($entry = readdir($listing)) !== FALSE) {
            if ($entry != "." && $entry != "..") {
                $coreItem = $dir . DS . $entry;
                $replaceArray = array(
                    'INPUT'                   => 'OUTPUT',
                    'code' . DS . 'local'     => 'code',
                    'controllers'             => 'Controller',
                    'design'                  => 'code' . DS . $nameSpace . DS . $moduleName . DS . 'view',
                    'app' . DS . 'locale'     => 'app' . DS . 'code' . DS . $nameSpace . DS . $moduleName . DS . 'i18n',
                    'skin' . DS . 'adminhtml' => 'app' . DS . 'code' . DS . $nameSpace . DS . $moduleName . DS . 'view' . DS . 'adminhtml' . DS . 'web',
                    'skin' . DS . 'frontend'  => 'app' . DS . 'code' . DS . $nameSpace . DS . $moduleName . DS . 'view' . DS . 'frontend' . DS . 'web',
                    'skin'                    => '',
                    'media'                   => 'pub' . DS . 'media',
                    'etc' . DS . 'system.xml' => 'etc' . DS . 'adminhtml' . DS . 'system.xml',
                    DS . 'base'               => '',
                    DS . 'default'            => '',
                    'OUTPUT' . DS . 'js'      => 'OUTPUT' . DS . 'app' . DS . 'code' . DS . $nameSpace . DS . $moduleName . DS . 'view' . DS . 'base' . DS . 'web',
                    'OUTPUT' . DS . 'lib'     => 'OUTPUT' . DS . 'lib' . DS . 'internal'
                );
                $localItem = str_replace(array_keys($replaceArray), array_values($replaceArray), $coreItem);
                if (is_dir($coreItem)) {
                    if (!file_exists($localItem)) {
                        $old_umask = umask(0);
                        mkdir($localItem, 0755, TRUE);
                        if (strpos($localItem, $moduleName . DS . 'etc')) {
                            mkdir($localItem . DS . 'adminhtml', 0755, TRUE);
                            mkdir($localItem . DS . 'frontend', 0755, TRUE);
                        }
                        umask($old_umask);
                    }
                    $this->mainConvert($coreItem);
                } elseif (is_file($coreItem)) {
                    copy($coreItem, $localItem);
                    if (substr($coreItem, -4) == '.php') {
                        if (strpos($coreItem, $moduleName . DS . 'controllers') !== FALSE) {
                            Mage::helper('magento2challenge/convertController')->writeContentControllerFile($localItem);
                        }
                        if (strpos($coreItem, 'Model') !== FALSE) {
                            Mage::getModel('magento2challenge/convertModel')->convert($coreItem);
                        }
                        if (strpos($coreItem, $moduleName . DS . 'Block') !== FALSE) {
                            Mage::helper('magento2challenge/convertBlock')->writeContentBlockFile($localItem);
                        }
                        if (strpos($coreItem, $moduleName . DS . 'Helper') !== FALSE) {
                            Mage::helper('magento2challenge/convertHelper')->writeContentHelperFile($localItem);
                        }
                        if (strpos($coreItem, $moduleName . DS . 'sql') !== FALSE) {
                            Mage::helper('magento2challenge')->writeContentSqlFile($localItem);
                        }
                    } elseif (substr($coreItem, -6) == '.phtml') {
                        Mage::getModel('magento2challenge/template')->convert($localItem);
                    }
                }
            }
        }
    }

    public function convertAction()
    {
        $this->setOriginModuleData();
        if (!$this->getRequest()->getParam('useOldPackage')) {
            $this->removeInputFolder();
            if ($data = $this->getRequest()->getPost()) {
                if (isset($_FILES['zip_1x']['name']) && $_FILES['zip_1x']['name'] != '') {
                    $filePath = $_FILES['zip_1x']['tmp_name'];
                    $this->unZip($filePath);
                }
            } else {
                $this->_redirect('*/*/');

                return;
            }
        }
        if ($this->setModuleData()) {
            try {
                $this->readConfigXml();
                $this->mainConvert(Mage::getBaseDir() . DS . 'INPUT');
                Mage::getModel('magento2challenge/xml')->convert();
                Mage::getModel('magento2challenge/locale')->convert();

                $moduleData = $this->_helper()->getModuleData();
                if (!array_key_exists('class_error', $moduleData)) {
                    Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('magento2challenge')->__('Congratulations! You converted successfully!')
                    );
                } else {
                    $content = 'We cannot find this class in Magento 2:' . "\n";
                    foreach ($moduleData['class_error'] as $class => $type) {
                        $content .= $type . ' : ' . $class . "\n";
                    }
                    $this->_helper()->writeContentToFile($moduleData['module_name'] . '.log', $content);
                }

                $this->createZip();

                if ($this->getRequest()->getParam('submitdownload')) {
                    $filename = $moduleData['module_name'] . '.zip';
                    $filepath = Mage::getBaseDir('base') . '/OUTPUT/' . $filename;

                    if (is_file($filepath) && is_readable($filepath)) {
                        $this->getResponse()
                            ->setHttpResponseCode(200)
                            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', TRUE)
                            ->setHeader('Pragma', 'public', TRUE)
                            ->setHeader('Content-type', 'application/force-download')
                            ->setHeader('Content-Length', filesize($filepath))
                            ->setHeader('Content-Disposition', 'attachment' . '; filename=' . basename($filepath));
                        $this->getResponse()->clearBody();
                        $this->getResponse()->sendHeaders();
                        readfile($filepath);
                    }
                }
            } catch (Exception $e) {
            }
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('magento2challenge')->__('This is not a Magento module.')
            );
        }
        $this->_redirect('*/*/');
    }

    public function savedataAction()
    {
        $datas = $this->getRequest()->getParam('data');
        $dataArray = array();
        foreach ($datas as $data) {
            $target = $data['target'];
            $modulePath = explode('\\', $target);
            if (count($modulePath) < 4) {
                continue;
            }
            if ($modulePath[0] == 'Magento' && !$this->_helper()->magentoClassExist($target)) {
                continue;
            }
            $dataArray[] = array(
                'origin' => $data['origin'],
                'target' => $data['target'],
                'type'   => $data['type']
            );
        }
        if (count($dataArray)) {
            Mage::getResourceModel('magento2challenge/magento2challenge')->insertData($dataArray);
        }

        $this->_redirect('*/*/convert', array('useOldPackage' => TRUE));
    }

    public function readConfigXml()
    {
        $configFile = Mage::helper('magento2challenge')->getModuleDir('origin') . DS . 'etc' . DS . 'config.xml';
        $configXmlNotes = Mage::getModel('magento2challenge/xml')->getXmlNotes($configFile)->asArray();

        $moduleData = $this->_helper()->getModuleData();
        $moduleData['config'] = $configXmlNotes;
        $this->_helper()->setModuleData($moduleData);

        return $this;
    }

    public function removeInputFolder()
    {
        if (is_dir(Mage::getBaseDir() . DS . 'INPUT')) {
            $this->_helper()->rrmdir(Mage::getBaseDir() . DS . 'INPUT');
        }
    }

    public function setOriginModuleData()
    {
        if (is_dir(Mage::getBaseDir() . DS . 'OUTPUT')) {
            $this->_helper()->rrmdir(Mage::getBaseDir() . DS . 'OUTPUT');
        }

        $this->_helper()->setModuleData(array());
        Mage::getSingleton('admin/session')->unsetData('magento2convert_zipfile');

        if (!is_dir(Mage::getBaseDir() . DS . 'INPUT')) {
            mkdir(Mage::getBaseDir() . DS . 'INPUT');
        }
        if (!is_dir(Mage::getBaseDir() . DS . 'OUTPUT')) {
            mkdir(Mage::getBaseDir() . DS . 'OUTPUT');
        }
    }

    public function setModuleData()
    {
        $nameSpace = '';
        $moduleName = '';
        $dirLocal = getcwd() . DS . 'INPUT' . DS . 'app' . DS . 'code' . DS . 'local';
        $dirCommu = getcwd() . DS . 'INPUT' . DS . 'app' . DS . 'code' . DS . 'community';
        if (!is_dir($dirLocal) && !is_dir($dirCommu)) {
            return FALSE;
        }
        $dir = is_dir($dirLocal) ? $dirLocal : $dirCommu;
        $listing = opendir($dir);
        while (($entry = readdir($listing)) !== FALSE) {
            if ($entry != "." && $entry != "..") {
                $nameSpace = $entry;
                $listingModule = opendir($dir . DS . $nameSpace);
                while (($entryModule = readdir($listingModule)) !== FALSE) {
                    if ($entryModule != "." && $entryModule != "..") {
                        $moduleName = $entryModule;
                        break;
                    }
                }
                break;
            }
        }
        if ($nameSpace && $moduleName) {
            $this->_helper()->setModuleData(array(
                                                'module_name'       => $moduleName,
                                                'lower_module_name' => strtolower($moduleName),
                                                'name_space'        => $nameSpace
                                            ));

            return TRUE;
        }

        return FALSE;
    }

    protected function _helper($helper = NULL)
    {
        if ($helper) {
            return Mage::helper('magento2challenge/' . $helper);
        }

        return Mage::helper('magento2challenge');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('magento2challenge');
    }
}
