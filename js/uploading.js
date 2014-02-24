

//  Files chosen in the ui. Cleared when dialog closes.
var filesReadyForUpload = [];

// Keeps a map of all pending Uploads so that a throttled upload can execute.
var uploadMap = {};

// track the start of a series of drops
var firstDrop = true;

var activeUploads = {}; // keep track of active uploads (for canceling)
var activeThreads = {}; // keep track of active uploads (for canceling)
var latestOffset = {}; // keep track of offset for upload threads
const ACTIVE_THREAD_LIMIT = 1;
const MAX_NUMBER_OF_ERRORS = 3;

var UPLOAD_DROP_TEXT = "\u2026or drag one or more files here";
var NO_SERVER_ID = "NO_SERVER";

const BYTES_PER_CHUNK = 5000000; // 5 MB

var numberOfFailures; // keep track of the number of network errors on upload fragment Puts


// from http://blog.new-bamboo.co.uk/2010/7/30/html5-powered-ajax-file-uploads
// note: added browser check for windows/safari since we don't support it due to html overlay failure on flash
// note: added older safari check as Safari 4.0.5 fails, so make sure they are 5 or above.
function supportAjaxUpload()
{
    return macSafariVersionOk() && supportHtmlOverFlash() && supportFileAPI() && supportAjaxUploadProgressEvents();

    function macSafariVersionOk() {
        var browserVersion = BrowserDetect.version;

        return !(BrowserDetect.OS == "Mac" && BrowserDetect.browser == "Safari"); // CONS-10534
    }

    function supportHtmlOverFlash() {
        return !(BrowserDetect.OS == "Windows" && BrowserDetect.browser == "Safari");
    }

    function supportFileAPI() {
        var fi = document.createElement('INPUT');
        fi.type = 'file';
        return 'files' in fi;
    };

    function supportAjaxUploadProgressEvents() {
        var xhr = new XMLHttpRequest();
        return !! (xhr && ('upload' in xhr) && ('onprogress' in xhr.upload));
    };
}

function extractFilesToArray(files)
{
    var filesToReturn = [];

    jQuery.each(files, function () {
        filesToReturn.push(this);
    });

    return filesToReturn;
}

function resetFileInputField(tagId) {
    var oldInput = document.getElementById('fileToUpload');
    var newInput = document.createElement('input');
    newInput.type = 'file';
    newInput.id = 'fileToUpload';
    newInput.multiple = 'multiple';
    newInput.onchange = onBrowseSelected;
    oldInput.parentNode.replaceChild(newInput, oldInput);
    showChromeFilesIndicator(false);
}

function launchUploadForm(isCombineMedia)
{
    var me = this;
    me["isCombineMedia"] = isCombineMedia;

    var setUpModalAndDialog = function()
    {
        //Get the screen height and width
        var modalBackgroundHeight = jQuery(document).height();
        var modalBackgroundWidth = jQuery(window).width();

        //Set height and width to modalBackground to fill up the whole screen
        jQuery('#modalBackground').css({'width':modalBackgroundWidth,'height':modalBackgroundHeight,'display':'block'});

        var dialog = jQuery('#UploadDialog');
        dialog.css({"visibility":"hidden", "display":"block"});
        if(!me["isCombineMedia"])
        {
            jQuery("#combineFilesContainer").css("visibility", "hidden");
        }
        centerInWindow(dialog);
        dialog.css("visibility", "visible");

        //if modalBackground is clicked
        jQuery('#modalBackground').click(function (e) {
            e.preventDefault();
            e.stopPropagation();
        });

        jQuery(window).resize(function ()
        {
            //Get the screen height and width
            var modalBackgroundHeight = jQuery(document).height();
            var modalBackgroundWidth = jQuery(window).width();

            //Set height and width to modalBackground to fill up the whole screen
            jQuery('#modalBackground').css({'width':modalBackgroundWidth,'height':modalBackgroundHeight});

            centerInWindow(jQuery('#UploadDialog'))
        });
    }

    var handleUploadHtmlLoaded = function(response, status, xhr)
    {
        setUpModalAndDialog();

        initDragAndDrop();

        // dropdown for picking server
        jQuery("#servers").msDropDown({visibleRows:10, rowHeight:20});

        // publish profiles list picker
        jQuery("#uploadForm").find("#listpicker").listpicker( {
            checkAllText:"Select All",
            uncheckAllText:"Select None",
            title: "Publish using the following profiles",
            hintText: ""
        });

        // set up click handlers for this state
        jQuery('.upload').click(function () {
            me.submitUploadForm();
            closeUploadDialog();
        });
        jQuery('.cancel').click(function() {
            closeUploadDialog();
        });
    };
    jQuery("#UploadDialog").load("html/dialog.html #uploadForm", handleUploadHtmlLoaded);
}

