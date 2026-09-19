<?php
/**
 * PHPUnit bootstrap: loads WP_Mock so tests can run without a WordPress install.
 */

require_once __DIR__ . '/../vendor/autoload.php';

define( 'ABSPATH', sys_get_temp_dir() . '/wp/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'KB_IN_BYTES', 1024 );
define( 'ARRAY_A', 'ARRAY_A' );

\WP_Mock::bootstrap();
