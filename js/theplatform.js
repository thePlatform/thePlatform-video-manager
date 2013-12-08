(function(jQuery){
	jQuery.extend({
	
		/**
		 @function base64Encode Performs Base 64 Encoding on a string
		 @param {String} data - string to encode
		 @return {Number} encoded string
		*/
		base64Encode: function(data) {
			var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
			var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
			  ac = 0,
			  enc = "",
			  tmp_arr = [];

			if (!data) {
			  return data;
			}

			do { 
			  o1 = data.charCodeAt(i++);
			  o2 = data.charCodeAt(i++);
			  o3 = data.charCodeAt(i++);

			  bits = o1 << 16 | o2 << 8 | o3;

			  h1 = bits >> 18 & 0x3f;
			  h2 = bits >> 12 & 0x3f;
			  h3 = bits >> 6 & 0x3f;
			  h4 = bits & 0x3f;

			  tmp_arr[ac++] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
			} while (i < data.length);

			enc = tmp_arr.join('');

			var r = data.length % 3;

			return (r ? enc.slice(0, r - 3) : enc) + '==='.slice(r || 3);
		}
	});
})(jQuery);

TheplatformUploader = (function() {

	/**
	 @function fragFile Slices a file into fragments
	 @param {File} file - file to slice
	 @return {Array} array of file fragments
	*/
	TheplatformUploader.prototype.fragFile = function(file) {
		var fragSize = 1024 * 1024 * 5;
      	var i, j, k; 
      	var ret = [];
      
		if ( !(this.file.slice || this.file.mozSlice) ) {
			return this.file;
		}

		for (i = j = 0, k = Math.ceil(this.file.size / fragSize); 0 <= k ? j < k : j > k; i = 0 <= k ? ++j : --j) {
  			if (this.file.slice) {
  				ret.push(this.file.slice(i * fragSize, (i + 1) * fragSize));
			} else if (file.mozSlice) {
				ret.push(this.file.mozSlice(i * fragSize, (i + 1) * fragSize));
			}
    	}
      
    	return ret;
    };
    
	/**
	 @function waitForPublished Polls the API proxy for media publishing status until status is 'Processed'
	 @param {Object} params - URL parameters passed to the proxy
	*/    
    TheplatformUploader.prototype.waitForPublished = function(params) {
    	var me = this;
    	params.action = 'waitForPublish';
    	params._wpnonce = theplatform.tp_nonce;
    	
    	jQuery.ajax({
			url: theplatform.ajax_url,
			data: params,
       		type: "POST",
			success: function(responseJSON) {
				var response = jQuery.parseJSON(responseJSON);
				if (response.status == 'Processed') {
						
					message_nag("Publishing Complete. Your media is ready to post.", true);	
					window.setTimeout('location.reload()', 4000);					
				} else {
					me.waitForPublished( params );
				}
			},
			error: function(response) {
				error_nag("An error occurred while waiting for upload server COMPLETE status: " + response, true);
			}
		});
    };
    
    /**
	 @function publishMedia Publishes the uploaded media via the API proxy
	 @param {Object} params - URL parameters passed to the proxy
	*/
    TheplatformUploader.prototype.publishMedia = function(params) {
    	var me = this;
    	params.action = 'publishMedia';
    	params._wpnonce = theplatform.tp_nonce;
    	
    	if (this.publishing) {
    		return;
    	}
    	
    	this.publishing = true;
    	
    	message_nag("Publishing media..");
    	
    	jQuery.ajax({
			url: theplatform.ajax_url,
			data: params,
       		type: "POST",
			success: function(responseJSON) {
				var response = jQuery.parseJSON(responseJSON);
				
				if (response.success == 'true') {
					message_nag("Media published. It may take several minutes until the media is available to post.", true);
					window.setTimeout('location.reload()', 4000);
				} else {
					error_nag("Unable to publish media..", true);
				}
			}
		});
    };
    
    /**
	 @function waitForComplete Poll FMS via the API proxy until upload status is 'Complete'
	 @param {Object} params - URL parameters passed to the proxy
	*/
    TheplatformUploader.prototype.waitForComplete = function(params) {
    	var me = this;
    	params.action = 'uploadStatus';
    	params._wpnonce = theplatform.tp_nonce;
    	
    	jQuery.ajax({
			url: theplatform.ajax_url,
			data: params,
       		type: "POST",
			success: function(responseJSON) {
				var response = jQuery.parseJSON(responseJSON);
				var data = response.content;
		
				if (data.entries.length != 0) {
					var state = data.entries[0].state;
	
					if (state == "Complete") { 
						var fileID = data.entries[0].fileId;
						
						params.file_id = fileID;
						
						if (jQuery('#publishing_profile option:selected').val() != "tp_wp_none") {
	    					message_nag("Waiting for MPX to publish media.");
	    					me.publishMedia(params);
						}
						else {
							message_nag("Upload completed, you can now safely navigate to a different page.");
							window.setTimeout('location.reload()', 4000);
						}						
					} else if (state == "Error") {
						error_nag(data.entries[0].exception);
					} else {
						message_nag(state);
						me.waitForComplete( params );
					}
				} else {
					me.waitForComplete( params );
				}
			},
			error: function(response) {
				error_nag("An error occurred while waiting for upload server COMPLETE status: " + response, true);
			}
		});
    };
        
    /**
	 @function finish Notify MPX that the upload has finished
	 @param {Object} params - URL parameters
	*/
    TheplatformUploader.prototype.finish = function(params) {
    	var me = this;
    	
    	if (this.finishedUploadingFragments) {
    		return;
    	}
    	
    	this.finishedUploadingFragments = true;
    	
    	var url = params.upload_base + '/web/Upload/finishUpload?';
    		url += 'schema=1.1';
    		url += '&token=' + params.token;
    		url += '&account=' + encodeURIComponent(params.account_id);
    		url += '&_guid=' + params.guid;
    		
    	var data = "finished";
    	   
    	jQuery.ajax({
    		url: url,
    		data: data,
    		type: "POST",
    		xhrFields: {
			   withCredentials: true
			},
			success: function(response) {
				me.waitForComplete(params);
			},
			error: function(response) {
			
    		}
    	}); 
    };
    
    /**
	 @function cancel Notify the API proxy to cancel the upload process
	 @param {Object} params - URL parameters passed to the proxy
	*/
    TheplatformUploader.prototype.cancel = function(params) {
   		var me = this;
    	params.action = 'cancelUpload';
    	params._wpnonce = theplatform.tp_nonce;
    	
    	this.failed = true;
    	
    	jQuery.ajax({
			url: theplatform.ajax_url,
			data: params,
       		type: "POST"
       	});
    };
    
    /**
	 @function uploadFragments Uploads file fragments to FMS
	 @param {Object} params - URL parameters 
	 @param {Array} fragments - Array of file fragments
	 @param {Integer} index - Index of current fragment to upload
	*/
    TheplatformUploader.prototype.uploadFragments = function(params, fragments, index) {
    	var me = this;
    	var fragSize = 1024 * 1024 * 5;

    	
    	if (this.failed) {
    		return;
    	}    	

    	var url = params.upload_base + '/web/Upload/uploadFragment?';
			url += 'schema=1.1';
			url += '&token=' + params.token;
			url += '&account=' + encodeURIComponent(params.account_id);
			url += '&_guid=' + params.guid;
			url += '&_offset=' + (index * fragSize);
			url += '&_size=' + fragments[index].size;
			url += "&_mediaId=" + params.media_id;
			url += "&_filePath=" + encodeURIComponent(params.file_name);
			url += "&_mediaFileInfo.format=" + params.format;
			url += "&_mediaFileInfo.contentType=" + params.contentType;
			url += "&_serverId=" + params.server_id;
    	
    	jQuery.ajax({
    		url: url,
    		processData: false,
    		data: fragments[index],
    		type: "PUT",
    		xhrFields: {
			   withCredentials: true
			},
    		success: function(response) {    			
    			me.frags_uploaded++;    			
    			if (me.frags_uploaded == 1) {
    				NProgress.configure({ trickle: false });
					NProgress.start();							
    			}
    			else
    				NProgress.set(me.frags_uploaded/params.num_fragments);
				if (params.num_fragments == me.frags_uploaded) {
					message_nag("Uploaded last fragment. Please do not navigate from this page.");
					NProgress.done();
					me.finish(params);
				} else {
					message_nag("Finished uploading fragment " + me.frags_uploaded + " of " + params.num_fragments + ". Please do not navigate from this page.");
					index++;
					me.attempts = 0;
					me.uploadFragments(params, fragments, index);
				}
    		},
    		error: function(response, type, msg) {
    			me.attempts++;
    			NProgress.done();
				var actualIndex = parseInt(index)+1;    			
    			error_nag("Unable to upload fragment " + actualIndex + " of " + params.num_fragments + ". Retrying count is " + me.attempts + " of 5. Retrying in 5 seconds..", true);
    			
    			if (me.attempts < 5) {
   					setTimeout(function() {
						me.uploadFragments(params, fragments, index);
					}, 5000);
    			} else {
    				error_nag("Uploading fragment " + actualIndex + " of " + params.num_fragments + " failed on the client side. Cancelling... Retry upload later.", true);
    				
    				window.setTimeout(function() {
						me.cancel(params);
					}, 6000);
    				
    			}
    		}	
    	});
    };
    
    /**
	 @function waitForReady Wait for FMS to become ready for the upload
	 @param {Object} params - URL parameters
	 @param {File} file - The media file to upload
	*/
    TheplatformUploader.prototype.waitForReady = function(params, file) {
    	var me = this;
    	
    	params.action = 'uploadStatus';
    	params._wpnonce = theplatform.tp_nonce;
    	
    	jQuery.ajax({
			url: theplatform.ajax_url,
			data: params,
       		type: "POST",
			success: function(responseJSON) {
				var response = jQuery.parseJSON(responseJSON);
				var data = response.content;
		
				if (data.entries.length != 0) {
					var state = data.entries[0].state;
	
					if (state == "Ready") { 

						var frags = me.fragFile(file);
						
						me.frags_uploaded = 0;
						params.num_fragments = frags.length;
						
						message_nag("Beginning upload of " + frags.length + " fragments. Please do not navigate from this page.");
						
						me.uploadFragments(params, frags, 0);
						
					} else {
						me.waitForReady( params );
					}
				} else {
					me.waitForReady( params );
				}
			},
			error: function(response) {
				error_nag("An error occurred while waiting for upload server READY status: " + response, true);
			}
		});
    };
    
    /**
	 @function startUpload Inform FMS via the API proxy that we are starting an upload
	 @param {Object} params - URL parameters passed to the proxy
	 @param {File} file - The media file to upload
	*/
    TheplatformUploader.prototype.startUpload = function(params, file) {
    	var me = this;

		params.action = 'startUpload';
		params._wpnonce = theplatform.tp_nonce;
	
		jQuery.ajax({
			url: theplatform.ajax_url,
			data: params,
       		type: "POST",
			xhrFields: {
			   withCredentials: true
			},
			success: function(responseJSON) {
				var response = jQuery.parseJSON(responseJSON);
			
				if (response.success == 'true') {
					message_nag("Waiting for READY status from " + params.upload_base + ".");
					me.waitForReady(params, file);
				} else {
					error_nag("Startup Upload failed with code: " + response.code, true);			
				}
			},
			error: function(result) {
				error_nag("Call to startUpload failed. Please try again later.", true);
			}
		});
    };
    
    /**
	 @function establishSession Establish a cross-domain upload session
	 @param {Object} params - URL parameters
	 @param {File} file - The media file to upload
	*/
    TheplatformUploader.prototype.establishSession = function(params, file) {
		var me = this;

		var url = params.upload_base + '/crossdomain.xml';

		jQuery.ajax({
			url: url,
       		type: "GET",
       		dataType: "xml text",
			xhrFields: {
			   withCredentials: true
			},
			success: function(result) {
				me.startUpload(params, file);
			},
			error: function(result) {
				// Cross-domain XML parsing will get us here.. Ignore the error (SB)
				message_nag("Session established.");
				me.startUpload(params, file);
			}	
		});
    };
    
    /**
	 @function constructor Inform the API proxy to create placeholder media assets in MPX and begin uploading
	 @param {File} file - The media file to upload
	*/
    function TheplatformUploader(file, fields, custom_fields, profile) {
    	var me = this;
    	this.file = file;
    	
    	this.failed = false;
    	this.finishedUploadingFragments = false;
    	this.publishing = false;
    	this.attempts = 0;
    	
		var data = {
			_wpnonce: theplatform.tp_nonce,
			action: 'initialize_media_upload',
			filesize: file.size,
			filetype: file.type,
			filename: jQuery("#theplatform_upload_file").val().replace("C:\\fakepath\\", ""),
			fields: fields,
			custom_fields: custom_fields,
			profile: profile
		};
	
		jQuery.post(theplatform.ajax_url, data, function(responseJSON) {
			var response = jQuery.parseJSON(responseJSON);
		
			if (response.success == "true") {
				var params = {
					file_name: file.name,
					file_size: file.size,
					token: response.token,
					guid: response.guid,
					media_id: response.media_id,
					account_id: response.account_id,
				    server_id: response.server_id,
				    upload_base: response.upload_base,
				    format: response.format,
				    contentType: response.contentType,
				    profile: profile
				};		
			
				message_nag("Server " + params.upload_base + " ready for upload of " + file.name + " [" + params.format + "].");
			
				me.establishSession(params, file);	
			} else {
				error_nag("Unable to upload media asset at this time. Please try again later.", true);
			}
		});	
    };
    
	return TheplatformUploader;
})();