function centerInWindow(component)
{
    //Get the window height and width
    var winH = jQuery(window).height();
    var winW = jQuery(window).width();

    //Set the popup window to center
    component.css('top',  winH/2 - component.height()/2);
    component.css('left', winW/2 - component.width()/2);
}

function closeUploadDialog()
{
    jQuery('#modalBackground, #UploadDialog').hide();
    filesReadyForUpload = [];
}

function handleFilesPicked(files)
{
    filesReadyForUpload = files.concat();

    var uploadMapVals = [];

    for(var i = 0; i < filesReadyForUpload.length; i++)
    {
        var key = "UploadLookup:index:" + i +  ":" + (new Date()).getTime();
        uploadMap[key] = filesReadyForUpload[i];
        uploadMapVals.push(key);
    }

    swfId().trackFilesOnBrowse(filesReadyForUpload, uploadMapVals, getSelectedIndex());
}

//
// functions and callbacks between flash and JS
//

function submitUploadForm()
{
    var publishValuesArray;

    try
    {
        publishValuesArray = jQuery("#uploadForm").find("#listpicker").listpicker('getSelected');
    }
    catch(error)
    {
        // TODO : temp fix for CONS-11056
    }

    if(!publishValuesArray) publishValuesArray = [];

    // publishValuesArray should appear as:
    // [ {id:"mypubprofid1", title:"My publish profile 1"}, {id:"mypubprofid2", title:"My publish profile 2"}]

    // for testing purposes, until pub profs are hooked up with real pub profs, i'll send back whatever is in
    // the text input as an array like the above, if no items are selected.
    // if we make this text input read/write in the future, then we'll
    // go to the work of matching a title or id added by hand to a selected item. not too bad to do.


    var combine = jQuery('#combineFiles:checked').val();

    swfId().submitUpload(getSelectedIndex(), (combine == "on"), publishValuesArray);// putting in bogus data for arg 3 breaks uploads
}

function onBrowseSelected()
{
    resetDragAndDrop();
    handleFilesPicked(extractFilesToArray(document.getElementById('fileToUpload').files));
    showChromeFilesIndicator(true);
}

function showChromeFilesIndicator(visible)
{
    if(BrowserDetect.browser == "Chrome" && BrowserDetect.OS == "Mac")
    {
        if(visible)
        {
            var filesArray = extractFilesToArray(document.getElementById('fileToUpload').files);
            var toolTip = extractFileNamesFromFilesArray(filesArray).join("\n");
            var iconElement = document.getElementById('chrome-browse-file-icon');
            if(!iconElement)
            {
                var iconHtml = "<div id='chrome-browse-file-icon'/>";
                jQuery("#inner").append(iconHtml);
            }

            jQuery("#chrome-browse-file-icon")
                .css("visibility", "visible")
                .attr("title", toolTip);
        }
        else
        {
            jQuery("#chrome-browse-file-icon")
                .css("visibility", "hidden")
                .attr("title", "");
        }
    }
}

function uploadCanceled(uploadRequest)
{

    activeUploads[uploadRequest.uploadGuid] = null;

    var file = uploadMap[uploadRequest.uploadLookupKey];
    uploadMap[uploadRequest.uploadLookupKey] = null; // clear out the reference for GCing later

    var uploadPost = uploadRequest.rmpURL
        + "/web/Upload/cancelUpload?schema=1.1"
        + "&token=" + uploadRequest.token
        + "&_guid=" + uploadRequest.uploadGuid
        + "&account=" + uploadRequest.accountId;


    var rmpPost = new XMLHttpRequest();

    setCredentials(rmpPost, uploadRequest)

    rmpPost.onload = function (evt)
    {
        // great succeeded
    }

    rmpPost.onerror = function (evt)
    {
        swfId().uploadError(uploadRequest.uploadGuid, "Upload canceled failed on : " + uploadPost);
    }

    rmpPost.open("PUT", uploadPost, true);
    rmpPost.send();



}

