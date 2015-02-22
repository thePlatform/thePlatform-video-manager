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

$tp_html     = new ThePlatform_HTML();
$IS_EMBED    = false;
$preferences = get_option( TP_PREFERENCES_OPTIONS_KEY );
$account     = get_option( TP_ACCOUNT_OPTIONS_KEY );

// TODO: 
// Make this template work in the post editor

?>

<div class="wrap">
	<div class="wp-filter">
		<div id="icon-options-general" class="icon32"></div>
		<h2 class="tp-header">thePlatform</h2>

		<form class="tp-search-form" role="search" onsubmit="return false;">
			<input id="input-search" type="text" class="" placeholder="Keywords">

			<label for="selectpick-sort">Sort By:</label>
			<select id="selectpick-sort">
				<option value="added">Added</option>
				<option value="title">Title</option>
				<option value="updated">Updated</option>
			</select>

			<label for="selectpick-order">Order By:</label>
			<select id="selectpick-order">
				<option value="|desc">Descending</option>
				<option value="">Ascending</option>
			</select>

			<label for="selectpick-categories">Category:</label>
			<select id="selectpick-categories">
				<option value="">All Videos</option>
			</select>

			<?php if ( $preferences['user_id_customfield'] !== '(None)' ) { ?>

				<input type="checkbox"
				       id="my-content-cb" <?php checked( $preferences['filter_by_user_id'] === 'true' ); ?> />
				<label for="my-content-cb">My Content</label>

			<?php } ?>
			<button id="btn-search" type="button" class="button-primary">Search</button>
		</form>
	</div>


	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2 tp-post-body">
			<!-- main content -->
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<div class="inside">
							<?php $tp_html->pagination( 'top' ) ?>
							<div id="message-panel" class="error below-h2 hidden">
								<p></p></div>
							<div id="media-list"></div>
							<?php $tp_html->pagination( 'bottom' ) ?>
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
			</div> <!-- #postbox-container-1 .postbox-container -->
		</div> <!-- #post-body .metabox-holder .columns-2 -->
		<br class="clear">
	</div> <!-- #poststuff -->

</div> <!-- .wrap -->

<?php
if ( ! $IS_EMBED && current_user_can( $tp_editor_cap )) {
?>
<div id="tp-edit-dialog">
	<?php require_once( dirname( __FILE__ ) . '/thePlatform-edit-upload.php' ); ?>
	<?php } ?>

	<script type="text/javascript">
		tpHelper = {};
		tpHelper.account = "<?php echo esc_js( $account['mpx_account_id'] ); ?>";
		tpHelper.accountPid = "<?php echo esc_js( $account['mpx_account_pid'] ); ?>";
		tpHelper.isEmbed = "<?php echo esc_js( $IS_EMBED ); ?>";
		tpHelper.mediaEmbedType = "<?php echo esc_js( $preferences['media_embed_type'] ); ?>";
		tpHelper.selectedCategory = '';		
		tpHelper.queryString = '';
		tpHelper.currentPage = 1;
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