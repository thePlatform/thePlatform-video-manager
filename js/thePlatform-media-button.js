jQuery(function($) {
    $(document).ready(function() {
    	if (jQuery().dialog == undefined) {
    		jQuery('#theplatform-media-button').hide();
    	}
        jQuery('#theplatform-media-button').click(open_tp_dialog);
    });

    function open_tp_dialog() {
    	var iframeUrl = tp_media_button_local.ajaxurl + '?action=theplatform_media&embed=true&_wpnonce=' + tp_media_button_local.tp_nonce['theplatform_media'];
            if (jQuery("#tp-embed-dialog").length == 0) {
                jQuery("body").append('<div id="tp-embed-dialog"></div>')
            }
            if (window.innerHeight < 1200) {
                var height = window.innerHeight - 50
            } else {
                var height = 1024
            }
            jQuery("#tp-embed-dialog").html('<iframe src="' + iframeUrl + '" height="100%" width="100%">').dialog({
                dialogClass: "wp-dialog",
                modal: true,
                resizable: true,
                minWidth: 1024,
                width: 1250,
                height: height
            }).css("overflow-y", "hidden")
    }
});