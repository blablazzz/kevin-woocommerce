/***********
 * getKevin payment gateway
 * Javascript actions
 ***********/
jQuery(document).ready(function($) {
    $('.tabContent').hide('fast');
    $('#tab0').addClass('nav-tab-active');
    $('#content0').show('fast');
    $('.nav-tab').on('click',function(evt) {
        $('.nav-tab').removeClass('nav-tab-active');
        $('.tabContent').hide('fast');
        $('#' + evt.target.attributes.getNamedItem('id').value).addClass('nav-tab-active');
        $('#' + evt.target.attributes.getNamedItem('data-cont').value).show('fast');
    });
});
