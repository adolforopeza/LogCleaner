<?php
/**
 * Copyright © Adolfo Oropeza. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Aoropeza\LogCleaner\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Settings extends AbstractHelper
{

    private const LOGCLEANER_GENERAL = "log_cleaner/general/";
    private const LOGCLEANER_ENABLE = self::LOGCLEANER_GENERAL . "enable";
    private const LOGCLEANER_FREQUENCY = self::LOGCLEANER_GENERAL . "frequency";
    private const LOGCLEANER_CAPACITY = self::LOGCLEANER_GENERAL . "capacity";
    private const LOGCLEANER_CAPACITY_CONF = self::LOGCLEANER_GENERAL . "capacity_conf";
    private const LOGCLEANER_FOLDERS = "log_cleaner/folders/";
    private const LOGCLEANER_LIST = self::LOGCLEANER_FOLDERS . "list";


    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    /**
     * Indicates if the module is enabled
     *
     * @return bool
     */
    public function isModuleEnable(): bool
    {
        return $this->scopeConfig->isSetFlag(self::LOGCLEANER_ENABLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Indicates the cron execution frequency
     *
     * @return mixed
     */
    public function getFrequency()
    {
        return $this->scopeConfig->getValue(self::LOGCLEANER_FREQUENCY, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Indicates the capacity that will be taken into account to clean the directory
     *
     * @return string
     */
    public function getCapacity(): string
    {
        return (string)$this->scopeConfig->getValue(self::LOGCLEANER_CAPACITY, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Indicates if the directory size should be checked
     *
     * @return bool
     */
    public function getCapacityConf(): bool
    {
        return $this->scopeConfig->isSetFlag(self::LOGCLEANER_CAPACITY_CONF, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Gets the configured directories to clean
     *
     * @return array
     */
    public function getListDirectories(): array
    {
        try {
            $value = $this->scopeConfig->getValue(self::LOGCLEANER_LIST, ScopeInterface::SCOPE_STORE);
            if (!$value) {
                return [];
            }
            $list = json_decode($value);
            $return = [];
            if (!empty($list)) {
                foreach ($list as $item) {
                    if (isset($item->folders)) {
                        $return[] = $item->folders;
                    }
                }
            }
            return $return;
        } catch (\Throwable $th) {
            return [];
        }
    }
}
