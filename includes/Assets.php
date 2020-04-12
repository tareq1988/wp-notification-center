<?php

namespace WeDevs\Notification;

/**
 * Assets
 */
class Assets {

    function __construct() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function enqueue_assets() {
        wp_register_style( 'wd-notify-style', WD_NOTIF_ASSETS . '/style.css', false, filemtime( WD_NOTIF_PATH . '/assets/style.css' ) );

        $fetch_duration = apply_filters( 'wd_notification_internal', 10 );
        wp_register_script( 'wd-notify-js', WD_NOTIF_ASSETS . '/script.js', [ 'jquery', 'wp-util' ], filemtime( WD_NOTIF_PATH . '/assets/script.js' ), true );
        wp_localize_script( 'wd-notify-js', 'wdNotify', [
            'root'  => esc_url_raw( rest_url() ),
            'nonce' => wp_create_nonce( 'wp_rest' ),
            'duration' => $fetch_duration * 1000
        ] );

        wp_enqueue_style( 'wd-notify-style' );
        wp_enqueue_script( 'wd-notify-js' );
    }
}
