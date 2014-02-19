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
		$site_url = admin_url("/admin-ajax.php?post_id=$iframe_post_id&action=theplatform_embed"); 

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