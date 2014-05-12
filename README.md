**Warning, you are looking at a development branch which may not be stable. For the latest Wordpress VIP release please switch to either the Master or Release branch**

# === thePlatform Video Manager ===
Developed By: thePlatform for Media, Inc.  
Tags: embedding, video, embed, portal, theplatform, shortcode  
Requires at least: 3.7  
Tested up to: 3.9  
Stable tag: 1.3.0

Manage your content hosted by thePlatform and embed media in WordPress posts.

# == Description ==
View your content hosted by thePlatform for Media and easily embed videos from your
library in WordPress posts, modify media metadata, and upload new media. 
  
# == Installation ==

Copy the folder "thePlatform-video-manager" with all included files into the "wp-content/plugins" folder of WordPress. Activate the plugin and set your MPX credentials in the plugin settings interface.

# == Screenshots ==

1. thePlatform's Setting screen
2. View your media library, search for videos, and sort the returned media set
3. Modify video metadata
4. Easily embed videos from MPX into your posts
5. Upload media to your MPX account

# == Changelog ==

## = 1.3.0 =
* Plugin settings are now cleaned up when switching accounts or deactivating
* Plugin settings now gracefully fall back when login fails
* Added support for EU accounts
* Updated metadata and upload field settings to allow Read/Write/Hide
* Default values are now provided for player ID and upload server ID when account is selected

## = 1.2.5 = 
* Fixed a bug where publishing profiles didn't work if they existing in more than one authorized account
* Added a new setting section - Embedding options
* Removed Full Video/Embed only setting
* Categories are now sorted by title instead of fullTitle
* Moved embed and edit buttons from the media into the metadata container
* Added a feaure to set the featured image from the video thumbnail

## = 1.2.0 =
* Completely redesigned the Upload, Browse, Edit and Embed pages
* Reworked plugin settings to match the new UI
* Verified up to WordPress 3.9
* Fixed Uploading issues
* Disabled unsupported Metadata fields
* Moved all MPX related functionality to it's own Menu slug
* Finer control over user capabilities:
	* 'tp_viewer_cap', 'edit_posts' - View the MPX Media Browser	
	* 'tp_embedder_cap', 'edit_posts' - Embed MPX media into a post
	* 'tp_editor_cap', 'upload_files' - Edit MPX Media
	* 'tp_uploader_cap' - 'upload_files' - Upload MPX media	
	* 'tp_admin_cap', 'manage_options' - Manage thePlatform's plugin settings
* Moved the embedding button into a TinyMCE plugin	

## = 1.1.0 =
* Added an option to submit the Wordpress User ID into a custom field and filter by it
* Moved uploads to a popup window
* Added Pagination to the media views.
* Support for custom fields in editing and uploading.
* Add multiple categories during upload and editing.
* Added a filter for our embed output, tp_embed_code - The complete embed code
* Added a filter for our base embed URL, tp_base_embed_url - Just the player URL
* Added a filter for our full embed URL, tp_full_embed_url - The player URL with all parameters, applied after tp_base_embed_url
* Added filters for user capabilities:
	* 'tp_publisher_cap' - 'upload_files' - Upload MPX media
	* 'tp_editor_cap', 'upload_files' - Edit MPX Media and display the Media Manager
	* 'tp_admin_cap', 'manage_options' - Manage thePlatform's plugin settings
	* 'tp_embedder_cap', 'edit_posts' - Embed MPX media into a post
* Embed shortcode now supports arbitary parameters
* Removed Query by custom fields
* Removed MPX Namespace option
* Fixed over-zealous cap checks - This should fix the user invite workflow issues
* Fixed settings page being loaded on every adming page request
* Resized the media preview in edit mode
* Cleaned up the options page, hiding PID options
* Cleaned up some API calls
* Layout and UX enhancements
* Upload/custom fields default to Omit instead of Allow

## = 1.0.0 =
* Initial release

# == Configuration ==

This plugin requires an account with thePlatform's MPX. Please contact your Account Manager for additional information.

## = MPX Account Options =
* MPX Username - The MPX username to use for all of the plugin capabilities
* MPX Password - The password for the entered MPX username
* MPX Account - The MPX account to upload and retrieve media from

## = Embedding Preferences =
* Default Player - The default player used for embedding and in the Media Browser
* Embed Tag Type - IFrame or Script embed
* Force Autoplay - Pass the autoplay parameter to embedded players

## = General Preferences =
* Filter Users Own Video - Filter by the User ID custom field, ignored if the User ID is blank
* User ID Custom Field - Name of the Custom Field to store the Wordpress User ID, (None) to disable
* Default Upload Server - Default MPX server to upload new media to
* Default Publish Profile - If set, uploaded media will automatically publish to the selected profile. 

## = Filters =
* tp_base_embed_url - Just the player URL
* tp_full_embed_url - The player URL with all parameters, applied after tp_base_embed_url
* tp_embed_code - The complete embed code, with surrounding HTML, applied after tp_full_embed_url
* tp_viewer_cap, default - 'edit_posts' - View the MPX Media Browser	
* tp_embedder_cap, default - 'edit_posts' - Embed MPX media into a post
* tp_editor_cap, default - 'upload_files' - Edit MPX Media
* tp_uploader_cap - default - 'upload_files' - Upload MPX media	
* tp_admin_cap, default - 'manage_options' - Manage thePlatform's plugin settings