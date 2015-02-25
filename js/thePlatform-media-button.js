jQuery(function($) {
    $(document).ready(function() {
        jQuery('#theplatform-media-button').click(function() {
            wp.media({
                frame: 'post',
                state: 'iframe:theplatform'
            }).open();
        });
    });
});
