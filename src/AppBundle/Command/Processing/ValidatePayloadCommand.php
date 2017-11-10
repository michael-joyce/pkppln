<?php

/*
 * Copyright (C) 2015-2016 Michael Joyce <ubermichael@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace AppBundle\Command\Processing;

use AppBundle\Entity\Deposit;
use Exception;

/**
 * Validate the size and checksum of a downloaded deposit.
 */
class ValidatePayloadCommand extends AbstractProcessingCmd
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pln:validate-payload');
        $this->setDescription('Validate PLN deposit packages.');
        parent::configure();
    }
    
    protected function hashFile($algorithm, $filepath) {
        $handle = fopen($filepath, "r");
        $context = hash_init($algorithm);
        while(($data = fread($handle, 64 * 1024))) {
            hash_update($context, $data);
        }
        $hash = hash_final($context);
        fclose($handle); 
        return $hash;
    }

    /**
     * {@inheritdoc}
     */
    protected function processDeposit(Deposit $deposit)
    {
        $depositPath = $this->filePaths->getHarvestFile($deposit);

        if (!$this->fs->exists($depositPath)) {
            throw new Exception("Cannot find deposit bag {$depositPath}");
        }

        $checksumValue = $this->hashFile($deposit->getChecksumType(),$depositPath);
        if ($checksumValue !== $deposit->getChecksumValue()) {
            $deposit->addErrorLog("Deposit checksum does not match. Expected {$deposit->getChecksumValue()} != Actual ".strtoupper($checksumValue));
            $this->logger->warning("Deposit checksum does not match for deposit {$deposit->getDepositUuid()}");
            
            return false;
        }

        $this->logger->info("Deposit {$depositPath} validated.");

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function nextState()
    {
        return 'payload-validated';
    }

    /**
     * {@inheritdoc}
     */
    public function processingState()
    {
        return 'harvested';
    }

    /**
     * {@inheritdoc}
     */
    public function failureLogMessage()
    {
        return 'Payload checksum validation failed.';
    }

    /**
     * {@inheritdoc}
     */
    public function successLogMessage()
    {
        return 'Payload checksum validation succeeded.';
    }

    /**
     * {@inheritdoc}
     */
    public function errorState()
    {
        return 'payload-error';
    }
}
