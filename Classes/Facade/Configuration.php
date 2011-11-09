<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2007 Simon Tuck <stu@rtp.ch>, Rueegg Tuck Partner GmbH
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * @copyright 	2011 Rueegg Tuck Partner GmbH
 * @author 		Simon Tuck <stu@rtp.ch>
 * @link 		http://www.rtpartner.ch/
 * @package 	Smarty (smarty)
 **/


class Tx_Smarty_Facade_Configuration
{

    /**
     * @var null|ReflectionClass
     */
    private $smartyClass            = null;

    /**
     * @var null|Tx_Smarty_Facade_Wrapper
     */
    private $smartyInstance         = null;

    /**
     * @var string
     */
    const GETTER_ACTION             = 'get';

    /**
     * @var string
     */
    const SETTER_ACTION             = 'set';

    /**
     * @var string
     */
    const ADDER_ACTION              = 'add';

    /**
     * @param Tx_Smarty_Facade_Wrapper $smartyInstance
     */
    public function __construct(Tx_Smarty_Facade_Wrapper $smartyInstance)
    {
        if($smartyInstance instanceof Tx_Smarty_Facade_Wrapper) {
            $this->smartyInstance = $smartyInstance;
            $this->smartyClass = new ReflectionClass($smartyInstance);
        } else {
            throw new InvalidArgumentException('Configuration manager requires a valid instance of smarty!', 1320785449);
        }
    }

    /**
     *
     * @api
     * @throws BadMethodCallException|InvalidArgumentException
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public final function __call($method, array $args)
    {
        // Gets the action from the method call and throws
        // an exception for any unrecognized actions.
        $action = self::getActionFromMethod($method);
        if(!self::hasAction($action)) {
            throw new BadMethodCallException('Unknown action "' . $action . '" in method "' . $method .'"!', 1320785456);
        }

        // Gets the property from the method call and formats
        // it correctly, i.e. templateDir --> template_dir
        // Throws an exception if the smarty class doesn't have
        // a corresponding property.
        $property = self::formatPropertyName(self::getPropertyFromMethod($method));

        // Catches accessors for unknown properties
        if(!$this->smartyClass->hasProperty($property)) {
            throw new InvalidArgumentException('Unknown property "' . $property . '" in method "' . $method .'"!', 1320785462);
        }

        // Catches adders without a corresponding method in smarty
        if(self::isAdder($action) && !$this->smartyClass->hasMethod($method)) {
            throw new BadMethodCallException('Cannot use  method "' . $action . '" to access "' . $property .'"!', 1320785472);
        }

        // Sets or appends smarty configuration settings
        if(self::isSetter($action) || self::isAdder($action)) {

            // Resolves directories or files to absolute paths
            if(!empty($args) && self::isPath($args[0])) {
                $args[0] = self::getPath($args[0]);
            }

            // Use smarty's setter or adder if available
            if($this->smartyClass->hasMethod($method)) {
                call_user_func(array($this->smartyInstance, $method), $args);

            // Set the smarty property directly
            } elseif($this->smartyClass->hasProperty($property)) {
                $this->smartyInstance->{$property} = $args[0];

            }

        // Gets smarty configuration settings
        } else {

            // Use smarty's adder if available
            if($this->smartyClass->hasMethod($method)) {
                return call_user_func(array($this->smartyInstance, $method));

            // Get the smarty property directly
            } elseif($this->smartyClass->hasProperty($property)) {
                return $this->smartyInstance->{$property};

            }
        }
    }

    /**
     * @static
     * @param $method
     * @return string
     */
    private static function getActionFromMethod($method)
    {
        return strtolower(substr($method, 0, 3));
    }

    /**
     * @static
     * @param $action
     * @return bool
     */
    private static function hasAction($action)
    {
        return (boolean) (self::isSetter($action) || self::isGetter($action) || self::isAdder($action));
    }

    /**
     * @static
     * @param $action
     * @return bool
     */
    private static function isGetter($action)
    {
        return (boolean) ((string) $action === self::GETTER_ACTION);
    }

    /**
     * @static
     * @param $action
     * @return bool
     */
    private static function isSetter($action)
    {
        return (boolean) ((string) $action === self::SETTER_ACTION);
    }

    /**
     * @static
     * @param $action
     * @return bool
     */
    private static function isAdder($action)
    {
        return (boolean) ((string) $action === self::ADDER_ACTION);
    }

    /**
     * @static
     * @param $setting
     * @return bool
     */
    private static function isPath($setting)
    {
        return (boolean) (strtolower(substr($setting, -3)) === 'dir');
    }

    /**
     * @static
     * @param $dirs
     * @return array
     */
    private static function getPath($dirs)
    {
        $paths = $dirs;
        if(is_array($dirs)) {
            while($dir = array_shift($dirs)) {
                $path = t3lib_div::getFileAbsFileName($dir);
                $paths[] = (is_dir($path) && substr($path, -1) !== DS) ? $path . DS : $path;
            }
        } elseif(is_scalar($dirs)) {
            $paths  = t3lib_div::getFileAbsFileName($dirs);
            $paths .= (is_dir($paths) && substr($paths, -1) !== DS) ? DS : '';
        }
        return $paths;
    }

    /**
     * @static
     * @param $method
     * @return string
     */
    private static function getPropertyFromMethod($method)
    {
        return substr($method, 3, strlen($method) - 3);
    }

    /**
     * @static
     * @param $property
     * @return string
     */
    private static function formatPropertyName($property)
    {
        return substr(strtolower(preg_replace('/([A-Z])/', '_\1', $property)), 1);
    }
}