/**
 @function message_nag Display an informative message to the user
 @param {String} msg - The message to display
 @param {Boolean} fade - Whether or not to fade the message div after some delay
*/
var message_nag = function(msg, fade) {
	fade = typeof fade !== 'undefined' ? fade : false;

	if (jQuery('#message_nag').length == 0) {
		jQuery('.wrap > h2').parent().prev().after('<div id="message_nag" class="updated"><p id="message_nag_text">' +  msg + '</p></div>').fadeIn(2000);
	} else {
		jQuery('#message_nag').fadeIn(1000);
		jQuery('#message_nag_text').animate({'opacity': 0}, 1000, function () {
			jQuery(this).text(msg);
		}).animate({'opacity': 1}, 1000);
	}
	
	if (fade == true) {
		jQuery('#message_nag').delay(4000).fadeOut(6000);
	}
}

/**
 @function error_nag Display an error message to the user
 @param {String} msg - The message to display
 @param {Boolean} fade - Whether or not to fade the message div after some delay
*/
var error_nag = function(msg, fade) {
	fade = typeof fade !== 'undefined' ? fade : false;

	jQuery('#message_nag').delay(4000).fadeOut(6000);

	if (jQuery('#error_nag').length == 0) {
		jQuery('.wrap > h2').parent().prev().after('<div id="error_nag" class="error"><p id="error_nag_text">' +  msg + '</p></div>').fadeIn(2000);
	} else {
		jQuery('#error_nag').fadeIn(1000);
		jQuery('#error_nag_text').animate({'opacity': 0}, 1000, function () {
			jQuery(this).text(msg);
		}).animate({'opacity': 1}, 1000);
	}
	
	if (fade == true) {
		jQuery('#error_nag').delay(4000).fadeOut(6000);
	}
}

