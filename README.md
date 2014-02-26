**Warning, you are looking at a development branch which may not be stable. For the latest Wordpress VIP release please switch to either the Master or Release branch**

# === thePlatform Video Manager ===
Developed By: thePlatform for Media, Inc.  
Tags: embedding, video, embed, portal, theplatform, shortcode  
Requires at least: 3.7  
Tested up to: 3.8  
Stable tag: 1.2.0  

Manage your content hosted by thePlatform and embed media in WordPress posts.

# == Description ==
View your content hosted by thePlatform for Media and easily embed videos from your
library in WordPress posts, modify media metadata, and upload new media. 
  
# == Installation ==

Copy the folder "thePlatform-video-manager" with all included files into the "wp-content/plugins" folder of WordPress. Activate the plugin and set your MPX credentials in the plugin settings interface.

# == Screenshots ==

# == Changelog ==

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
* MPX Username (Required) - The MPX username to use for all of the plugin capabilities
* MPX Password (Required) - The password for the entered MPX username
* MPX Account (Required) - The MPX account to upload and retrieve media from
* MPX Namespace (Optional) - You can choose a specific namespace to use when grabbing Metadata fields, otherwise all metadata fields will be used.

## = General Preferences =
* Default Player (Optional) - The default player used for embedding and previews
* Number of Videos (Required) - Number of videos to show in the media browser
* Default Sort Order (Required) - Default sort order for media queries
* Default Video Type (Required) - Default player embed style (i.e. player.theplatform.com/{accountPID}/embed/{playerPID} or player.theplatform.com/{accountPID}/{playerPID})
* Filter Users Own Video (Required) - Filter by the User ID custom field, ignored if the User ID is blank
* User ID Custom Field (Optional) - Name of the Custom Field to store the Wordpress User ID (No namespace prefix required)
* Default Upload Server (Required) - Default MPX server to upload new media to
* Default Publish Profile (Required) - If set, uploaded media will automatically publish to the selected profile. 

## = Filters =
* tp_base_embed_url - Just the player URL
* tp_full_embed_url - The player URL with all parameters, applied after tp_base_embed_url
* tp_embed_code - The complete embed code, applied after tp_full_embed_url
* 'tp_publisher_cap' - 'upload_files' - Applied and checked before initializing the upload of MPX Media
* 'tp_editor_cap', 'upload_files' - Applied and checked before edit MPX Media and adding the Media Manager link to the sidebar
* 'tp_admin_cap', 'manage_options' - Applied and checked before managing thePlatform's plugin settings and adding it to the sidebar
* 'tp_embedder_cap', 'edit_posts' - Applied and checked before embed MPX media into the post editor