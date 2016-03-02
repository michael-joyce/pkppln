<?php

namespace AppBundle\Services;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Journal;
use Monolog\Logger;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Calculate file paths.
 */
class FilePaths {
    
    /**
     * Base directory where the files are stored.
     *
     * @var string
     */
    private $baseDir;
    
    /**
     * Symfony filesystem object.
     *
     * @var FileSystem
     */
    private $fs;
    
    /**
     * Kernel environment, a path on the file system.
     *
     * @var string
     */
    private $env;
    
    /**
     * @var Logger
     */
    private $logger;

    /**
     * Build the service.
     */
    public function __construct() {
        $this->fs = new Filesystem();
    }
    
    /**
     * Set the service logger
     * 
     * @param Logger $logger
     */
    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }

    /**
     * Set the kernel environment.
     * 
     * @param string $env
     */
    public function setKernelEnv($env) {
        $this->env = $env;
    }
    
    /**
     * Set the file system base directory.
     * 
     * @param type $dir
     */
    public function setBaseDir($dir) {
        if(substr($dir, -1) !== '/') {
            $this->baseDir = $dir . '/';
        } else {
            $this->baseDir = $dir;
        }
    }
    
    /**
     * Get the root dir, based on the baseDir.
     * 
     * @return string
     */
    protected function rootPath() {
        $path = $this->baseDir;
        if ( ! $this->fs->isAbsolutePath($path)) {
            $root = dirname($this->env);
            $path =  $root . '/' . $path;
        }
        return realpath($path);
    }
    
    /**
     * Get an absolute path to a processing directory for the journal.
     * 
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
	
	public final function getHarvestFile(Deposit $deposit) {
		$path = $this->getHarvestDir($deposit->getJournal());
		return realpath($path . '/' . $deposit->getFileName());
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
	
	public function getProcessingBagPath(Deposit $deposit) {
		$path = $this->getProcessingDir($deposit->getJournal());
		return $path . '/' . $deposit->getDepositUuid();
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
	
	public final function getStagingBagPath(Deposit $deposit) {
		$path = $this->getStagingDir($deposit->getJournal());
		return $path . '/' . $deposit->getFileName();
	}
    
    /**
     * Get the path to the onix feed XML file.
     * 
     * @return string
     */
    public function getOnixPath() {
        return $this->rootPath() . '/onix.xml';
    }
}
