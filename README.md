wordpress-db-backup-generator
=============================

Creates a WordPress DB backup that can be easily restored using the same user account as the original database.

Usage
-----

By default it will look for a file named `config.php` in the same directory to initialize variables from. See 
config.php.sample for an example. 

A good use of this program is to read the wp_config.php file directly. Just pass `-c rel/path/to/wp_config.php`.

### Arguments

`-c` Relative path to the config file

`-d` Databse name

`-u` DB user name

`-p` Password

`-h` Host

### Note
* Will fail if any of the required arguments are missing.
* Reads the DB dump to memory so it will be a problem on a big databse.
