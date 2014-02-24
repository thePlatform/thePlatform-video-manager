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
    	
    	message_nag("Publishing media...");
    	
    	jQuery.ajax({
			url: theplatform.ajax_url,
			data: params,
       		type: "POST",
			success: function(responseJSON) {
				var response = jQuery.parseJSON(responseJSON);
				
				if (response.success == 'true') {
					message_nag("Media is being published. It may take several minutes until the media is available. This window will now close.", true);
					window.setTimeout('window.close()', 10000);
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
						
						if (params.profile != "tp_wp_none") {
	    					message_nag("Waiting for MPX to publish media.");
	    					me.publishMedia(params);
						}
						else {
							message_nag("Upload completed, you can now safely close this window.");
							window.setTimeout('window.close()', 4000);
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
				if (params.num_fragments == me.frags_uploaded) {
					message_nag("Uploaded last fragment. Please do not close this window.");
					me.finish(params);
				} else {
					message_nag("Finished uploading fragment " + me.frags_uploaded + " of " + params.num_fragments + ". Please do not close this window.");
					index++;
					me.attempts = 0;
					me.uploadFragments(params, fragments, index);
				}
    		},
    		error: function(response, type, msg) {    			
    			me.attempts++;    			
    			if (index==0) {
    				message_nag("Unable to start upload, server is not ready.");
    				me.startUpload(params, me.file);    				
    				return;
    			}
				var actualIndex = parseInt(index)+1;    			
    			error_nag("Unable to upload fragment " + actualIndex + " of " + params.num_fragments + ". Retrying count is " + me.attempts + " of 5. Retrying in 5 seconds..", true);
    			
    			if (me.attempts < 5) {
   					setTimeout(function() {
						me.uploadFragments(params, fragments, index);
					}, 100);
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
						
						message_nag("Beginning upload of " + frags.length + " fragments. Please do not close this window.");
						
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

		var sessionParams = {
			url: url,
			action: 'establishSession',
			_wpnonce: theplatform.tp_nonce
		};

		jQuery.post(theplatform.ajax_url, sessionParams, function(result) {
				// Cross-domain XML parsing will get us here.. Ignore the error (SB)
				message_nag("Session established.");
				me.startUpload(params, file);
			}	
		);
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
			filename: file.name,
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
				// parentLocation.reload();
				me.establishSession(params, file);	
			} else {
				error_nag("Unable to upload media asset at this time. Please try again later.", true);
			}
		});	
    };
    
	return TheplatformUploader;
})();