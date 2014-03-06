<?php 
	if ( ! defined( 'ABSPATH' ) ) exit;
	$tp_viewer_cap = apply_filters('tp_viewer_cap', 'edit_posts');
	if (!current_user_can($tp_viewer_cap)) {
		wp_die('<p>'.__('You do not have sufficient permissions to browse MPX Media').'</p>');
	}		
?>

<style type="text/css">
	#tp-iframe {
		height: 100%;
		width: 100%;
	}

	#tp-container {
		height: 100%;
		width: 100%;	
		overflow-y: hidden;	
	}
</style>

<div id="tp-container">		
	<?php 
		$site_url = admin_url("/admin-ajax.php?action=theplatform_media"); 
		echo '<iframe id="tp-iframe" src="' . $site_url . '"></iframe>'
	?>		
</div>

<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('#tp-iframe').css('height', window.innerHeight-101);

		jQuery(window).resize(function() {
			jQuery('#tp-iframe').css('height', window.innerHeight-101);
		});
	})
</script>
