<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />

<title>thePlatform Video Library</title>
<?php 			
	wp_print_scripts(array('jquery', 'theplatform_uploader_js', 'theplatform_js'));
	wp_print_styles(array('theplatform_css', 'global', 'wp-admin', 'colors', 'bootstrap_css'));

?>

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
	message_nag("Preparing for upload..");
	var theplatformUploader = new TheplatformUploader(uploaderData.file, uploaderData.params, uploaderData.custom_params, uploaderData.profile);
</script>
</html>