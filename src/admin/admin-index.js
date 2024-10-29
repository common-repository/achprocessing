jQuery(function($){
    'use strict';
    var achp_admin={
        isTestMode: function() {
            return $( '#woocommerce_ach_processing_testmode' ).is( ':checked' );
        },
        init: function() {
            $( document.body ).on( 'change', '#woocommerce_ach_processing_testmode', function() {
                var test_api_key = $( '#woocommerce_ach_processing_test_api_key' ).parents( 'tr' ).eq( 0 ),
                    test_api_card_key = $( '#woocommerce_ach_processing_test_api_card_key' ).parents( 'tr' ).eq( 0 ),
                    test_api_endpoint = $( '#woocommerce_ach_processing_test_api_endpoint' ).parents( 'tr' ).eq( 0 ),
                    test_user_name = $( '#woocommerce_ach_processing_test_user_name' ).parents( 'tr' ).eq( 0 ),
                    test_password = $( '#woocommerce_ach_processing_test_password' ).parents( 'tr' ).eq( 0 ),
                    live_api_key = $( '#woocommerce_ach_processing_api_key' ).parents( 'tr' ).eq( 0 ),
                    live_api_card_key = $( '#woocommerce_ach_processing_api_card_key' ).parents( 'tr' ).eq( 0 ),
                    live_user_name = $( '#woocommerce_ach_processing_user_name' ).parents( 'tr' ).eq( 0 ),
                    live_password = $( '#woocommerce_ach_processing_password' ).parents( 'tr' ).eq( 0 ),
                    live_api_endpoint = $( '#woocommerce_ach_processing_api_endpoint' ).parents( 'tr' ).eq( 0 );
                if ( $( this ).is( ':checked' ) ) {
                    test_api_endpoint.show();
                    test_api_key.show();
                    test_api_card_key.show();
                    test_user_name.show();
                    test_password.show();
                    live_api_endpoint.hide();
                    live_api_key.hide();
                    live_api_card_key.hide();
                    live_user_name.hide();
                    live_password.hide();
                }else{
                    test_api_endpoint.hide();
                    test_api_key.hide();
                    test_api_card_key.hide();
                    test_user_name.hide();
                    test_password.hide();
                    live_api_endpoint.show();
                    live_api_key.show();
                    live_api_card_key.show();
                    live_user_name.show();
                    live_password.show();
                }
            });
            $( '#woocommerce_ach_processing_testmode' ).trigger( 'change' );
            $( document.body ).on( 'change', '#woocommerce_ach_processing_creditcard_enabled', function() {
                var test_api_card_key = $( '#woocommerce_ach_processing_test_api_card_key' ).parents( 'tr' ).eq( 0 ),
                    live_api_card_key = $( '#woocommerce_ach_processing_api_card_key' ).parents( 'tr' ).eq( 0 ),
                    testmode = $( '#woocommerce_ach_processing_testmode' ).is( ':checked' );
                if ( $( this ).is( ':checked' ) ) {
                    if ( testmode ) {
                        test_api_card_key.show();
                        live_api_card_key.hide();
                    } else {
                        test_api_card_key.hide();
                        live_api_card_key.show();
                    }
                }else{
                    test_api_card_key.hide();
                    live_api_card_key.hide();
                }
            });
            $( '#woocommerce_ach_processing_creditcard_enabled' ).trigger( 'change' );
            $( '#woocommerce_ach_processing_test_password, #woocommerce_ach_processing_password').after(
                '<button class="wc-achp_toggle-secret" style="height: 30px; margin-left: 2px; cursor: pointer"><span class="dashicons dashicons-visibility"></span></button>'
            );
            $( '.wc-achp_toggle-secret' ).on( 'click', function( event ) {
                event.preventDefault();

                var $dashicon = $( this ).closest( 'button' ).find( '.dashicons' );
                var $input = $( this ).closest( 'tr' ).find( '.input-text' );
                var inputType = $input.attr( 'type' );

                if ( 'text' == inputType ) {
                    $input.attr( 'type', 'password' );
                    $dashicon.removeClass( 'dashicons-hidden' );
                    $dashicon.addClass( 'dashicons-visibility' );
                } else {
                    $input.attr( 'type', 'text' );
                    $dashicon.removeClass( 'dashicons-visibility' );
                    $dashicon.addClass( 'dashicons-hidden' );
                }
            } );
        }
    }
    achp_admin.init();
    $('#woocommerce_ach_processing_check_order_status').click(function(){
        location.href = '../wp-content/plugins/achprocessing/check-status.php';
    })
})
