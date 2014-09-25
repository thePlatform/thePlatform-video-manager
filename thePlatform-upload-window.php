<!-- thePlatform Video Manager Wordpress Plugin
Copyright (C) 2013-2014  thePlatform for Media Inc.

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

/*
 * Load scripts and styles 
 */
add_action('wp_enqueue_scripts', 'theplatform_media_clear_styles', 1000);
function theplatform_media_clear_styles() {
    global $wp_styles; 
    foreach( $wp_styles->queue as $handle ) {   
        wp_dequeue_style( $handle );
    }    
    wp_enqueue_script( 'theplatform_uploader_js' );
    wp_enqueue_script( 'tp_nprogress_js' );
    wp_enqueue_style( 'tp_nprogress_css' );
    wp_enqueue_style( 'bootstrap_tp_css' );
    wp_enqueue_style( 'theplatform_css' );
    wp_enqueue_style( 'wp-admin' );
}
	
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
    <head>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />

		<title>thePlatform Video Library</title>
		
		<?php wp_head(); ?>		
    </head>

    <body class="tp">			    
    <?php wp_footer(); ?> 
    </body>
    <script type="text/javascript">		
		var theplatformUploader = new TheplatformUploader( uploaderData.file, uploaderData.params, uploaderData.custom_params, uploaderData.profile, uploaderData.server );
    </script>
</html>