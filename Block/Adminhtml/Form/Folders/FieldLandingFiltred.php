<?php
/**
 * Copyright © Adolfo Oropeza. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Aoropeza\LogCleaner\Block\Adminhtml\Form\Folders;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;


class FieldLandingFiltred extends AbstractFieldArray
{

    protected $_columns = [];
    protected $_addAfter = true;
    protected $_addButtonLabel;
    private $resourceFoldersRenderer;

    public function renderCellTemplate($columnName): string
    {
        $options = array("folders");
        if (in_array($columnName, $options)) {
            $this->_columns[$columnName]['class'] = 'input-select required-entry test';
        }

        if ($columnName == "value") {
            $this->_columns[$columnName]['class'] = 'input-text';
            return '<input type="text" id="' . $this->_getCellInputElementId('<%- _id %>', $columnName) .
                '" name="' . $this->_getCellInputElementName($columnName) . '" value="<%- ' . $columnName . ' %>" ' .
                (isset($this->_columns[$columnName]['size']) ? 'size="' . $this->_columns[$columnName]['size'] . '"' : '') .
                ' class="' . (isset($this->_columns[$columnName]['class']) ? $this->_columns[$columnName]['class'] : 'input-text') .
                '"' . (isset($this->_columns[$columnName]['style']) ? ' style="' . $this->_columns[$columnName]['style'] . '"' : '') .
                '/>';
        }
        return parent::renderCellTemplate($columnName);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->_addButtonLabel = __('Add');
    }

    protected function _prepareToRender()
    {
        $this->addColumn(
            'folders',
            [
                'label' => __('Folders in VAR'),
                'renderer' => $this->listResourceFolders(),
            ]
        );
        $this->_addAfter = false;
    }


    private function listResourceFolders()
    {
        if (!$this->resourceFoldersRenderer) {
            $this->resourceFoldersRenderer = $this->getLayout()->createBlock(
                '\Aoropeza\LogCleaner\Block\Adminhtml\Form\Folders\ListResourceFolders',
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->resourceFoldersRenderer;
    }

    protected function _prepareArrayRow(DataObject $row)
    {
        $folders = $row->getFolders();

        $optionsFolders = [];
        $select = [];

        if ($folders) {
            $optionsFolders['option_' . $this->listResourceFolders()->calcOptionHash($folders)] = 'selected="selected"';
            $select = array(
                'option_' . $this->listResourceFolders()->calcOptionHash($folders) => 'selected="selected"'
            );
        }

        $row->setData('option_extra_attrs', $select);
        $row->setData('folders', $optionsFolders);

    }
}
