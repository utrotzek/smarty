<?php

/**
 * Smarty plugin "env"
 * -------------------------------------------------------------
 * File:    function.env.php
 * Type:    function
 * Name:    System Environment Variables
 * Version: 1.0
 * Author:  Simon Tuck <stu@rtp.ch>, Rueegg Tuck Partner GmbH
 * Purpose: Provides access to t3lib_div::getIndpEnv
 * Example: {env name="TYPO3_SITE_URL"}
 * -------------------------------------------------------------
 *
 * @see t3lib_div::getIndpEnv
 * @param $params
 * @param Smarty_Internal_Template $template
 * @return string
 * @throws Tx_Smarty_Exception_PluginException
 */
function smarty_function_env($params, Smarty_Internal_Template $template)
{
    $params = array_change_key_case($params, CASE_LOWER);
    if (!isset($params['name'])) {
        $msg = 'The setting "name" is required smarty templating function {env}!';
        throw new Tx_Smarty_Exception_PluginException($msg, 1331633555);
    }

    //
    return t3lib_div::getIndpEnv(strtoupper($params['name']));
}