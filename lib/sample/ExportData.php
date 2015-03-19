<?php
/**
 *
 * @copyright Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 */
namespace <ClassName>;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class <ActionName> extends \Magento\Backend\App\Action
{
    /**
     * Export sales report grid to CSV format
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $this->_view->loadLayout();
		$fileName = <FileName>;
        $grid = $this->_view->getLayout()->createBlock(<BlockName>);
		$fileFactory = $this->_objectManager->get('Magento\Framework\App\Response\Http\FileFactory');
        return $fileFactory->create($fileName, $grid-><FunctionName>(), DirectoryList::VAR_DIR);
    }
}
