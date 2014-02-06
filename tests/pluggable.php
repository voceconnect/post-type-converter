<?php
/**
 * Replace some "pluggable" WordPress functions so they are testable
 */

function wp_redirect( $location, $status = 302 ) {

	Voce_WP_UnitTestCase::wp_redirect( $location, $status );

}