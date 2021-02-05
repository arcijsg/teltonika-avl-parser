<?php
/**
 * Expects to be invoked at regular intervals (cron as easiest examole, any other
 * task runners / shcedulers welcomed :)
 */

 define("DS", DIRECTORY_SEPARATOR);
$here = __FILE__;
define("PATH_APP_ROOT", realpath($here.DS.".."));

// MARK: Bootstrap
require_once(PATH_APP_ROOT.DS."vendor".DS."autoload.php");
