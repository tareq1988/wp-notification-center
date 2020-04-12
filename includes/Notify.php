<?php

namespace WeDevs\Notification;

use Exception;

/**
 * Frontend
 */
class Notify {

    const LENGTH = 250;

    protected $body = '';
    protected $link = '';
    protected $read = 0;
    protected $type = 'USER';
    protected $to;
    protected $sent_by;
    protected $icon = '';
    protected $origin = '';

    function __construct( $origin ) {
        $this->origin = $origin;
    }

    public function body( $content ) {
        $content = trim( $content );

        if ( empty( $content ) ) {
            throw new Exception( __( 'Empty notification body given', 'textdomain' ) );
        }

        $length_func = function_exists( 'mb_strlen' ) ? 'mb_strlen' : 'strlen';

        if ( $length_func( $content ) > self::LENGTH ) {
            throw new Exception( __( 'Notification body exceeds maximum allowed length.', 'textdomain' ) );
        }

        $content = wp_kses( $content, [ 'strong' => [] ] );

        $this->body = $content;

        return $this;
    }

    public function from_plugin( $basename ) {
        $this->type = 'PLUGIN';

        if ( ! is_plugin_active( $basename ) ) {
            throw new Exception( __( 'The plugin is not active', 'textdomain' ) );
        }

        $plugins = get_plugins();
        $plugin = $plugins[ $basename ];

        $this->sent_by = $plugin['Name'];
        $this->icon = WD_NOTIF_ASSETS . '/images/plugin.svg';

        return $this;
    }

    public function link( $link ) {
        $this->link = $link;

        return $this;
    }

    public function mark_read() {
        $this->read = 1;
    }

    public function from_user( $user_id ) {
        $this->sent_by = (int) $user_id;
        $this->type = 'USER';

        $avatar = get_avatar_url( $user_id, [ 'size' => 32 ] );

        if ( $avatar ) {
            $this->icon = $avatar;
        }

        return $this;
    }

    public function to( $user_id ) {
        $this->to = (int) $user_id;

        return $this;
    }

    public function send() {
        global $wpdb;

        $data = [
            'body'    => $this->body,
            'link'    => $this->link,
            'read'    => $this->read,
            'type'    => $this->type,
            'to'      => $this->to,
            'sent_by' => $this->sent_by,
            'icon'    => $this->icon,
            'origin'  => $this->origin,
            'sent_at' => current_time( 'mysql', true ),
        ];

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'wd_notifications',
            $data,
            [
                '%s',
                '%s',
                '%d',
                '%s',
                '%d', // to
                '%s',
                '%s',
                '%s',
                '%s'
            ]
        );

        if ( ! $inserted ) {
            throw new Exception( __( 'Unable to insert notification', 'wd-notifications' ) );
        }

        update_user_meta( $this->to, '_wd_notify_alert', true );

        return $wpdb->insert_id;
    }

}
