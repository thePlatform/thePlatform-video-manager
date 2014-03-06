tinymce.PluginManager.add('theplatform', function(editor, url) {
    // Add a button that opens a window
    editor.addButton('theplatform', {
        text: 'Embed MPX Media',
        tooltip: 'Embed MPX Media',        
        image: url.substring(0, url.lastIndexOf('/js')) + '/images/embed_button.png',
        onclick: function() {
            // Open window         
            
            tinyMCE.activeEditor = editor;
            if (jQuery(".tp-embed-dialog").length == 0)
            	jQuery('body').append('<div id="tp-embed-dialog"></div>');
            jQuery("#tp-embed-dialog").html('<iframe src="' + ajaxurl + '?action=theplatform_media&embed=true" height="100%" width="100%">').dialog({dialogClass: "wp-dialog", modal: true, resizable: true, minWidth: 1024, width: 1200, height: 1024}).css("overflow-y","hidden");                       
        }
    });

});

tinymce.init({    
    plugins: 'theplatform'   
});