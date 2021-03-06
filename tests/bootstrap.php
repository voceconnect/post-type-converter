<?php
$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';

// Add this plugin to WordPress for activation so it can be tested.
$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array( 'post-type-converter/post-type-converter.php' ),
);

require_once dirname( __FILE__ ) . '/pluggable.php';

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
        require dirname( __FILE__ ) . '/../post-type-converter.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

require_once dirname( __FILE__ ) . '/Voce_WP_UnitTestCase.php';
