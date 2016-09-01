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
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * app:console command to purge all data from the database and reload it from
 * fixtures. This is a dangerous command, and should probably be removed.
 */
class PurgeCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pln:purge')
                ->setDescription('Purge *ALL* data from the database.');
    }

    /**
     * Execute one command with arguments. Adds the executing command name to
     * the arguments as required by the command executor.
     *
     * @param string          $cmd
     * @param array           $args
     * @param OutputInterface $output
     *
     * @return int
     */
    private function exec($cmd, $args, $output)
    {
        $command = $this->getApplication()->find($cmd);
        $args['command'] = $cmd;
        $input = new ArrayInput($args);
        $rc = $command->run($input, $output);

        return $rc;
    }

    /**
     * Entry point for the command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return type
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->getContainer()->get('kernel')->getEnvironment() === 'prod') {
            $output->writeln('This command cannot be used in production.');
            exit;
        }
        $helper = $this->getHelper('question');
        $confirmQ = new ConfirmationQuestion('This command will purge all data from the database. Continue y/N? ', false);
        if (!$helper->ask($input, $output, $confirmQ)) {
            return;
        }

        $loadQ = new ConfirmationQuestion('Load database fixtures y/N? ', false);
        $fixtures = $helper->ask($input, $output, $loadQ);

        $this->exec('doctrine:schema:drop', array('--force' => true), $output);
        $this->exec('doctrine:schema:create', array(), $output);
        if ($fixtures) {
            $this->exec('doctrine:fixtures:load', array('--append' => true), $output);
        }
        $this->exec('cache:clear', array('--no-warmup' => true), $output);
        $this->exec('doctrine:cache:clear-metadata', array(), $output);
        $this->exec('doctrine:cache:clear-query', array(), $output);
        $this->exec('doctrine:cache:clear-result', array(), $output);
    }
}
