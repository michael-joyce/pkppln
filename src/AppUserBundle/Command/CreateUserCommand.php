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

namespace AppUserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Overrides the CreateUserCommand from FOSUserBundle to add support for
 * fullname and institution.
 */
class CreateUserCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('fos:user:create')
            ->setDescription('Create a user.')
            ->setDefinition(array(
                new InputArgument('email', InputArgument::REQUIRED, 'The email'),
                new InputArgument('password', InputArgument::REQUIRED, 'The password'),
                new InputArgument('fullname', InputArgument::REQUIRED, 'The full name'),
                new InputArgument('institution', InputArgument::REQUIRED, 'The institution'),
                new InputOption('super-admin', null, InputOption::VALUE_NONE, 'Set the user as super admin'),
                new InputOption('inactive', null, InputOption::VALUE_NONE, 'Set the user as inactive'),
            ))
            ->setHelp(<<<EOT
The <info>fos:user:create</info> command creates a user:

  <info>php app/console fos:user:create user@example.com</info>

This interactive shell will ask you for a password.

You can alternatively specify the email and password as the first and second arguments:

  <info>php app/console fos:user:create matthieu@example.com mypassword</info>

You can create a super admin via the super-admin flag:

  <info>php app/console fos:user:create admin@example.com --super-admin</info>

You can create an inactive user (will not be able to log in):

  <info>php app/console fos:user:create user@example.com --inactive</info>

EOT
            );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $fullname = $input->getArgument('fullname');
        $institution = $input->getArgument('institution');
        $inactive = $input->getOption('inactive');
        $superadmin = $input->getOption('super-admin');

        $manipulator = $this->getContainer()->get('appuserbundle.user_manipulator');
        $manipulator->create($email, $password, $fullname, $institution, !$inactive, $superadmin);

        $output->writeln(sprintf('Created user <comment>%s</comment>', $email));
    }

    /**
     * @see Command
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('email')) {
            $email = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose an email:',
                function ($email) {
                    if (empty($email)) {
                        throw new \Exception('Email can not be empty');
                    }

                    return $email;
                }
            );
            $input->setArgument('email', $email);
        }

        if (!$input->getArgument('fullname')) {
            $fullname = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose a fullname:',
                function ($fullname) {
                    if (empty($fullname)) {
                        throw new \Exception('fullname can not be empty');
                    }

                    return $fullname;
                }
            );
            $input->setArgument('fullname', $fullname);
        }

        if (!$input->getArgument('institution')) {
            $institution = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose a institution:',
                function ($institution) {
                    if (empty($institution)) {
                        throw new \Exception('institution can not be empty');
                    }

                    return $institution;
                }
            );
            $input->setArgument('institution', $institution);
        }

        if (!$input->getArgument('password')) {
            $password = $this->getHelper('dialog')->askHiddenResponseAndValidate(
                $output,
                'Please choose a password:',
                function ($password) {
                    if (empty($password)) {
                        throw new \Exception('Password can not be empty');
                    }

                    return $password;
                }
            );
            $input->setArgument('password', $password);
        }
    }
}
