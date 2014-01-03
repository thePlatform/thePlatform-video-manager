<?php
	$tp_embedder_cap = apply_filters('tp_embedder_cap', 'edit_posts');
	if (!current_user_can($tp_embedder_cap)) {
		wp_die('<p>'.__('You do not have sufficient permissions to embed videos').'</p>');
	}

	if ( ! class_exists( 'ThePlatform_API' ) )
		require_once( dirname(__FILE__) . '/thePlatform-API.php' );
	?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />

<title>thePlatform Video Library</title>
<?php 			
	wp_print_scripts(array('jquery', 'theplatform_js', 'thickbox', 'jquery-ui-dialog'));
	wp_print_styles(array('theplatform_css', 'global', 'media', 'wp-admin', 'colors', 'thickbox', 'jquery-ui-dialog'));
	
	$tp_api = new ThePlatform_API;  

	$players = $tp_api->get_players();
	
	if ( is_wp_error( $players ) )
		echo '<div id="message" class="error below-h2"><p>' . $players->get_error_message() . '</p></div>';
	
	$metadata = $tp_api->get_metadata_fields();
	
	if ( is_wp_error( $metadata ) )
		echo '<div id="message" class="error below-h2"><p>' . $metadata->get_error_message() . '</p></div>';
	
	$preferences = get_option('theplatform_preferences_options');
	
	$page = isset( $_POST['tppage'] ) ? sanitize_text_field( $_POST['tppage'] ) : '1';		

	if (isset($_POST['s'])) {
		check_admin_referer('theplatform-ajax-nonce'); 
		$key_word = isset( $_POST['s'] ) ? sanitize_text_field( $_POST['s'] ) : '';						
		$field = isset( $_POST['theplatformsearchfield'] ) ? sanitize_text_field( $_POST['theplatformsearchfield'] ) : '';			
		$sort = isset( $_POST['theplatformsortfield'] ) ? sanitize_text_field( $_POST['theplatformsortfield'] ) : '';

		$query = array();
		if ($key_word !== '')
			array_push($query, $field. '=' . $key_word);
					
		if (isset($_POST['filter-by-userid']) && $preferences['user_id_customfield'] !== '')
		 	array_push($query, 'byCustomValue=' . urlencode('{' . $preferences['user_id_customfield'] . '}{' . wp_get_current_user()->ID . '}'));
		
		if ($field !== 'q')
			$videos = $tp_api->get_videos(implode('&', $query), $sort, $page);
		else
			$videos = $tp_api->get_videos(implode('&', $query), '', $page);		
	} 
	else {
		// Library View				
		if ($preferences['filter_by_user_id'] === 'TRUE' && $preferences['user_id_customfield'] !== '') 
				$videos = $tp_api->get_videos('byCustomValue=' . urlencode('{' . $preferences['user_id_customfield'] . '}{' . wp_get_current_user()->ID . '}'), '', $page);
		else
			$videos = $tp_api->get_videos('','',$page);	
	}
	$count = $videos['totalResults'];	
	$pages = ceil(intval($count)/intval($preferences['videos_per_page']));
	$videos = $videos['entries'];	
?>

<script type='text/javascript'>

jQuery(document).ready(function() {

	jQuery('.embed-photo, .use-shortcode').click(function() {
		var media = this.id;
		
		var player = jQuery('#embed_player').val();
	
		if (media != '') {
			var shortcode = '[theplatform media="' + media + '" player="' + player + '"]';
		
			var win = window.dialogArguments || opener || parent || top;
			var isVisual = (typeof win.tinyMCE != "undefined") && win.tinyMCE.activeEditor && !win.tinyMCE.activeEditor.isHidden();	
			if (isVisual) {
				win.tinyMCE.activeEditor.execCommand('mceInsertContent', false, shortcode);
			} else {
				var currentContent = jQuery('#content', window.parent.document).val();
				if ( typeof currentContent == 'undefined' )
					currentContent = '';		
				jQuery( '#content', window.parent.document ).val( currentContent + shortcode );
			}
			self.parent.tb_remove();
		}
		return false;
	});

	jQuery('.mpx-media-page').click(function(e) {
		e.preventDefault();
		jQuery('#form-page-field').val(jQuery(this).data('page'));
		jQuery('#theplatform-search').submit();
	});

	jQuery('#search-by-content').text(jQuery('.search-select').find(":selected").text());
	jQuery('#sort-by-content').text(jQuery('.sort-select').find(":selected").text());

});

