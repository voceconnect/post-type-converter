<?php

/**
 * Tests to test that that testing framework is testing tests. Meta, huh?
 *
 * @package wordpress-plugins-tests
 */
class Test_Post_Type_Converter extends WP_UnitTestCase {

	/**
	 * If these tests are being run on Travis CI, verify that the version of
	 * WordPress installed is the version that we requested.
	 *
	 * @requires PHP 5.3
	 */
	function test_wp_version() {

		if ( !getenv( 'TRAVIS' ) )
			$this->markTestSkipped( 'Test skipped since Travis CI was not detected.' );

		$requested_version = getenv( 'WP_VERSION' ) . '-src';

		// The "master" version requires special handling.
		if ( $requested_version == 'master-src' ) {
			$file = file_get_contents( 'https://raw.github.com/tierra/wordpress/master/src/wp-includes/version.php' );
			preg_match( '#\$wp_version = \'([^\']+)\';#', $file, $matches );
			$requested_version = $matches[1];
		}

		$this->assertEquals( get_bloginfo( 'version' ), $requested_version );

	}

	/**
	 * Ensure that the plugin has been installed and activated.
	 */
	function test_plugin_activated() {

		$this->assertTrue( is_plugin_active( 'post-type-converter/post-type-converter.php' ) );

	}

	/**
	 * Ensure that the plugin's initialize method is hooked in
	 */
	function test_initialize_init_hook() {

		$priority = has_action( 'init', array( 'Post_Type_Converter', 'initialize' ) );

		$this->assertGreaterThan( 0, $priority );

	}

	/**
	 * Ensure that main hooks are set for "manage_options" users
	 */
	function test_hooks_for_manage_options_user() {

		$user = $this->factory->user->create_and_get( array(
			'role' => 'administrator'
		) );

		$this->assertTrue( $user->has_cap( 'manage_options' ), 'Administrator role user does not have "manage_options" capability.' );

		wp_set_current_user( $user->ID );

		$this->assertEquals( $user->ID, get_current_user_id(), "User {$user->ID} is not current user." );

		Post_Type_Converter::initialize();

		$hooks = array(
			'add_meta_boxes'		=> 'add_convert_meta_box',
			'save_post'				=> 'save_convert',
			'admin_enqueue_scripts'	=> 'add_bulk_edit_js',
			'admin_init'			=> 'check_bulk_convert'
		);

		foreach ( $hooks as $hook => $callback ) {

			$priority = has_action( $hook, array( 'Post_Type_Converter', $callback ) );

			$this->assertGreaterThan( 0, $priority, "Post_Type_Converter::{$callback} not attached to {$hook}." );

		}

	}

}
