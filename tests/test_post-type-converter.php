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
	 * Ensure that main hooks are *not* set when no user logged in
	 */
	function test_hooks_for_no_user() {

		$this->assertEquals( 0, get_current_user_id(), "There should not be a current user." );

		Post_Type_Converter::initialize();

		$hooks = array(
			'add_meta_boxes'        => 'add_convert_meta_box',
			'save_post'             => 'save_convert',
			'admin_enqueue_scripts' => 'add_bulk_edit_js',
			'admin_init'            => 'check_bulk_convert'
		);

		foreach ( $hooks as $hook => $callback ) {

			$priority = has_action( $hook, array( 'Post_Type_Converter', $callback ) );

			$this->assertFalse( $priority, "Post_Type_Converter::{$callback} attached to {$hook}." );

		}

	}

	/**
	 * Ensure that main hooks are *NOT* set for unprivileged users
	 */
	function test_hooks_for_non_privileged_user() {

		$user = $this->factory->user->create_and_get( array(
			'role' => 'editor'
		) );

		$this->assertFalse( $user->has_cap( 'manage_options' ), 'Author role user has "manage_options" capability.' );

		wp_set_current_user( $user->ID );

		$this->assertEquals( $user->ID, get_current_user_id(), "User {$user->ID} is not current user." );

		Post_Type_Converter::initialize();

		$hooks = array(
			'add_meta_boxes'        => 'add_convert_meta_box',
			'save_post'             => 'save_convert',
			'admin_enqueue_scripts' => 'add_bulk_edit_js',
			'admin_init'            => 'check_bulk_convert'
		);

		foreach ( $hooks as $hook => $callback ) {

			$priority = has_action( $hook, array( 'Post_Type_Converter', $callback ) );

			$this->assertFalse( $priority, "Post_Type_Converter::{$callback} attached to {$hook}." );

		}

	}

	/**
	 * Ensure that main hooks are set for "manage_options" users
	 *
	 * NOTE: after this test, the hooks will be set!
	 */
	function test_hooks_for_privileged_user() {

		$user = $this->factory->user->create_and_get( array(
			'role' => 'administrator'
		) );

		$this->assertTrue( $user->has_cap( 'manage_options' ), 'Administrator role user does not have "manage_options" capability.' );

		wp_set_current_user( $user->ID );

		$this->assertEquals( $user->ID, get_current_user_id(), "User {$user->ID} is not current user." );

		Post_Type_Converter::initialize();

		$hooks = array(
			'add_meta_boxes'        => 'add_convert_meta_box',
			'save_post'             => 'save_convert',
			'admin_enqueue_scripts' => 'add_bulk_edit_js',
			'admin_init'            => 'check_bulk_convert'
		);

		foreach ( $hooks as $hook => $callback ) {

			$priority = has_action( $hook, array( 'Post_Type_Converter', $callback ) );

			$this->assertGreaterThan( 0, $priority, "Post_Type_Converter::{$callback} not attached to {$hook}." );

		}

	}

	/**
	 * Ensure get_post_types() only returns public post types
	 */
	function test_get_post_types_are_public() {

		$public_types = get_post_types( array( 'public' => true ) );
		$post_types   = Post_Type_Converter::get_post_types();

		foreach ( $post_types as $post_type ) {

			$this->assertArrayHasKey( $post_type, $public_types, "Non-public post type {$post_type} found." );

		}

	}

	/**
	 * Ensure get_post_types() does not include "attachment" post type
	 */
	function test_get_post_types_excludes_attachment() {

		$post_types = Post_Type_Converter::get_post_types();

		$this->assertArrayNotHasKey( 'attachment', $post_types, "Post type 'attachment' found in post types." );

	}

	/**
	 * Ensure the bulk edit javascript is NOT enqueue on non post edit screens
	 */
	function test_bulk_edit_js_not_on_non_post_edit_screens() {

		set_current_screen( 'index.php' );

		Post_Type_Converter::add_bulk_edit_js();

		$this->assertFalse( wp_script_is( 'post-type-converter', 'enqueued' ), 'Bulk edit javascript enqueued on non post edit screen.' );

	}

	/**
	 * Ensure the bulk edit javascript is enqueued on a post edit screen
	 */
	function test_bulk_edit_js_on_post_edit_screen() {

		set_current_screen( 'edit-post.php' );

		Post_Type_Converter::add_bulk_edit_js();

		$this->assertTrue( wp_script_is( 'post-type-converter', 'enqueued' ), 'Bulk edit javascript not enqueued on post edit screen.' );

	}

}
