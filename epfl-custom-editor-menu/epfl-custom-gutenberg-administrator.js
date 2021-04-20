/* Hide some unused and troublesome functionalities for administrators */

jQuery(document).ready(function(){
    /* remove Welcome guide */
    if (wp.data) {
        wp.data.select( "core/edit-post" ).isFeatureActive( "welcomeGuide" ) && wp.data.dispatch( "core/edit-post" ).toggleFeature( "welcomeGuide" );
    }
});
