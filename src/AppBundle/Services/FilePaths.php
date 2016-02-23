<?php

namespace AppBundle\Services;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Journal;
use Monolog\Logger;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Description of FilePaths
 *
 * @author mjoyce
 */
class FilePaths {
    
    private $baseDir;
    
    private $fs;
    
    private $env;
    
    /**
     * @var Logger
     */
    private $logger;

    public function __construct() {
        $this->fs = new Filesystem();
    }
    
    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }

    public function setKernelEnv($env) {
        $this->env = $env;
    }
    
    public function setBaseDir($dir) {
        if(substr($dir, -1) !== '/') {
            $this->baseDir = $dir . '/';
        } else {
            $this->baseDir = $dir;
        }
    }
    
    protected function rootPath() {
        $path = $this->baseDir;
        if ( ! $this->fs->isAbsolutePath($path)) {
            $root = dirname($this->env);
            $path =  $root . '/' . $path;
        }
        return realpath($path);
    }
    
    /**
     * @param string $dirname
     * @param Journal $journal
     * @return string
     */
    protected function absolutePath($dirname, Journal $journal = null) {
        $path = $this->rootPath() . '/' . $dirname;
        if(substr($dirname, -1) !== '/') {
            $path .= '/';
        }
        if( ! $this->fs->exists($path)) {
            $this->fs->mkdir($path);
        }
        if($journal !== null) {
            return  $path . $journal->getUuid();
        }
        return realpath($path);
    }

    /**
     * Get the harvest directory.
     *
     * @see AppKernel#getRootDir
     * @param Journal $journal
     * @return string
     */
    public final function getHarvestDir(Journal $journal = null) {
        $path = $this->absolutePath('received', $journal);
		if( ! $this->fs->exists($path)) {
            $this->logger->notice("Creating directory {$path}");
			$this->fs->mkdir($path);
		}
		return $path;
    }

    /**
     * Get the processing directory.
     *
     * @param Journal $journal
     * @return string
     */
    public final function getProcessingDir(Journal $journal) {
        $path = $this->absolutePath('processing', $journal);
		if( ! $this->fs->exists($path)) {
            $this->logger->notice("Creating directory {$path}");
			$this->fs->mkdir($path);
		}
		return $path;
    }

    /**
     * Get the staging directory for processed deposits.
     *
     * @param Journal $journal
     * @return string
     */
    public final function getStagingDir(Journal $journal) {
        $path = $this->absolutePath('staged', $journal);
		if( ! $this->fs->exists($path)) {
            $this->logger->notice("Creating directory {$path}");
			$this->fs->mkdir($path);
		}
		return $path;
    }
    
    public function getOnixPath() {
        return $this->rootPath() . '/onix.xml';
    }
}