function startUpload(uploadRequest)
{

    numberOfFailures = 0;
    var file = uploadMap[uploadRequest.uploadLookupKey];


    var uploadPost = uploadRequest.rmpURL
        + "/web/Upload/startUpload?schema=1.1"
        + "&token=" + uploadRequest.token
        + "&_guid=" + uploadRequest.uploadGuid
        + "&_mediaId=" + uploadRequest.mediaId
        + "&_filePath=" + encodeURIComponent(file.name)
        + "&_fileSize=" + file.size
        + "&account=" + uploadRequest.accountId
        + "&_mediaFileInfo.format=" + uploadRequest.format
        + "&_mediaFileInfo.contentType=" + uploadRequest.contentType
        + "&_serverId=" + uploadRequest.serverId;

    var rmpPost = new XMLHttpRequest();

    setCredentials(rmpPost, uploadRequest);

    rmpPost.onload = function(evt)
    {
        swfId().uploadStarted(uploadRequest.uploadGuid, uploadRequest);
    }

    rmpPost.onerror = function(evt)
    {
        swfId().uploadError(uploadRequest.uploadGuid, "Start upload failed on : " + uploadPost);
    }

    rmpPost.open("PUT", uploadPost, true);
    rmpPost.send();
}

function setCredentials(rmpPost, uploadRequest)
{
    if ("withCredentials" in rmpPost)
    {
        // this browser supports cookies - so we'll enable withCredentials to pass them up
        if (uploadRequest.rmpURL.indexOf("theplatform.") >= 0)
        {
            // we only use cookies on RMP Uploads that use our LoadBalancer
            rmpPost.withCredentials = true;
        }
    }

}


/**
 * This method is where the sequence for Uploading chunks begins
 * @param uploadRequest
 */
function performUpload(uploadRequest)
{
    // set all the maps for tracking the upload
    var file = uploadMap[uploadRequest.uploadLookupKey];
    uploadMap[uploadRequest.uploadLookupKey] = null; // clear out the reference for GCing later


    activeUploads[uploadRequest.uploadGuid]  = file.size;
    activeThreads[uploadRequest.uploadGuid] = 1; // set the initial thread count
    latestOffset[uploadRequest.uploadGuid] = 0;


    sendChunks(file, 0, uploadRequest);
}


/**
 * This is the iterative starting point for sending chunks (it gets called back to send each chunk
 * by the completion of each upload fragment
 * @param file
 * @param start
 * @param uploadRequest
 */
function sendChunks(file, start, uploadRequest)
{
    if (activeUploads[uploadRequest.uploadGuid] == null)
    {
        return; // upload has been cleared (reached size or error) - we're done
    }

    var end = start + BYTES_PER_CHUNK;

    upload(sliceBlob(file, start, end), file, uploadRequest, start, end);
}

/**
 * Handle the slicing of the chunk (browser specific)
 * @param blob
 * @param start
 * @param end
 * @return {*}
 */
function sliceBlob(blob, start, end)
{
    if (blob.mozSlice)
    {
        return blob.mozSlice(start, end)
    }
    else if (blob.webkitSlice)
    {
        return blob.webkitSlice(start, end);
    }
    else if (blob.slice)
    {
        return blob.slice(start, end);
    }
    else
    {
        alert("Error : This Browser does not support slice");
    }
}

/**
 * Manage the upload fragment call and the success (loop back) or error (try again)
 * @param blobOrFile
 * @param originalFile
 * @param uploadRequest
 * @param startOffset
 * @param endOffset
 */
