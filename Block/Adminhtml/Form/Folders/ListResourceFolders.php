<?php
/**
 * Copyright © Adolfo Oropeza. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Aoropeza\LogCleaner\Block\Adminhtml\Form\Folders;

use Aoropeza\LogCleaner\Helper\DirectorySettings;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

class ListResourceFolders extends Select
{

    /**
     * @var array
     */
    protected $_options = [];
    protected DirectorySettings $directorySettings;

    public function __construct(
        Context $context,
        DirectorySettings $directorySettings,
        array $data = []
    ) {
        $this->directorySettings = $directorySettings;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function _toHtml(): string
    {
        $this->_options = $this->getSource();

        if (!$this->getOptions()) {
            foreach ($this->_options as $value => $item) {
                $this->addOption($value, $item->getName());
            }
        }
        $this->setClass('select-product input-select required-entry');
        $this->setTitle("Category");
        $extraParams = 'style="width: 200px;"';
        $this->setExtraParams($extraParams);
        return parent::_toHtml();
    }

    /**
     * @return array
     * @throws FileSystemException
     */
    public function getSource(): array
    {
        $categories = array();
        $category = $this->directorySettings->getVarDirectoryList(false);
        foreach ($category as $_item) {
            $categories[$_item["name"]] = "var/" . $_item["name_size"];
        }
        return $categories;
    }

    /**
     * @param $value
     * @return mixed
     */
    public function setInputName($value): mixed
    {
        return $this->setName($value);
    }
}
