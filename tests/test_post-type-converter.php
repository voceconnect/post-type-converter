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
	 * Ensure the bulk edit javascript is not enqueued on "add post" screens
	 */
	function test_convert_meta_box_not_on_add_screen() {

		set_current_screen( 'post-new.php' );

		Post_Type_Converter::add_bulk_edit_js();

		$this->assertFalse( wp_script_is( 'post-type-converter', 'enqueued' ), 'Bulk edit javascript not enqueued on post edit screen.' );

	}

	/**
	 * Ensure the bulk edit javascript is enqueued on a post edit screen
	 */
	function test_bulk_edit_js_on_post_edit_screen() {

		set_current_screen( 'edit-post.php' );

		Post_Type_Converter::add_bulk_edit_js();

		$this->assertTrue( wp_script_is( 'post-type-converter', 'enqueued' ), 'Bulk edit javascript not enqueued on post edit screen.' );

	}

	/**
	 * Ensure that the convert type metabox is not registered for the add post screen
	 *
	 * @global array $wp_meta_boxes
	 */
	function test_convert_meta_box_on_add_post_screen() {

		global $wp_meta_boxes;

		set_current_screen( 'post-new.php' );

		Post_Type_Converter::add_convert_meta_box();

		if ( is_array( $wp_meta_boxes ) ) {

			$this->assertArrayNotHasKey( 'convert-post-type', $wp_meta_boxes['post']['side']['high'], 'Convert Post Type metabox registered on Add Post screen.' );

		} else {

			$this->assertNull( $wp_meta_boxes );

		}

	}

	/**
	 * Ensure the convert metabox is registered for the edit post screen
	 *
	 * @global array $wp_meta_boxes
	 */
	function test_convert_meta_box_on_edit_post_screen() {

		global $wp_meta_boxes;

		set_current_screen( 'edit-post.php' );

		Post_Type_Converter::add_convert_meta_box();

		$this->assertArrayHasKey( 'convert-post-type', $wp_meta_boxes['post']['side']['high'], 'Convert Post Type metabox *not* registered on Edit Post screen.' );

	}

	/**
	 * Check post -> post in convert work flow
	 */
	function test_convert_post_type_same_types() {

		$post = $this->factory->post->create_and_get(array(
			'post_type' => 'post'
		));

		Post_Type_Converter::convert_post_type( $post, 'post' );

		$post = get_post( $post->ID );

		$this->assertEquals( 'post', $post->post_type, 'Post did not retain "post" post_type.' );

	}

	/**
	 * Test "post" -> "page" in convert workflow, with categories
	 */
	function test_convert_post_type_post_to_page() {

		$post = $this->factory->post->create_and_get(array(
			'post_type' => 'post'
		));

		$categories = $this->factory->category->create_many( 5 );

		$this->factory->category->add_post_terms( $post->ID, $categories, 'category', false );

		Post_Type_Converter::convert_post_type( $post, 'page' );

		$post = get_post( $post->ID );

		$this->assertEquals( 'page', $post->post_type, 'Post did not become "page" post_type.' );

		$post_cats = wp_get_post_terms( $post->ID, 'category', array( 'fields' => 'ids' ) );

		$this->assertEquals( $categories, $post_cats, 'Categories did not transfer to "page" post_type.' );

	}

	/**
	 * Ensure save_convert() short circuits on autosaves
	 */
	function test_save_convert_autosave() {

		$post     = $this->factory->post->create();

		$autosave = _wp_put_post_revision( $post, true );

		$result   = Post_Type_Converter::save_convert( $autosave );

		$this->assertEquals( $autosave, $result, 'save_convert() should return post_id on autosave.' );

	}

	/**
	 * Ensure save_convert() short circuits on revisions
	 */
	function test_save_convert_revision() {

		$post     = $this->factory->post->create();

		$revision = _wp_put_post_revision( $post );

		$result   = Post_Type_Converter::save_convert( $revision );

		$this->assertEquals( $revision, $result, 'save_convert() should return post_id on revision.' );

	}

	/**
	 * Ensure save_convert() returns null when no nonce is present
	 */
	function test_save_convert_no_nonce() {

		$post_id = $this->factory->post->create();

		$result  = Post_Type_Converter::save_convert( $post_id );

		$this->assertNull( $result, 'save_convert() should return null when no nonce present.' );

	}

	/**
	 * Ensure save_convert() returns null when the nonce is invalid
	 */
	function test_save_convert_bad_nonce() {

		$post_id = $this->factory->post->create();

		$_REQUEST['convert_post_type_nonce'] = 'badbadbadbad';

		$result  = Post_Type_Converter::save_convert( $post_id );

		$this->assertNull( $result, 'save_convert() should return null when the nonce is invalid.' );

	}

	/**
	 * Ensure that bad post types are caught by save_convert()
	 */
	function test_save_convert_good_nonce_bad_post_type() {

		$_REQUEST['convert_post_type_nonce'] = wp_create_nonce( 'update_post_type_conversion' );
		$_REQUEST['convert_post_type']       = 'bad_post_type';

		$post_id = $this->factory->post->create();

		Post_Type_Converter::save_convert( $post_id );

		$post = $this->factory->post->get_object_by_id( $post_id );

		$this->assertEquals( 'post', $post->post_type, 'Post was converted to invalid post type through save_convert().' );

	}

	/**
	 * Ensure that save_convert() actually converts post type will all good inputs
	 */
	function test_save_convert_valid_inputs() {

		$_REQUEST['convert_post_type_nonce'] = wp_create_nonce( 'update_post_type_conversion' );
		$_REQUEST['convert_post_type']       = 'page';

		$post_id = $this->factory->post->create();

		Post_Type_Converter::save_convert( $post_id );

		$post = $this->factory->post->get_object_by_id( $post_id );

		$this->assertEquals( 'page', $post->post_type, 'Post was *not* converted to "page" through save_convert().' );

	}


}