function upload(blobOrFile, originalFile, uploadRequest, startOffset, endOffset)
{

    var uploadPost = uploadRequest.rmpURL
        + "/web/Upload/uploadFragment?schema=1.1"
        + "&_offset=" + startOffset
        + "&_size=" + blobOrFile.size
        + "&token=" + uploadRequest.token
        + "&_guid=" + uploadRequest.uploadGuid
        + "&_mediaId=" + uploadRequest.mediaId
        + "&_filePath=" + encodeURIComponent(originalFile.name)
        + "&account=" + uploadRequest.accountId
        + "&_mediaFileInfo.format=" + uploadRequest.format
        + "&_mediaFileInfo.contentType=" + uploadRequest.contentType
        + "&_serverId=" + uploadRequest.serverId;


    var rmpPost = new XMLHttpRequest();

    rmpPost.open("PUT", uploadPost, true);

    setCredentials(rmpPost, uploadRequest);

    rmpPost.onload = function (evt)
    {
        numberOfFailures = 0; // good, we're getting valid responses

        sendThreadedChunks(originalFile, endOffset, uploadRequest);
    }

    rmpPost.onerror = function (evt)
    {
        // this error is a 'network' error on upload fragment Puts (not an error/exception from RMP). we'll give it a couple more shots
        ++numberOfFailures

        if (numberOfFailures > MAX_NUMBER_OF_ERRORS)
        {
            swfId().uploadError(uploadRequest.uploadGuid, "Too many upload network errors on : " + uploadPost);
            return;
        }

        sendChunks(originalFile, startOffset, uploadRequest); // try again and hope for the best
    }

    rmpPost.send(blobOrFile)

}


/**
 * Spawn the next set of upload fragments based on the number of active threads available
 * @param file
 * @param endOffset
 * @param uploadRequest
 */
function sendThreadedChunks(file, endOffset, uploadRequest)
{
    var activeThreadCount = activeThreads[uploadRequest.uploadGuid];
    var latestOffsetVal = latestOffset[uploadRequest.uploadGuid];
    var fileSize = activeUploads[uploadRequest.uploadGuid];

    --activeThreadCount; // reduce by one thread completing

    var offset = endOffset > latestOffsetVal ? endOffset : latestOffsetVal;

    if (fileSize == -1 || offset >= fileSize)
    {
        // just waiting to finish
        activeThreads[uploadRequest.uploadGuid] = activeThreadCount;

        if (activeThreadCount <= 0)
        {
            // all threads are accounted for - we can now officially complete (ie send back to console to call 'finish')
            activeUploads[uploadRequest.uploadGuid] = null;
            swfId().uploadComplete(uploadRequest.uploadGuid, "Complete");
        }

        return; // we're at the end of the file, don't send any more chunks
    }

    for (var idx = activeThreadCount; idx < ACTIVE_THREAD_LIMIT; idx++)
    {
        // go through active threads and send off the next set of chunks
        if (offset >= fileSize)
        {
            activeUploads[uploadRequest.uploadGuid] = -1; //we're at the end
            break;
        }

        activeThreadCount++;

        sendChunks(file, offset, uploadRequest);

        offset += BYTES_PER_CHUNK;
    }

    latestOffset[uploadRequest.uploadGuid] = offset; // new offset
    activeThreads[uploadRequest.uploadGuid] = activeThreadCount; // new count
}


function setPublishProfiles(publishProfiles)
{
    jQuery("#uploadForm").find("#listpicker").listpicker("option", "optionsArray", publishProfiles);
}

function setServers(servers, selectedIndex)
{
    // remove existing options
    var msdata = jQuery('#servers').msDropDown().data("dd");
    var currentNumRows = msdata.get('length');
    for(var i = 0; i < currentNumRows; i++)
    {
        msdata.remove(0);
    }

    // add new options
    jQuery.each(servers, function() {
        var server = this;
        var serverUrl = '';
        if(server.id != NO_SERVER_ID)
        {
            serverUrl = (server.iconUrl && server.iconUrl.length > 4) ? server.iconUrl : 'assets/default_server.png';
        }
        msdata.add({text:server.label, value:server.id, title: serverUrl});
    });

    jQuery("#servers").msDropDown({visibleRows:10, rowHeight:20});
    var updatedDropdown = jQuery('#servers').msDropDown().data("dd");
    updatedDropdown.set("selectedIndex", selectedIndex);
}


function getSelectedIndex()
{
    var msdata = jQuery('#servers').msDropDown().data("dd");
    return msdata.get("selectedIndex");
}





///////////////////// drag and drop /////////////////////

