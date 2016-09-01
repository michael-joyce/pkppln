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

namespace AppUserBundle\Util;

use AppUserBundle\Entity\User;
use FOS\UserBundle\Model\UserManagerInterface;

/**
 * Custom user manipulator which adds support for fullname and institution, which
 * are not part of the stock FOSUserBundle.
 * 
 * http://stackoverflow.com/questions/11595261/override-symfony2-console-commands
 */
class UserManipulator
{
    /**
     * User manager.
     *
     * @var UserManagerInterface
     */
    private $userManager;

    public function __construct(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * Creates a user and returns it.
     *
     * @param string $email
     * @param string $password
     * @param string $fullname
     * @param string $institution
     * @param bool   $active
     * @param bool   $superadmin
     *
     * @return User
     */
    public function create($email, $password, $fullname, $institution, $active, $superadmin)
    {
        $user = $this->userManager->createUser();
        $user->setEmail($email);
        $user->setPlainPassword($password);
        $user->setFullname($fullname);
        $user->setInstitution($institution);
        $user->setEnabled($active);
        $user->setSuperAdmin($superadmin);
        $this->userManager->updateUser($user);

        return $user;
    }
}
