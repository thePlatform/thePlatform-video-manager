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

/*
 * Load scripts and styles 
 */
add_action( 'wp_enqueue_scripts', 'theplatform_media_clear_styles_and_scripts', 100912 );
function theplatform_media_clear_styles_and_scripts() {
	global $wp_styles;
	foreach ( $wp_styles->queue as $handle ) {
		wp_dequeue_style( $handle );
	}

	global $wp_scripts;
	foreach ( $wp_scripts->queue as $handle ) {
		wp_dequeue_script( $handle );
	}

	if ( ! isset( $_GET['embed'] ) ) {
		wp_enqueue_script( 'tp_edit_upload_js' );
	}
	wp_enqueue_script( 'tp_browser_js' );
	wp_enqueue_style( 'tp_browser_css' );
}

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$tp_viewer_cap = apply_filters( TP_VIEWER_CAP, TP_VIEWER_DEFAULT_CAP );
$tp_editor_cap = apply_filters( TP_EDITOR_CAP, TP_EDITOR_DEFAULT_CAP );

if ( ! current_user_can( $tp_viewer_cap ) ) {
	wp_die( '<p>You do not have sufficient permissions to browse MPX Media</p>' );
}

define( 'TP_MEDIA_BROWSER', true );

$preferences = get_option( TP_PREFERENCES_OPTIONS_KEY );
$account     = get_option( TP_ACCOUNT_OPTIONS_KEY );

if ( $account == false || empty( $account['mpx_account_id'] ) ) {
	wp_die( 'MPX Account ID is not set, please configure the plugin before attempting to manage media' );
}

$IS_EMBED = isset( $_GET['embed'] );

$tp_html = new ThePlatform_HTML();

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="tp:PreferredRuntimes" content="Flash, HTML5"/>
	<meta name="tp:initialize" content="false"/>
	<title>thePlatform Media Browser</title>

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

	<script id="category-template" type="text/template">
		<a href="#" class="list-group-item category"><%= entryTitle %></a>
	</script>

	<script id="media-template" type="text/template">
		<div class="media" id="<%= id %>"><img class="media-object pull-left thumb-img" data-src="<%= placeHolder %>"
		                                       alt="128x72" src="<%= defaultThumbnailUrl %>">

			<div class="media-body">
				<div id="head">
					<strong class="media-heading"><%= title %></strong>
				</div>
				<div id="source"></div>
				<div id="desc"><%= _ . template . formatDescription( description ) %></div>
			</div>
		</div>
	</script>

	<script id="shortcode-template" type="text/template">
		[theplatform account="<%= account %>" media="<%= release %>" player="<%= player %>"]
	</script>

	<?php wp_head(); ?>

</head>
<body>

<!-- navbar -->
<div class="navbar navbar-default navbar-fixed-top" role="navigation">
	<div class="container-fluid">
		<!-- Logo -->
		<div class="navbar-header">
			<a class="navbar-brand" href="#">
				<img alt="thePlatform"
				     src="<?php echo esc_url( plugins_url( '/images/embed_button.png', __FILE__ ) ); ?>">
				thePlatform</a>
		</div>

		<!-- Q Search input -->
		<form class="navbar-form navbar-left" role="search" onsubmit="return false;">
			<div class="form-group">
				<input id="input-search" type="text" class="form-control" placeholder="Keywords">
			</div>
			<button id="btn-feed-preview" type="button" class="btn btn-default">Search</button>
		</form>

		<!-- Sort dropdown -->
		<p class="navbar-text sort-bar-text">Sort:</p>

		<form class="navbar-form navbar-left sort-bar-nav" role="sort">
			<select id="selectpick-sort" class="form-control">
				<option>Added</option>
				<option>Title</option>
				<option>Updated</option>
			</select>
		</form>

		<!-- My Content Checkbox -->
		<div id="my-content" class="navbar-left">
			<p class="navbar-text sort-bar-text">
				<?php if ( $preferences['user_id_customfield'] !== '(None)' ) { ?>
					<input type="checkbox"
					       id="my-content-cb" <?php checked( $preferences['filter_by_user_id'] === 'true' ); ?> />
					<label for="my-content-cb" style="font-weight: normal">My Content</label>
				<?php } ?>
			</p>
		</div>

		<!-- Player dropdown -->
		<?php
		if ( $IS_EMBED ) {
			$tp_html->player_dropdown();
		}
		?>

		<!-- Loading Image -->
		<img id="load-overlay" alt="Loading..."
		     src="<?php echo esc_url( plugins_url( '/images/loading.gif', __FILE__ ) ) ?>" class="loadimg navbar-right">
	</div>
	<!-- /.container-fluid -->
</div>
<!-- /.navbar -->


<!-- 3 Column main layout -->
<div class="fs-main">

	<!-- Sidebar -->
	<div id="filter-container">
		<div id="filter-affix" class="scrollable affix-top">
			<div id="list-categories" class="list-group">
				<a class="list-group-item active">
					Categories
				</a>
				<a href="#" class="list-group-item category selected">All Videos</a>
			</div>
		</div>
	</div>
	<!-- END sidebar -->

	<!-- Media List -->
	<div id="content-container">
		<div id="message-panel"></div>
		<div id="media-list"></div>
	</div>
	<!-- END Media List -->

	<div id="info-container">
		<div id="info-affix" class="scrollable affix-top">
			<div id="info-player-container">
				<?php $tp_html->preview_player() ?>
				<?php $tp_html->content_pane( $IS_EMBED ) ?>
			</div>
		</div>
	</div>

</div>

<?php
if ( ! $IS_EMBED && current_user_can( $tp_editor_cap )) {
?>
<div id="tp-edit-dialog" style="display: none; padding-left:10px;">
	<?php require_once( dirname( __FILE__ ) . '/thePlatform-edit-upload.php' ); ?>
	<?php } ?>

	<?php wp_footer(); ?>
</body>
</html>