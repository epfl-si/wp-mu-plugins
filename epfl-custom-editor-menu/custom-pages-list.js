/* Hide some unused and troublesome functionalities for editors */

jQuery(document).ready(function(){
    /*
     * Pages list
     */
    /* Hide the template selection from Quick edit from pages list*/
    jQuery('.inline-edit-row-page select[name="page_template"]').parent().hide();

    /* Hide the password selection from Quick edit from pages list*/
    jQuery('.inline-edit-row-page  input[name="post_password"]').first().parent().parent().hide();
    jQuery('.inline-edit-row-page  input[name="post_password"]').first().parent().parent().next('em').hide();
});
