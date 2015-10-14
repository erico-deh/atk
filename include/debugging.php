<?php
/**
 * This file is part of the ATK distribution on GitHub.
 * Detailed copyright and licensing information can be found
 * in the doc/COPYRIGHT and doc/LICENSE files which should be
 * included in the distribution.
 *
 * @package atk
 * @subpackage include
 *
 * @copyright (c)2000-2004 Ibuildings.nl BV
 * @license http://www.achievo.org/atk/licensing ATK Open Source License
 *
 * @version $Revision: $
 * $Id$
 */
if (Atk_Tools::atk_value_in_array($GLOBALS['config_smart_debug'])) {
    $GLOBALS['config_debug'] = Atk_Config::smartDebugLevel($GLOBALS['config_debug'], $GLOBALS['config_smart_debug']);
}

if ($GLOBALS['config_debug'] > 0) {
    ini_set('display_errors', 1);
}

// show server info in debug (useful in clustered environments)
Atk_Tools::atkdebug('Server info: ' . $_SERVER['SERVER_NAME'] . ' (' . $_SERVER['SERVER_ADDR'] . ')');
