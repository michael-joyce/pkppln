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

namespace AppBundle\Utility;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * This class is a wrapper around AbstractFixture. When loading data it checks
 * which environment is active, and then only loads fixtures for that
 * environment.
 *
 * http://stackoverflow.com/questions/11817971
 */
abstract class AbstractDataFixture extends AbstractFixture implements ContainerAwareInterface, OrderedFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Check if the class should load data, and then load it via the overridden
     * doLoad() method.
     */
    final public function load(ObjectManager $em)
    {
        /** @var KernelInterface $kernel */
        $kernel = $this->container->get('kernel');
        if (in_array($kernel->getEnvironment(), $this->getEnvironments())) {
            $this->doLoad($em);
        } else {
            $this->container->get('logger')->notice('skipped.');
        }
    }

    /**
     * {@inheritdoc}.
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}.
     */
    public function getOrder()
    {
        return 1;
    }

    /**
     * Load the data into the database.
     *
     * @param ObjectManager $manager
     */
    abstract protected function doLoad(ObjectManager $manager);

    /**
     * Fixtures use this function to return an array listing the environments
     * that they should be activated for.
     */
    abstract protected function getEnvironments();
}
