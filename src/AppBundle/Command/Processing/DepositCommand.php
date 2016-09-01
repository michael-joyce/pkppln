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
use AppBundle\Services\SwordClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Send a fully processed deposit to LOCKSSOMatic.
 * 
 * @see SwordClient
 */
class DepositCommand extends AbstractProcessingCmd
{
    /**
     * @var SwordClient
     */
    private $client;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pln:deposit');
        $this->setDescription('Send deposits to LockssOMatic.');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->client = $container->get('sword_client');
        $this->client->setLogger($this->logger);
    }

    /**
     * Process one deposit. Fetch the data and write it to the file system.
     * Updates the deposit status.
     *
     * @param Deposit $deposit
     *
     * @return type
     */
    protected function processDeposit(Deposit $deposit)
    {
        $this->logger->notice("Sending deposit {$deposit->getDepositUuid()}");

        return $this->client->createDeposit($deposit);
    }

    /**
     * {@inheritdoc}
     */
    public function nextState()
    {
        return 'deposited';
    }

    /**
     * {@inheritdoc}
     */
    public function processingState()
    {
        return 'reserialized';
    }

    /**
     * {@inheritdoc}
     */
    public function failureLogMessage()
    {
        return 'Deposit to Lockssomatic failed.';
    }

    /**
     * {@inheritdoc}
     */
    public function successLogMessage()
    {
        return 'Deposit to Lockssomatic succeeded.';
    }

    /**
     * {@inheritdoc}
     */
    public function errorState()
    {
        return 'deposit-error';
    }
}
