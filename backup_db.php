<?php
/**
 *
 * Creates a back of the WP database including header information for
 * restoring with the same user accounts.
 *
 * Reads a config file, wp-config.php works nicely, or gets parameters from the command line
 */

// setup
error_reporting(E_ERROR | E_PARSE);

// run!
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

        /** @noinspection PhpIncludeInspection */
        $parser = new wp_config_parser();
        $results = $parser->parse($path);

        return array('db_name' => $results['db_name'], 'db_password' => $results['db_password'], 'db_host' => $results['db_host'], 'db_user' => $results['db_user']);

    }

}

class wp_config_parser {

    static function parse( $path = null) {

        if (!$path) {
            $path = "config.php";
        }

        $abs_path = __DIR__ . "/" . $path;
        if (!file_exists($abs_path)) {
            throw new Exception("Config file could not be found: " . $abs_path);
        }

        $path = $abs_path;
        $file_content = null;

        if ( file_exists( $path ) && is_file( $path ) && is_readable( $path ) ) {
            $file = @fopen( $path, 'r' );
            $file_content = fread( $file, filesize( $path ) );
            @fclose( $file );
        }

        $params = array();

        preg_match_all( '/define\s*?\(\s*?([\'"])(DB_NAME|DB_USER|DB_PASSWORD|DB_HOST|DB_CHARSET)\1\s*?,\s*?([\'"])([^\3]*?)\3\s*?\)\s*?;/si', $file_content, $defines );

        if ( ( isset( $defines[ 2 ] ) && ! empty( $defines[ 2 ] ) ) && ( isset( $defines[ 4 ] ) && ! empty( $defines[ 4 ] ) ) ) {
            foreach( $defines[ 2 ] as $key => $define ) {

                switch( $define ) {
                    case 'DB_NAME':
                        $params['db_name'] = $defines[ 4 ][ $key ];

                        break;
                    case 'DB_USER':
                        $params['db_user'] = $defines[ 4 ][ $key ];
                        break;
                    case 'DB_PASSWORD':
                        $params['db_password'] = $defines[ 4 ][ $key ];
                        break;
                    case 'DB_HOST':
                        $params['db_host'] = $defines[ 4 ][ $key ];
                        break;
                    case 'DB_CHARSET':
                        $params['db_charset']  = $defines[ 4 ][ $key ];
                        break;
                }
            }
        }

        return $params;
    }

}