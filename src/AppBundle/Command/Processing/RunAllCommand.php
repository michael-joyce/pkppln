<?php

namespace AppBundle\Command\Processing;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunAllCommand extends ContainerAwareCommand {

    protected $container;

    /**
     * Configure the command.
     */
    protected function configure() {
        $this->setName('pln:run-all');
        $this->setDescription('Run all processing commands.');
        $this->addOption(
                'force', 'f', InputOption::VALUE_NONE, 'Force the processing state to be updated'
        );
        parent::configure();
    }

    private static function commandList() {
        return array(
            'pln:harvest',
            'pln:validate-payload',
            'pln:validate-bag',
            'pln:virus-scan',
            'pln:validate-xml',
            'pln:reserialize',
            'pln:deposit',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        foreach(self::commandList() as $cmd) {
            $output->writeln("Running {$cmd}");
            $command = $this->getApplication()->find($cmd);
            $command->run($input, $output);
        }
    }

}
