<?php
/*
Plugin Name: Post Type Converter
Plugin URI: https://github.com/voceconnect/post-type-converter
Description: Gives a qualified user the ability to convert a post from one post_type to another public post_type.
Author: Voce Communications - Kevin Langley
Version: 0.3
Author URI: http://voceplatforms.com
*/

if(!class_exists('Post_Type_Converter')) {

	class Post_Type_Converter {

		public static function initialize() {
			if ( current_user_can('manage_options') ){
				add_action('add_meta_boxes', array(__CLASS__, 'add_convert_meta_box'), 20);;
				add_action('save_post', array(__CLASS__, 'save_convert'));
				add_action('admin_enqueue_scripts', array(__CLASS__, 'add_bulk_edit_js'));
				add_action('admin_init', array(__CLASS__, 'check_bulk_convert'));
			}
		}

		public static function get_post_types(){
			$post_types = get_post_types(array('public' => true));
			unset($post_types['attachment']);
			return $post_types;
		}

		public static function get_script_vars(){
			$post_types = self::get_post_types();
			$script_vars = array();
			foreach($post_types as $post_type){
				$script_vars[$post_type] = get_post_type_object($post_type)->labels->singular_name;
			}

			return $script_vars;
		}

		public static function add_bulk_edit_js(){
			$screen = get_current_screen();
			$post_types = self::get_post_types();
			//screens are id'ed as 'edit-$post_type' so only add the js if on on a edit-$post_type screen.
			if( ( substr( $screen->id, 0, 4 ) == 'edit' ) && ( in_array( substr( $screen->id, 5, strlen( $screen->id ) ), $post_types ) ) ) {

				$script_vars = self::get_script_vars();

				wp_enqueue_script('post-type-converter', trailingslashit(plugins_url()).trailingslashit(basename(dirname(__FILE__))).'js/post-type-converter.js', array('jquery'));
				wp_localize_script('post-type-converter', 'script_vars', $script_vars);
			}
		}

		public static function add_convert_meta_box() {
			$screen = get_current_screen();

			if($screen->action == 'add')
				return;

			$post_types = self::get_post_types();

			foreach($post_types as $post_type) {
				add_meta_box('convert-post-type', 'Convert Post Type', array(__CLASS__, 'convert_meta_box_content'), $post_type, 'side', 'high');
				add_post_type_support($post_type, 'post-type-convert');
			}
		}

		public static function convert_meta_box_content($post) {
			$post_types = self::get_post_types();

			echo '<select id="convert_post_type" name="convert_post_type">';
			foreach($post_types as $single_post_type){
				echo '<option value="'.$single_post_type.'"  '.selected($post->post_type, $single_post_type).'>'.get_post_type_object($single_post_type)->labels->singular_name.'</option>';
			}
			echo '</select>';
			wp_nonce_field( "update_post_type_conversion_{$post->ID}", 'convert_post_type_nonce');
		}

		public static function check_bulk_convert() {
			global $pagenow;

			if($pagenow == 'edit.php' && isset($_POST['post'])){
				if(isset($_POST['change_post_type']) && -1 != $_POST['change_post_type'] ){
					$new_post_type = $_POST['change_post_type'];
				} elseif(isset($_POST['change_post_type2']) && -1 != $_POST['change_post_type2'] ){
					$new_post_type = $_POST['change_post_type2'];
				}
				if(isset($new_post_type)){
					foreach($_POST['post'] as $post_id){
						$post = get_post($post_id);
						self::convert_post_type($post, $new_post_type);
					}

					$new_url = get_admin_url('', $pagenow);

					if($_POST['post_type'] != 'post'){
						$new_url = add_query_arg('post_type', $_POST['post_type'], $new_url);
					}

					wp_redirect($new_url);
					exit();
				}
			}
		}

		public static function save_convert($post_id) {
			if(wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
				return $post_id;
			}
			if(isset($_POST['convert_post_type_nonce']) && wp_verify_nonce($_POST['convert_post_type_nonce'], "update_post_type_conversion_{$post_id}")) {
				$new_post_type = $_POST['convert_post_type'];
				$post_types = self::get_post_types();
				if(in_array($new_post_type, $post_types)){
					self::convert_post_type(get_post($post_id), $new_post_type);
				}
			}
		}

		public static function convert_post_type($post, $new_post_type) {
			if($post->post_type != $new_post_type){
				$original_post = $post;
				$categories = get_the_terms($post->ID, 'category');
				$cat_array = array();
				if($categories) {
					foreach($categories as $category){
						$cat_array[] =  $category->term_id;
					}
				}
				$post->post_type = $new_post_type;
				$post->post_category = $cat_array;
				$post->post_parent = 0;
				wp_insert_post(apply_filters('convert_post_type-insert_post', $post, $new_post_type, $original_post));
				do_action('convert_post_type', $post, $new_post_type);
			}
			return;
		}
	}

	add_action( 'init', array( 'Post_Type_Converter', 'initialize' ) );
}