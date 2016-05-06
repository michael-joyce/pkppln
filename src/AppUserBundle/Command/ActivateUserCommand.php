<?php

namespace AppUserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Overrides ActivateUserCommand from FOSUserBundle.
 */
class ActivateUserCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('fos:user:activate')
            ->setDescription('Activate a user')
            ->setDefinition(array(
                new InputArgument('email', InputArgument::REQUIRED, 'The email'),
            ))
            ->setHelp(<<<EOT
The <info>fos:user:activate</info> command activates a user (so they will be able to log in):

  <info>php app/console fos:user:activate user@example.com</info>
EOT
            );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $email = $input->getArgument('email');

        $manipulator = $this->getContainer()->get('fos_user.util.user_manipulator');
        $manipulator->activate($email);

        $output->writeln(sprintf('User "%s" has been activated.', $email));
    }

    /**
     * @see Command
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('email')) {
            $username = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose an email:',
                function($email) {
                    if (empty($email)) {
                        throw new \Exception('Username can not be empty');
                    }

                    return $email;
                }
            );
            $input->setArgument('email', $email);
        }
    }
}
