<?php
/**
 *
 * Creates a back of the WP database including header information for
 * restoring with the same user accounts.
 *
 * Reads a config file, wp-config.php works nicely, or gets parameters from the command line
 */

// Setup
$short_opts = "c:"; // path to config file, by default looks for a file named config.php in the same directory
$short_opts .= "n"; // dry-run, prints out the command without running
$short_opts .= "v"; // verbose output

validate();
verbose_info();

read_config();

function read_config($path = "config.php") {
    $abs_path = __DIR__ . "/" . $path;
    if (!file_exists($abs_path)) {
        throw new Exception("Config file could not be found: ".$abs_path);
    }

    require_once($abs_path);
}

function validate() {
    if (!defined("DB_NAME")) define("DB_NAME", null);
    if (!defined("DB_USER")) define("DB_USER", null);
    if (!defined("DB_PASSWORD")) define("DB_PASSWORD", null);
    if (!defined("DB_HOST")) define("DB_HOST", null);
}

function verbose_info() {
    /** @var $DB_NAME string */
    echo "\nDB_NAME = ". DB_NAME;
    echo "\nDB_USER = ". DB_USER;
    echo "\nDB_PASSWORD = ". DB_PASSWORD;
    echo "\nDB_HOST = ". DB_HOST;
    echo "\n";
}