jQuery(document).ready(function() {

	if (document.title.indexOf('thePlatform Plugin Settings') != -1) {
		// Hide PID option fields
		jQuery('#mpx_account_pid').parent().parent().hide();
		jQuery('#default_player_pid').parent().parent().hide();

		if (jQuery('#mpx_account_id option:selected').length != 0) {
			
			jQuery('#mpx_account_pid').val(jQuery('#mpx_account_id option:selected').val().split('|')[1]);
		}
		else 
			jQuery('#mpx_account_id').parent().parent().hide();

		if (jQuery('#default_player_name option:selected').length != 0) {			
			jQuery('#default_player_pid').val(jQuery('#default_player_name option:selected').val().split('|')[1]);	
		}
		else
			jQuery('#default_player_name').parent().parent().hide();

		if (jQuery('#mpx_server_id option:selected').length == 0) {			
			jQuery('#mpx_server_id').parent().parent().hide();
		}


		
	}	
	
	jQuery('#upload_add_category').click(function(e) {
		var categories = jQuery(this).prev().clone()
		var name = categories.attr('name');
		if (name.indexOf('-') != -1) {
			name = name.split('-')[0] + '-' + (parseInt(name.split('-')[1])+1)
		}
			
		jQuery(this).before(categories.attr('name',name));
	});

	// Fade in the upload form and fade out the media library view
	jQuery('#media-mpx-upload-button').click(function($) {
		jQuery('#theplatform-library-view').fadeOut(500, function() {
			jQuery('#media-mpx-upload-form').fadeIn(500);
		});
	});

	//Set up the PID for users	
	jQuery('#mpx_account_id').change(function(e) {
			jQuery('#mpx_account_pid').val(jQuery('#mpx_account_id option:selected').val().split('|')[1]);
	})

	//and players
	jQuery('#default_player_name').change(function(e) {
			jQuery('#default_player_pid').val(jQuery('#default_player_name option:selected').val().split('|')[1]);
	})

	// Validate account information in plugin settings fields
	jQuery("#verify-account-button").click(function($) {
		var usr = jQuery("#mpx_username").val();
		var pwd = jQuery("#mpx_password").val();
		var images = theplatform.plugin_base_url;
	
		var hash = jQuery.base64Encode(usr + ":" + pwd);
	
		var data = {
			action: 'verify_account',
			_wpnonce: theplatform.tp_nonce,
			auth_hash: hash
		};

		jQuery.post(theplatform.ajax_url, data, function(response) {
			if (jQuery("#verification_image").length > 0) {
				jQuery("#verification_image").remove();
			}
			
			if (response.indexOf('success') != -1 ) {
				jQuery('#verify-account').append('<img id="verification_image" src="' + images + 'checkmark.png" />');											
			} else {
				jQuery('#verify-account').append('<img id="verification_image" src="' + images + 'xmark.png" />');				
			}
		});	
	});

	jQuery("#theplatform-edit-media").submit(function(event) {
		var validation_error = false;
	
		jQuery('.edit_field').each(function() {
		   if (jQuery(this).val().match(/<(\w+)((?:\s+\w+(?:\s*=\s*(?:(?:"[^"]*")|(?:'[^']*')|[^>\s]+))?)*)\s*(\/?)>/)) {
			  jQuery(this).css({border: 'solid 1px #FF0000'}); 
			  validation_error = true;
		   }
		});

		jQuery('.edit_custom_field').each(function() {
		   if (jQuery(this).val().match(/<(\w+)((?:\s+\w+(?:\s*=\s*(?:(?:"[^"]*")|(?:'[^']*')|[^>\s]+))?)*)\s*(\/?)>/)) {
			  jQuery(this).css({border: 'solid 1px #FF0000'}); 
			  validation_error = true;
		   }
		});
	
		if (validation_error) {
			event.preventDefault();
			return false;
		} else {
			return true;
		}
		
	});

	// Upload a file to MPX
	jQuery("#theplatform_upload_button").click(function(event) {
		var file = document.getElementById('theplatform_upload_file').files[0];
		
		var validation_error = false;
		var params = {};
		var custom_params = {}
	
		jQuery('.upload_field').each(function() {
		   if (jQuery(this).val().match(/<(\w+)((?:\s+\w+(?:\s*=\s*(?:(?:"[^"]*")|(?:'[^']*')|[^>\s]+))?)*)\s*(\/?)>/)) {
			  jQuery(this).css({border: 'solid 1px #FF0000'}); 
			  validation_error = true;
		   }
		});

		jQuery('.custom_field').each(function() {
		   if (jQuery(this).val().match(/<(\w+)((?:\s+\w+(?:\s*=\s*(?:(?:"[^"]*")|(?:'[^']*')|[^>\s]+))?)*)\s*(\/?)>/)) {
			  jQuery(this).css({border: 'solid 1px #FF0000'}); 
			  validation_error = true;
		   }
		});
	
		if (validation_error) {
			event.preventDefault();
			return false;
		}
	
		jQuery('#media-mpx-upload-form').fadeOut(750, function() {
			jQuery('#theplatform-library-view').fadeIn(750);
			message_nag("Preparing for upload..");
		});
		
		jQuery('.upload_field').each(function(i){
			if (jQuery(this).val().length != 0)
				params[jQuery(this).attr('name')] = jQuery(this).val();
		});

		var categories = []
		jQuery('.category_field').each(function(i){
			if (jQuery(this).val() != '(None)') {
				var cat = {};
				cat['media$name'] = jQuery(this).val();
				categories.push(cat);
			}
						
		});
		
		params['media$categories'] = categories;

		jQuery('.custom_field').each(function(i){
			if (jQuery(this).val().length != 0) 
				custom_params[jQuery(this).attr('name')] = jQuery(this).val();
		});
		
		var profile = jQuery('.upload_profile');
		
		var theplatformUploader = new TheplatformUploader(file, JSON.stringify(params), JSON.stringify(custom_params), profile.val());
	});

	// Reload media viewer with no search queries
	jQuery("#media-mpx-show-all-button, #theplatform_cancel_edit_button").click(function(event) {
		var url = document.location;
		
		document.location = url.origin + url.pathname + "?page=theplatform-media";
	});

	// Cancel upload.. fade out upload form and fade in media library view
	jQuery("#theplatform_cancel_upload_button").click(function(event) {
	
		jQuery('#media-mpx-upload-form').fadeOut(750, function() {
			jQuery('#theplatform-library-view').fadeIn(750);
			message_nag("Cancelling upload..", true);
		});
	});
	
	// Handle search dropdown text
	jQuery('#search-dropdown').change(function() {
		jQuery('#search-by-content').text(jQuery(this).find(":selected").text());
	});
	
	// Handle sort dropdown text
	jQuery('#sort-dropdown').change(function() {
		jQuery('#sort-by-content').text(jQuery(this).find(":selected").text());
	});
});