<?php
/**
 * Copyright © Adolfo Oropeza. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Aoropeza\LogCleaner\Cron;

use Aoropeza\LogCleaner\Helper\{DirectorySettings, Settings};
use Psr\Log\LoggerInterface;

class LogCleaner
{
    /**
     * @var DirectorySettings
     */
    protected DirectorySettings $directorySettings;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var Settings
     */
    private Settings $settings;


    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param Settings $settings
     * @param DirectorySettings $directorySettings
     */
    public function __construct(
        LoggerInterface $logger,
        Settings $settings,
        DirectorySettings $directorySettings
    ) {
        $this->directorySettings = $directorySettings;
        $this->settings = $settings;
        $this->logger = $logger;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            if ($this->settings->isModuleEnable()) {
                $this->logger->info("Cronjob LogCleaner is executed.");

                $defaultCapacity = $this->settings->getCapacity();
                $this->logger->info("Cron LogCleaner: Configured capacity retrieved : " . $defaultCapacity);

                $defaultCapacity = $this->directorySettings->getCapacityInBytes($defaultCapacity);
                $this->logger->info("Cron LogCleaner: Configured capacity in bytes retrieved : " . $defaultCapacity);

                $isConfigSize = $this->settings->getCapacityConf();
                $this->logger->info("Cron LogCleaner: Validating if directory size calculation is enabled : " . json_encode($isConfigSize));

                $listDirectories = $this->directorySettings->getVarDirectoryList($isConfigSize);
                $this->logger->info("Cron LogCleaner: Directory list retrieved : " . json_encode($listDirectories));

                $settingDirectories = $this->settings->getListDirectories();
                $this->logger->info("Cron LogCleaner: Configured directory list retrieved : " . json_encode($settingDirectories));

                if (!empty($settingDirectories)) {

                    $listDirectories = array_filter($listDirectories, function ($directory) use ($settingDirectories) {
                        return in_array($directory['name'], $settingDirectories);
                    });
                    $this->logger->info("Cron LogCleaner: Filtered directory list : " . json_encode($listDirectories));

                    foreach ($listDirectories as $directory) {
                        $clean = $isConfigSize ? ($directory['size_bytes'] >= $defaultCapacity) : true;

                        if ($clean) {
                            $this->logger->info("Cronjob LogCleaner: Emptying directory [ " . $directory['name'] . " ]");
                            $result = $this->directorySettings->emptyDirectory($directory['path']);
                            if ($result) {
                                $this->logger->info("Cronjob LogCleaner: Directory emptied [ " . $directory['name'] . " ]");
                            } else {
                                $this->logger->info("Cronjob LogCleaner: Could not empty directory [ " . $directory['name'] . " ]");
                            }
                        }
                    }
                    $this->logger->info("Cronjob LogCleaner finished.");
                }
            } else {
                $this->logger->info("Cronjob LogCleaner is disabled.");
            }
        } catch (\Throwable $th) {

            $this->logger->error("CRON LogCleaner ERROR: ", [
                "message" => $th->getMessage(),
                "code" => $th->getCode(),
                "file" => $th->getFile(),
                "line" => $th->getLine(),
                "trace" => $th->getTraceAsString(),
            ]);
        }
    }
}
