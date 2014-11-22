<!-- MediaAMP Video Manager Wordpress Plugin
Copyright (C) 2013-2014 MediaAMP for Media Inc.

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
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA. -->

<?php
	wp_enqueue_script( 'theplatform_uploader_js' );
	wp_enqueue_style( 'bootstrap_tp_css' );
	wp_enqueue_style( 'theplatform_css' );
	wp_enqueue_style( 'wp-admin' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
    <head>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />

		<title>MediaAMP Video Library</title>
		
		<?php wp_head(); ?>		
    </head>

    <body>
		<div class="tp">			
			<div id="message_nag" class="updated"><p id="message_nag_text">Initializing video upload</p></div>
		</div>

		<!-- <div class="progress progress-striped active">
		  <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
			<span class="sr-only"></span>
		  </div>
		</div> -->

    </body>
    <script type="text/javascript">
		message_nag( "Preparing for upload.." );
		var theplatformUploader = new TheplatformUploader( uploaderData.file, uploaderData.params, uploaderData.custom_params, uploaderData.profile, uploaderData.server );
    </script>
</html>