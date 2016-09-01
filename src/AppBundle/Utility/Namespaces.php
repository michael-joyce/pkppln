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

use ReflectionClass;
use SimpleXMLElement;

/**
 * Simplify handling namespaces for SWORD XML documents.
 */
class Namespaces
{
    const DCTERMS = 'http://purl.org/dc/terms/';
    const SWORD = 'http://purl.org/net/sword/';
    const ATOM = 'http://www.w3.org/2005/Atom';
    const LOM = 'http://lockssomatic.info/SWORD2';
    const RDF = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    const APP = 'http://www.w3.org/2007/app';
    const PKP = 'http://pkp.sfu.ca/SWORD';

    /**
     * Get the FQDN for the prefix, in a case-insensitive
     * fashion.
     *
     * @param string $prefix
     *
     * @return string
     */
    public function getNamespace($prefix)
    {
        $constant = get_class().'::'.strtoupper($prefix);
        if (!defined($constant)) {
            return;
        }

        return constant($constant);
    }

    /**
     * Register all the known namespaces in a SimpleXMLElement.
     *
     * @param SimpleXMLElement $xml
     */
    public function registerNamespaces(SimpleXMLElement $xml)
    {
        $refClass = new ReflectionClass(__CLASS__);
        $constants = $refClass->getConstants();
        foreach (array_keys($constants) as $key) {
            $prefix = strtolower($key);
            $xml->registerXPathNamespace($prefix, $this->getNamespace($prefix));
        }
    }
}
