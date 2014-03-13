<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />

<title>thePlatform Video Library</title>
<?php 			
	wp_print_scripts(array('jquery', 'theplatform_js'));
	wp_print_styles(array('theplatform_css', 'global', 'wp-admin', 'colors'));
		
?>

</head>

<body>
	<div class="wrap">
		<?php screen_icon('theplatform'); ?>	
		<div id="message_nag" class="updated"><p id="message_nag_text">Initializing video upload</p></div>
	</div>
</body>
<script type="text/javascript">
	message_nag("Preparing for upload..");
	var theplatformUploader = new TheplatformUploader(uploaderData.file, uploaderData.params, uploaderData.custom_params, uploaderData.profile);
</script>
</html>