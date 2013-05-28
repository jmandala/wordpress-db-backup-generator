<?php
/**
 *
 * Creates a back of the WP database including header information for
 * restoring with the same user accounts.
 *
 * Reads a config file, wp-config.php works nicely, or gets parameters from the command line
 */

// setup
$generator = new wp_db_backup_generator();
$generator->write_file();

// write file

class wp_db_backup_generator {

    private $_params;

    function __construct() {

    }

    function write_file() {
        file_put_contents($this->get_params('out_file'), $this->mk_sql());
        return true;
    }

    function get_params($key = null) {
        if (!$this->_params) {
            $this->_params = $this->init();
        }
        if ($key) {
            return $this->_params[$key];
        }
        return $this->_params;
    }

    function mk_sql() {
        $db_dump = $this->get_db_dump();
        $db_header = $this->get_header();

        $sql = $db_header . $db_dump;
        return $sql;
    }


    function get_db_dump() {
        $params = $this->get_params();
        $dump_cmd = "mysqldump -u{$params['db_user']} -p{$params['db_password']} -h{$params['db_host']} {$params['db_name']}";
        $db_dump = shell_exec($dump_cmd);
        return $db_dump;
    }

    function get_header() {
        $params = $this->get_params();
        $db_header = "SET FOREIGN_KEY_CHECKS=0;\n";
        $db_header .= "DROP DATABASE IF EXISTS {$params['db_name']};\n";
        $db_header .= "CREATE DATABASE IF NOT EXISTS {$params['db_name']};\n";
        $db_header .= "GRANT ALL PRIVILEGES ON {$params['db_name']}.* TO {$params['db_user']}@'%' IDENTIFIED BY '{$params['db_pass']}' WITH GRANT OPTION;\n";
        $db_header .= "GRANT ALL PRIVILEGES ON {$params['db_name']}.* TO {$params['db_user']}@localhost IDENTIFIED BY '{$params['db_pass']}' WITH GRANT OPTION;\n";
        $db_header .= "USE {$params['db_name']};\n";
        return $db_header;
    }


    function init() {

        // Setup
        $short_opts = "c:"; // path to config file, by default looks for a file named config.php in the same directory
        $short_opts .= "n"; // dry-run, prints out the command without running
        $short_opts .= "v"; // verbose output
        $short_opts .= "d:"; // database name
        $short_opts .= "u:"; // user name
        $short_opts .= "p:"; // password
        $short_opts .= "h:"; // host

        $options = getopt($short_opts);


        // Read config file to get default values
        if (array_key_exists("c", $options)) {
            $path = $options['c'];
        } else {
            $path = null;
        }

        $params = $this->read_config($path);

        // Overwrite with any passed in arguments
        if (array_key_exists("d", $options)) {
            $params['db_name'] = $options['d'];
        }
        if (array_key_exists("h", $options)) {
            $params['db_host'] = $options['h'];
        }
        if (array_key_exists("u", $options)) {
            $params['db_user'] = $options['u'];
        }
        if (array_key_exists("p", $options)) {
            $params['db_password'] = $options['p'];
        }

        if (array_key_exists('o', $options)) {
            $params['out_file'] = $options['o'];
        } else {
            date_default_timezone_set("UTC");
            $date_part = date("Ymd.H.i.s");
            $params['out_file'] = "{$params['db_name']}-{$date_part}.sql";
        }

        return $params;

    }


    function read_config($path) {
        if (!$path) {
            $path = "config.php";
        }
        $abs_path = __DIR__ . "/" . $path;
        if (!file_exists($abs_path)) {
            throw new Exception("Config file could not be found: " . $abs_path);
        }

        /** @noinspection PhpIncludeInspection */
        require_once($abs_path);

        return array('db_name' => DB_NAME, 'db_password' => DB_PASSWORD, 'db_host' => DB_HOST, 'db_user' => DB_USER);

    }

}