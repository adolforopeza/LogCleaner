<?php
/**
 * Copyright © Adolfo Oropeza. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Aoropeza\LogCleaner\Helper;

use FilesystemIterator;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Exception;
use Aoropeza\LogCleaner\Helper\Settings;

class DirectorySettings extends AbstractHelper
{

    private const UNITS_TO_BYTES = [
        'B' => 1,
        'KB' => 1024,
        'MB' => 1048576,
        'GB' => 1073741824,
        'TB' => 1099511627776
    ];

    /**
     * @var DirectoryList
     */
    private DirectoryList $directoryList;

    /**
     * @var File
     */
    private File $driverFile;

    /**
     * @var Settings
     */
    private Settings $settings;

    /**
     * @param Context $context
     * @param DirectoryList $directoryList
     * @param File $driverFile
     * @param Settings $settings
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        File $driverFile,
        Settings $settings
    ) {
        $this->directoryList = $directoryList;
        $this->driverFile = $driverFile;
        $this->settings = $settings;
        parent::__construct($context);
    }

    /**
     * List directories inside var, with name and size
     *
     * @param bool $show_size validates if the directory size is obtained or not
     * @return array
     * @throws FileSystemException
     */
    public function getVarDirectoryList(bool $show_size): array
    {
        $varPath = $this->getVarBaseDirectoryPath();
        $directories = [];

        if ($this->driverFile->isExists($varPath)) {
            $directoryIterator = new FilesystemIterator($varPath, FilesystemIterator::SKIP_DOTS);
            foreach ($directoryIterator as $directory) {
                if ($directory->isDir()) {
                    $size = $show_size ? $this->getDirectorySize($directory->getPathname()) : "";
                    $sizeHuman = $show_size ? $this->getHumanReadableSize($size) : "";

                    $directories[] = array(
                        'path' => $directory->getPathname(),
                        'name' => $directory->getFilename(),
                        'name_size' => $directory->getFilename() . ' ' . $sizeHuman,
                        'size' => $sizeHuman,
                        'size_bytes' => $size
                    );
                }
            }
        }

        return $directories;
    }

    /**
     * Calculate directory size recursively
     *
     * @param string $directory
     * @return int
     */
    private function getDirectorySize(string $directory): int
    {
        $size = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * Convert bytes to human readable
     *
     * @param mixed $bytes
     * @return string
     */
    private function getHumanReadableSize($bytes): string
    {
        $bytes = (int) $bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Base var directory
     *
     * @return string
     * @throws FileSystemException
     */
    public function getVarBaseDirectoryPath(): string
    {
        return $this->directoryList->getPath(DirectoryList::VAR_DIR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Convert capacity to bytes Ex. 1GB = 1073741824 bytes
     *
     * @param string $size
     * @return int Size in bytes
     */
    public function getCapacityInBytes(string $size): int
    {
        $size = trim(strtoupper($size));

        if (preg_match('/^(\d+(?:\.\d+)?)\s*(B|KB|MB|GB|TB)$/', $size, $matches)) {
            $number = (float) $matches[1];
            $unit = $matches[2];

            if (isset(self::UNITS_TO_BYTES[$unit])) {
                return (int) ($number * self::UNITS_TO_BYTES[$unit]);
            }
        }

        return 0;
    }

    /**
     * Responsible for emptying the selected directory
     *
     * @param string $directoryPath Full path to directory
     * @return bool
     * @throws FileSystemException
     */
    public function emptyDirectory(string $directoryPath): bool
    {
        try {
            // Validate that the directory exists
            if (!$this->driverFile->isExists($directoryPath)) {
                throw new FileSystemException(
                    __('Directory does not exist: %1', $directoryPath)
                );
            }

            // Validate that it is a directory
            if (!$this->driverFile->isDirectory($directoryPath)) {
                throw new FileSystemException(
                    __('Path is not a directory: %1', $directoryPath)
                );
            }

            if ($this->settings->isBackupEnabled()) {
                $this->backupDirectory($directoryPath);
            }

            // Get all files and directories
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directoryPath, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            // Delete each file and directory
            foreach ($files as $file) {
                if ($file->isDir()) {
                    $this->driverFile->deleteDirectory($file->getRealPath());
                } else {
                    $this->driverFile->deleteFile($file->getRealPath());
                }
            }

            return true;
        } catch (Exception $e) {
            throw new FileSystemException(
                __('DIRECTORY CLEANUP FAILED %1: %2', $directoryPath, $e->getMessage())
            );
        }
    }

    /**
     * Backup directory to zip
     *
     * @param string $sourcePath
     * @param string|null $destinationPath
     * @return string
     * @throws FileSystemException
     */
    public function backupDirectory(string $sourcePath, ?string $destinationPath = null): string
    {
        if (!$this->driverFile->isExists($sourcePath)) {
            throw new FileSystemException(
                __('Source directory does not exist: %1', $sourcePath)
            );
        }

        if ($destinationPath === null) {
            $varPath = $this->getVarBaseDirectoryPath();
            $backupDir = $varPath . 'backups';

            if (!$this->driverFile->isExists($backupDir)) {
                $this->driverFile->createDirectory($backupDir, 0755);
            }

            $timestamp = date('Ymd_His');
            $dirName = basename($sourcePath);
            $destinationPath = $backupDir . DIRECTORY_SEPARATOR . $dirName . '_' . $timestamp . '.zip';
        }

        $zip = new \ZipArchive();
        if ($zip->open($destinationPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new FileSystemException(
                __('Cannot create zip file: %1', $destinationPath)
            );
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $sourcePathReal = realpath($sourcePath);
                $relativePath = substr($filePath, strlen($sourcePathReal) + 1);

                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        return $destinationPath;
    }
}
