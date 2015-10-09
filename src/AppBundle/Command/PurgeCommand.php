<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * app:console command to purge all data from the database and reload it from
 * fixtures.
 */
class PurgeCommand extends ContainerAwareCommand
{
    
    /**
     * {@inheritDoc}
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
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('This command will purge all data from the database. Continue y/N? ', false);
        if (!$helper->ask($input, $output, $question)) {
            return;
        }

        $question = new ConfirmationQuestion('Load database fixtures y/N? ', false);
        $fixtures = $helper->ask($input, $output, $question);

        $this->exec('doctrine:schema:drop', array('--force' => true), $output);
        $this->exec('doctrine:schema:create', array(), $output);
        if($fixtures) {
            $this->exec('doctrine:fixtures:load', array('--append' => true), $output);
        }
        $this->exec('cache:clear', array('--no-warmup' => true), $output);
        $this->exec('doctrine:cache:clear-metadata', array(), $output);
        $this->exec('doctrine:cache:clear-query', array(), $output);
        $this->exec('doctrine:cache:clear-result', array(), $output);
    }
}
