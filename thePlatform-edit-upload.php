<?php
/* thePlatform Video Manager Wordpress Plugin
  Copyright (C) 2013-2014 thePlatform, LLC

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License along
  with this program; if not, write to the Free Software Foundation, Inc.,
  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function theplatform_upload_clear_styles_and_scripts() {
	global $wp_styles;
	foreach ( $wp_styles->queue as $handle ) {
		wp_dequeue_style( $handle );
	}

	global $wp_scripts;
	foreach ( $wp_scripts->queue as $handle ) {
		wp_dequeue_script( $handle );
	}

	wp_enqueue_script( 'tp_edit_upload_js' );
	wp_enqueue_style( 'tp_edit_upload_css' );
}

$account = get_option( TP_ACCOUNT_OPTIONS_KEY );
if ( $account == false || empty( $account['mpx_account_id'] ) ) {
	wp_die( 'MPX Account ID is not set, please configure the plugin before attempting to manage media' );
}

if ( ! isset( $tp_html ) ) {
	$tp_html = new ThePlatform_HTML();
}

// Detect IE 9 and below which doesn't support HTML 5 File API
preg_match( '/MSIE (.*?);/', $_SERVER['HTTP_USER_AGENT'], $matches );

if ( count( $matches ) > 1 ) {
	//Then we're using IE
	$version = $matches[1];
	if ( $version <= 9 ) {
		echo "<h2>Internet Explorer " . $version . ' is not supported</h2>';
		exit;
	}
}

if ( ! isset( $tp_api ) ) {
	$tp_api = new ThePlatform_API;
}
$preferences = get_option( TP_PREFERENCES_OPTIONS_KEY );


$tp_uploader_cap = apply_filters( TP_UPLOADER_CAP, TP_UPLOADER_DEFAULT_CAP );
$tp_revoke_cap   = apply_filters( TP_REVOKE_CAP, TP_REVOKE_DEFAULT_CAP );

if ( ! defined( 'TP_MEDIA_BROWSER' ) ) {
	add_action( 'wp_enqueue_scripts', 'theplatform_upload_clear_styles_and_scripts', 100912 );
	theplatform_upload_clear_styles_and_scripts();

	if ( ! current_user_can( $tp_uploader_cap ) ) {
		wp_die( '<p>You do not have sufficient permissions to upload MPX Media</p>' );
	}


	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<title>thePlatform Upload Form</title>
		<?php wp_head(); ?>
	</head>
	<body style="width: 95%; padding-left: 10px">
	<h1>Upload Media to MPX</h1>
<?php
} else {
	// Edit Dialog has tabs, so we do all the necessary prefixing here
	$tp_html->edit_tabs_header();
} ?>

	<form role="form">
		<?php
		wp_nonce_field( 'theplatform_upload_nonce' );

		// Output a hidden WP User ID field if the plugin is configured to store it.
		$tp_html->user_id_field();

		// Output rows of all our writable metadata
		$tp_html->metadata_fields();


		if ( ! defined( 'TP_MEDIA_BROWSER' ) ) {
			$tp_html->profiles_and_servers( "upload" );
		} else {
			?>
			<div class="row" style="margin-top: 10px;">
				<div class="col-xs-3">
					<button id="theplatform_edit_button" class="form-control btn btn-primary" type="button"
					        name="theplatform-edit-button">Submit
					</button>
				</div>
			</div>
		<?php } ?>
	</form>
<?php
if ( defined( 'TP_MEDIA_BROWSER' ) ) {
	// Write all of our edit dialog tabs
	$tp_html->edit_tabs_content();
} else {
	wp_footer(); ?>
	</body>
	</html> <?php
}
?>