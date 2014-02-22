<?php

class Voce_WP_UnitTestCase extends WP_UnitTestCase {

	protected static $redirect_location = '';
	protected static $redirect_status   = 302;
	protected $exit_called              = false;

	function setUp() {

		parent::setUp();

		if ( function_exists( 'set_exit_overload' ) ) {

			set_exit_overload( array( $this, 'exit_overload' ) );

		}

	}

	function tearDown() {

		parent::tearDown();

		self::$redirect_location = '';
		self::$redirect_status   = 302;

		$this->exit_called       = false;

		if ( function_exists( 'unset_exit_overload' ) ) {

			unset_exit_overload();

		}

	}

	static function wp_redirect( $location, $status ) {

		self::$redirect_location = $location;
		self::$redirect_status   = $status;

	}

	function expected_wp_redirect( $location, $status = 302 ) {

		return ( $location === self::$redirect_location ) && ( $status === self::$redirect_status );

	}

	function exit_overload() {

		$this->exit_called = true;

	}

	function exit_called() {

		return $this->exit_called;

	}

}