function ignoreDrag(e) {
    jQuery("#filedrag")
        .removeClass()
        .addClass("drop-hover drop-box label ellipsis");

    e.originalEvent.stopPropagation();
    e.originalEvent.preventDefault();
}
function dragLeave(e) {
    jQuery("#filedrag")
        .removeClass()
        .addClass("drop-initial drop-box label ellipsis");
}
function drop(e)
{
    // cancel event and hover styling
    ignoreDrag(e);
    jQuery("#filedrag")
        .removeClass()
        .addClass("drop-drop drop-box label ellipsis");

    if(firstDrop)
    {
        firstDrop = false;
        filesReadyForUpload = [];
        resetFileInputField('fileToUpload');
    }

    // fetch FileList object
    var dataTransfer = e.originalEvent.dataTransfer;
    var files = dataTransfer.files;

    // process all File objects
    if(!files)
    {
        console.log("no files found on event");
        return;
    }

    var filesDragged = extractFilesToArray(files);
    var allDraggedFiles = filesReadyForUpload.concat(filesDragged);
    handleFilesPicked(allDraggedFiles);

    var fileNames = extractFileNamesFromFilesArray(allDraggedFiles);
    var dropBoxText = fileNames.join(", ");
    var toolTip = fileNames.join("\n");
    jQuery("#filedrag")
        .text(dropBoxText)
        .attr("title", toolTip);

    console.log("files to be uploaded: "+msg);
}

function extractFileNamesFromFilesArray(filesArray)
{
    var fileNames = [];
    jQuery.each(filesArray, function () {
        var file = this;
        fileNames.push((file.hasOwnProperty("fileName") ? file.fileName : file.name));
    });
    return fileNames;
}

// bind to something that gets recreated each time
// an upload dialog is opened, otherwise you get listeners*num times opened
function initDragAndDrop()
{
    jQuery(".upload-dialog")
        .bind("dragenter", ignoreDrag)
        .bind("dragover", ignoreDrag)
        .bind("dragleave", dragLeave)
        .bind("drop", drop);

    resetDragAndDrop();
    firstDrop = true;
}
function resetDragAndDrop()
{
    firstDrop = true;
    jQuery("#filedrag")
        .addClass("drop-initial drop-box label ellipsis")
        .text(UPLOAD_DROP_TEXT)
        .attr("title", "");

}


/**
 * Older RMP (2.6.2) upload support
 * @param uploadRequest
 */
function uploadCanceledForOldNonFrag(uploadRequest)
{
    var activeUpload = activeUploads[uploadRequest.uploadGuid]

    if (activeUpload)
    {
        activeUpload.abort();
    }
}


/**
 * Older RMP (2.6.2) upload support
 * @param uploadRequest
 */
function performOldNonFragUploadStream(uploadRequest)
{
    var file = uploadMap[uploadRequest.uploadLookupKey];
    uploadMap[uploadRequest.uploadLookupKey] = null; // clear out the reference for GCing later

    var uploadPost = uploadRequest.rmpURL
        + "/web/Upload/upload?schema=1.0"
        + "&token=" + uploadRequest.token
        + "&_guid=" + uploadRequest.uploadGuid
        + "&_mediaId=" + uploadRequest.mediaId
        + "&_filePath=" + encodeURIComponent(file.name)
        + "&_fileSize=" + file.size
        + "&account=" + uploadRequest.accountId
        + "&_mediaFileInfo.format=" + uploadRequest.format
        + "&_mediaFileInfo.contentType=" + uploadRequest.contentType
        + "&_serverId=" + uploadRequest.serverId;

    var rmpPost = new XMLHttpRequest();


    if("withCredentials" in rmpPost)
    {
        // this browser supports cookies - so we'll enable withCredentials to pass them up
        if (uploadRequest.rmpURL.indexOf("theplatform.") >= 0)
        {
            // we only use cookies on RMP Uploads that use our LoadBalancer
            rmpPost.withCredentials = true;
        }
    }

    activeUploads[uploadRequest.uploadGuid]  = rmpPost;

    rmpPost.onload = function(evt)
    {
        activeUploads[uploadRequest.uploadGuid] = null;
        swfId().uploadComplete(uploadRequest.uploadGuid, rmpPost.responseText);
    }

    rmpPost.onerror = function(evt)
    {
        activeUploads[uploadRequest.uploadGuid] = null;
        swfId().uploadError(uploadRequest.uploadGuid, "Upload failure on : " + uploadPost);
    }

    rmpPost.open("PUT", uploadPost, true);
    rmpPost.send(file)
}