</script>

</head>

<body>
	<div>
		<div>
			<div id="search-bar-outer-embed">
				<div id="search-bar-inner-embed" class="nav-sprite">
					<div>
						<label id="search-label-embed"> Search </label>
						<form class="search-form-embed" id="theplatform-search" name="library-search" method="POST" action="#">							
							<?php wp_nonce_field('theplatform-ajax-nonce'); ?>
          					<input type="hidden" name="page" value="theplatform-media" />
          					<input type="hidden" id="form-page-field" name="tppage" value="1" />
							<span class="nav-sprite" id="search-by" style="width: auto;">
							  <span id="search-by-content" style="width: auto; overflow: visible;">
								Title Prefix
							  </span>
							  <span class="search-down-arrow nav-sprite"></span>
							  <select title="Search by" class="search-select" id="search-dropdown" name="theplatformsearchfield" data-nav-selected="0" style="top: 0px;">
							  	<option value="byTitlePrefix" <?php echo $_POST['theplatformsearchfield'] == 'byTitlePrefix' ? 'selected="selected"' : '' ?>>Title Prefix</option>
								<option value="byTitle" <?php echo $_POST['theplatformsearchfield'] == 'byTitle' ? 'selected="selected"' : '' ?>>Full Title</option>								
								<option value="byCategories" <?php echo $_POST['theplatformsearchfield'] == 'byCategories' ? 'selected="selected"' : '' ?>>Categories</option>
								<option value="q" <?php echo $_POST['theplatformsearchfield'] == 'q' ? 'selected="selected"' : '' ?>>q</option>
							  </select>
							</span>
							
							<span class="nav-sprite" id="sort-by" style="width: auto;">
							  <span id="sort-by-content" style="width: auto; overflow: visible;">
								Sort by..
							  </span>
							  <span class="sort-down-arrow nav-sprite"></span>							  
							  <select title="Sort by" class="sort-select" id="sort-dropdown" name="theplatformsortfield" data-nav-selected="0" style="top: 0px;">
							  	<option value="title" <?php echo $_POST['theplatformsortfield'] == 'title' ? 'selected="selected"' : '' ?>>Title: Asc</option>
								<option value="title|desc" <?php echo $_POST['theplatformsortfield'] == 'title|desc' ? 'selected="selected"' : '' ?>>Title: Desc</option>
								<option value="added" <?php echo $_POST['theplatformsortfield'] == 'added' ? 'selected="selected"' : '' ?>>Date Added: Asc</option>
								<option value="added|desc" <?php echo $_POST['theplatformsortfield'] == 'added|desc' ? 'selected="selected"' : '' ?>>Date Added: Desc</option>
								<option value="author" <?php echo $_POST['theplatformsortfield'] == 'author' ? 'selected="selected"' : '' ?>>Author: Asc</option>
								<option value="author|desc" <?php echo $_POST['theplatformsortfield'] == 'author|desc' ? 'selected="selected"' : '' ?>>Author: Desc</option>
							  </select>
							</span>

							<?php 
							// add_query_arg(array('filter_by_user_id' => 'TRUE'))
								if ($preferences['user_id_customfield'] !== '') { ?>
									<span id="filter-by-embed">
										<input name="filter-by-userid" id="filter-cb-embed" type="checkbox" <?php 
										if (!isset($_POST['s']) && $preferences['filter_by_user_id'] === 'TRUE') { 
											echo 'checked="checked"'; 											
										} 
										if (isset($_POST['filter-by-userid'])) {
											echo 'checked="checked"';
										} ?>
										/>
                                		<label id="filter-label-embed" for="filter-cb-embed">My Media</label>
								</span>
							<?php } ?>	


							<div class="searchfield-outer nav-sprite">
							  <div class="searchfield-inner nav-sprite">
								<div class="searchfield-width" style="padding-left: 44px;">
								  <div id="search-input-container">
									<input type="text" autocomplete="off" name="s" value="<?php echo $_POST['s'] ?>" title="Search For" id="search-input" style="padding-right: 1px;">
								  </div>
								</div>
							  </div>
							</div>


							<div class="search-submit-button nav-sprite">
							  <input type="submit" title="Go" class="search-submit-input" value="Go">
							</div>
				   		</form>

				  		<div id="embed-player-select">
							<span> Player </span>
							<?php
								$html = '<select id="embed_player" name="embed_player_select">';  
								
								foreach ($players as $player) {
									if ($player['plplayer$pid'] == $preferences['default_player_pid'])
										$html .= '<option value="' . esc_attr($player['plplayer$pid']) . '" selected="selected">' . esc_html($player['title']) . '</option>';  
									else
										$html .= '<option value="' . esc_attr($player['plplayer$pid']) . '">' . esc_html($player['title']) . '</option>';  
								}
								 $html .= '</select>';  
								echo $html;
							?>
						</div>
					</div>		
				</div>	
			</div>

			<div id="response-div">
					
				<?php			  

					if ( !is_wp_error( $response ) ) {

						if (empty( $videos) )
							echo 'No media present.';

						$output = '<div style="clear:both;"></div><div style="align: center;"><div class="wrap" >';
						foreach ( $videos as $video ) {	

							$thumbnail_url = $video['plmedia$defaultThumbnailUrl'];
							if ($thumbnail_url === '')					
								$thumbnail_url = plugins_url('/images/notavailable.gif', __FILE__);
							$embed_id = null;			
							if (!is_array($video['media$content'])) 
								continue;	

							foreach ($video['media$content'] as $content) {			
								if (!is_array($content['plfile$releases'])) 
									continue;
								
								foreach ($content['plfile$releases'] as $release) {
									if ($release['plrelease$delivery'] == "streaming") {
										$embed_id = $release['plrelease$pid'];	
										break;
									}						
								}					
							}

							if (is_null($embed_id)) {
								$count--;
								continue;
							}
							
								
							$output .= '
							<div id="theplatform-media-embed-wrapper" class="theplatform-media">
							<div id="' . esc_attr($embed_id) . '" class="photo embed-photo">
							<img src="' . esc_url($thumbnail_url) . '">
							</div>
							<div class="item-title">' . esc_html( $video['title'] ) .'</div>
							</div>';
						}

						$output.='</div><div style="clear:both;"></div>';

						$output .= '<ul id="pagination">';

						if (!isset($_POST['tppage']) || $_POST['tppage'] === '1')
							$output .= '<li class="previous-off">«Previous</li>';
						else
							$output .= '<li><a class="mpx-media-page" href="#" data-page="' . (intval($_POST['tppage'])-1) . '">«Previous</a></li><li>';

						for ($i=1; $i <= $pages; $i++) { 
							if ($i == $page)
								$output .= '<li class="active">' . $page . '</li>';
							else
								$output .= '<li><a class="mpx-media-page" href="#" data-page="' . $i . '">' . $i . '</a></li>';
						}

						if ($_POST['tppage'] != $pages)
							$output .= '<li><a class="mpx-media-page" href="#" data-page="' . (isset($_POST['tppage']) ? intval($_POST['tppage'])+1 : 2) . '">Next »</a></li>';
						else
							$output .= '<li class="next-off">Next »</li><li>';
							
						$output .= '</ul>';
		
					}
					echo $output;
				?>
	      	</div>
		</div>
	</div>
</body>

</html>