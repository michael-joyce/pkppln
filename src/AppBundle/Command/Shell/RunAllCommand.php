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

namespace AppBundle\Command\Shell;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Run all the processing commands in order.
 */
class RunAllCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pln:run-all');
        $this->setDescription('Run all processing commands.');
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force the processing state to be updated'
        );
        $this->addOption(
            'limit',
            'l',
            InputOption::VALUE_OPTIONAL,
            'Only process $limit deposits.'
        );
        parent::configure();
    }

    /**
     * List of commands to run.
     *
     * @return string[]
     */
    private static function commandList()
    {
        return array(
            'pln:harvest',
            'pln:validate-payload',
            'pln:validate-bag',
            'pln:validate-xml',
            'pln:virus-scan',
            'pln:reserialize',
            'pln:deposit',
            'pln:status',
        );
    }

    /**
     * Execute the runall command, which executes all the commands.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach (self::commandList() as $cmd) {
            $output->writeln("Running {$cmd}");
            $command = $this->getApplication()->find($cmd);
            $command->run($input, $output);
        }
    }
}
