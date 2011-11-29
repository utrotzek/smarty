<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2007 Simon Tuck <stu@rtpartner.ch>, Rueegg Tuck Partner GmbH
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
 * @copyright 2007 Rueegg Tuck Partner GmbH
 * @author Simon Tuck <stu@rtpartner.ch>
 * @link http://www.rtpartner.ch/
 * @package Smarty (smarty)
 **/
class Tx_Smarty_Utility_TypoScript
{

    /**
     * @param array $parameters
     * @return array
     */
    public function getSetupFromParameters(array $parameters = array())
    {
        // Setup is the array of parameters passed to the smarty plugin
        $setup = array();

        // "setup" is a special parameter which can point to a value in the
        // current global TypoScript scope. Retrieves the matching value from
        // the current global TypoScript scope if "setup" is defined.
        if (isset($parameters['setup'])) {
            list($setup, $type) = self::getSetupFromTypo3($parameters['setup']);
            unset($parameters['setup']);
        }

        // Converts the remaining parameters to a TypoScript array.
        if(!empty($parameters)) {

            // Parameters will recursively override any setup from the "setup" parameter.
            if (!empty($setup)) {
                $tmpSetup = Tx_Smarty_Utility_TypoScript::getTypoScriptFromParameters($parameters);
                $setup = t3lib_div::array_merge_recursive_overrule($setup, $tmpSetup);

            } else {
                $setup = Tx_Smarty_Utility_TypoScript::getTypoScriptFromParameters($parameters);;
            }
        }
        
        return array($setup, $type);
    }

    /**
     *
     * Gets TypoScript from the current global TypoScript setup array
     *
     * @static
     * @see t3lib_TSparser::getVal($string, $setup)
     * @param string $string Object path for which to get the value
     * @return array
     */
    private static function getSetupFromTypo3($string)
    {

        // Cast the object path to a string
        $objPath = trim((string) $string);

        // Break the object path down by periods (.) excluding the last part
        // which could also point to the object type. So that, for example, if
        // the typoscript path is "lib.foo.bar" both the OBJECT_TYPE and it's
        // configuration are picked up:
        // lib.foo.bar = OBJECT_TYPE
        // lib.foo.bar.file = /path/to/file
        $objPathParts = Tx_Smarty_Utility_Array::trimExplode('.', $objPath);
        $lastObjPathPart = array_pop($objPathParts);

        // The current global TypoScript setup array
        $setup = $GLOBALS['TSFE']->tmpl->setup;

        // Iterate through the global TypoScript scope, throw an exception
        // if any part of the object path can't be found.
        while($objPathPart = array_shift($objPathParts)) {
            if (isset($setup[$objPathPart . '.'])) {
                $setup = $setup[$objPathPart . '.'];
            } else {
                throw new Exception('Stop');
            }
        }

        // The last part of the object path should get the configuration, otherwise
        // throw an exception.
        if (isset($setup[$lastObjPathPart . '.'])) {
            $setup = $setup[$lastObjPathPart . '.'];
        } else {
            throw new Exception('No Way!');
        }

        // The last part of the object path might also point to the object type.
        if (isset($setup[$lastObjPathPart])) {
            $type = $setup[$lastObjPathPart];
        } else {
            $type = null;
        }

        // Return the object configuration and type
        return array($setup, $type);
    }

    /**
     * @static
     * @param array $parameters
     * @return array
     */
    private static function getTypoScriptFromParameters(array $parameters = array())
    {
        $typoscript = array();
        foreach($parameters as $parameter => $value) {
            $properties = Tx_Smarty_Utility_Array::trimExplode('.', $parameter);
            $setting = self::convertParameterToTypoScript($value, $properties);
            $typoscript = t3lib_div::array_merge_recursive_overrule($typoscript, $setting);
        }
        return $typoscript;
    }

    /**
     * @static
     * @param $value
     * @param array $settings
     * @param array $setting
     * @return array
     */
    private static function convertParameterToTypoScript($value, array $settings = array(), array $setting = array())
    {
        $property = array_shift($settings);
        if(count($settings) > 0) {
            $setting[$property . '.'] = self::convertParameterToTypoScript($value, $settings, $setting);
        } else {
            $setting[$property] = $value;
        }
        return $setting;
    }
}