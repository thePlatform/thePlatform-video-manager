<?php
/* thePlatform Video Manager Wordpress Plugin
  Copyright (C) 2013-2015 thePlatform, LLC

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
$tp_viewer_cap = apply_filters( TP_VIEWER_CAP, TP_VIEWER_DEFAULT_CAP );
$tp_editor_cap = apply_filters( TP_EDITOR_CAP, TP_EDITOR_DEFAULT_CAP );
define( 'TP_MEDIA_BROWSER', true );

if ( ! current_user_can( $tp_viewer_cap ) ) {
	wp_die( '<p>You do not have sufficient permissions to browse MPX Media</p>' );
}

$tp_html = new ThePlatform_HTML();
$IS_EMBED = false;
$preferences = get_option( TP_PREFERENCES_OPTIONS_KEY );
$account     = get_option( TP_ACCOUNT_OPTIONS_KEY );

// $page = $_GET['page'];
// $keyword = $_GET['keyword'];
// $category = $_GET['category'];
// $myContent = $_GET['mycontent'];
// $order = $_GET['order'];
// $sort = $_GET['sort'];


?>

<div class="wrap">



	<div class="wp-filter">

<div id="icon-options-general" class="icon32"></div>
	<h2 style="float: left;">thePlatform</h2>
		<form style="margin: 10px 0;" class="tp-search-form" role="search" onsubmit="return false;">
			
			<input id="input-search" type="text" class="" placeholder="Keywords">
			
		
			<label for="selectpick-sort" style="font-weight: normal">Sort By:</label>
			<select id="selectpick-sort" class="form-control">
				<option value="added">Added</option>
				<option value="title">Title</option>
				<option value="updated">Updated</option>
			</select>
	
	
			<label for="selectpick-order" style="font-weight: normal">Order By:</label>
			<select id="selectpick-order" class="form-control">
				<option value="|desc">Descending</option>
				<option value="">Ascending</option>
			</select>

			<label for="selectpick-categories" style="font-weight: normal">Category:</label>
			<select id="selectpick-categories" class="form-control">
				<option value="">All Videos</option>				
			</select>
		
			<?php if ( $preferences['user_id_customfield'] !== '(None)' ) { ?>
				
					<input type="checkbox"
					       id="my-content-cb" <?php checked( $preferences['filter_by_user_id'] === 'true' ); ?> />
					<label for="my-content-cb" style="font-weight: normal">My Content</label>
				
			<?php } ?>
			<button id="btn-search" type="button" class="button-primary" style="margin-left: 10px">Search</button>
		</form>
	</div>
	
	
<!-- 	<div class="wp-filter">
	<ul class="filter-links">
			<li class="plugin-install-search"><a href="http://matan.dev/wp-tp/wp-admin/plugin-install.php?tab=search" class=" current">Search Results</a> </li>
	<li class="plugin-install-featured"><a href="http://matan.dev/wp-tp/wp-admin/plugin-install.php?tab=featured" class="">Featured</a> </li>
	<li class="plugin-install-popular"><a href="http://matan.dev/wp-tp/wp-admin/plugin-install.php?tab=popular" class="">Popular</a> </li>
	<li class="plugin-install-recommended"><a href="http://matan.dev/wp-tp/wp-admin/plugin-install.php?tab=recommended" class="">Recommended</a> </li>
	<li class="plugin-install-favorites"><a href="http://matan.dev/wp-tp/wp-admin/plugin-install.php?tab=favorites" class="">Favorites</a> </li>
	<li class="plugin-install-beta"><a href="http://matan.dev/wp-tp/wp-admin/plugin-install.php?tab=beta" class="">Beta Testing</a></li>
	</ul>

	<form class="search-form search-plugins" method="get">
		<input type="hidden" name="tab" value="search">
				<select name="type" id="typeselector">
			<option value="term" selected="selected">Keyword</option>
			<option value="author">Author</option>
			<option value="tag">Tag</option>
		</select>
				<label><span class="screen-reader-text">Search Plugins</span>
			<input type="search" name="s" value="tablepress">
		</label>
		<input type="submit" name="" id="search-submit" class="button screen-reader-text" value="Search Plugins">	</form></div> -->


	<div id="poststuff">
	
		<div id="post-body" class="metabox-holder columns-2 tp-post-body">
<!-- 		<div class="tablenav">
				<div class="tablenav-pages">
					<span class="displaying-num">Example Markup for n items</span> 
					<a class="first-page disabled" title="Go to the first page" href="#">«</a> 
					<a class="prev-page disabled" title="Go to the previous page" href="#">‹</a> 
					<span class="paging-input"><input class="current-page" title="Current page" type="text" name="paged" value="1" size="1"> of <span class="total-pages">5</span></span> 
					<a class="next-page" title="Go to the next page" href="#pagination">›</a> 
					<a class="last-page" title="Go to the last page" href="#pagination">»</a>
				</div>
			</div> -->



		

			<!-- main content -->			
			<div id="post-body-content">
				
				<div class="meta-box-sortables ui-sortable">
					
					<div class="postbox">
					
						<!-- <h3><span>Main Content Header</span></h3> -->


						<div class="inside">
							
							<div id="media-list">
 
   
</div>





						</div> <!-- .inside -->
						


					</div> <!-- .postbox -->
					
				</div> <!-- .meta-box-sortables .ui-sortable -->
				
			</div> <!-- post-body-content -->
			

			<!-- sidebar -->
			<div id="postbox-container-1" class="postbox-container tp-postbox-container-1">
				
				<div class="meta-box-sortables">
					
					<div class="postbox">
											
						<div class="inside">
							<div id="info-player-container" class="scrollable">
								<?php $tp_html->preview_player() ?>
								<?php $tp_html->content_pane( $IS_EMBED ) ?>
							</div>
						</div> <!-- .inside -->
						
					</div> <!-- .postbox -->
					
				</div> <!-- .meta-box-sortables -->
					<?php $tp_html->pagination('top') ?>
			</div> <!-- #postbox-container-1 .postbox-container -->
			
		</div> <!-- #post-body .metabox-holder .columns-2 -->
		
		<br class="clear">
	</div> <!-- #poststuff -->
	
</div> <!-- .wrap -->

<?php
if ( ! $IS_EMBED && current_user_can( $tp_editor_cap )) {
?>
<div id="tp-edit-dialog" style="display: none; padding-left:10px;">
	<?php require_once( dirname( __FILE__ ) . '/thePlatform-edit-upload.php' ); ?>
	<?php } ?>

<script type="text/javascript">
		tpHelper = {};
		tpHelper.account = "<?php echo esc_js( $account['mpx_account_id'] ); ?>";
		tpHelper.accountPid = "<?php echo esc_js( $account['mpx_account_pid'] ); ?>";
		tpHelper.isEmbed = "<?php echo esc_js( $IS_EMBED ); ?>";
		tpHelper.mediaEmbedType = "<?php echo esc_js( $preferences['media_embed_type'] ); ?>";
		tpHelper.selectedCategory = '';
		tpHelper.feedEndRange = 0;
		tpHelper.queryString = '';
	</script>


	<script id="media-template" type="text/template">
		<div class="media" id="<%= id %>">
			<div class="media-left">
				<img class="media-object thumb-img" data-src="<%= placeHolder %>" alt="128x72"
				     src="<%= defaultThumbnailUrl %>">
			</div>
			<div class="media-body">
				<strong class="media-heading"><%= title %></strong>

				<div id="desc"><%= _ . template . formatDescription( description ) %></div>
			</div>
		</div>
	</script>

	<script id="shortcode-template" type="text/template">
		[theplatform account="<%= account %>" media="<%= release %>" player="<%= player %>"]
	</script>