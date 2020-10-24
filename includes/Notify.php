<?php

namespace WeDevs\Notification;

use Exception;

/**
 * Notify Class
 */
class Notify {

    /**
     * Max length for notification message
     */
    const LENGTH = 250;

    /**
     * Notification body
     *
     * @var string
     */
    protected $body = '';

    /**
     * Notification link
     *
     * @var string
     */
    protected $link = '';

    /**
     * Notification read status
     *
     * @var integer
     */
    protected $read = 0;

    /**
     * Notification type
     *
     * @var string
     */
    protected $type = 'USER';

    /**
     * Notification to user
     *
     * @var integer
     */
    protected $to;

    /**
     * Notification sent by
     *
     * @var integer|string
     */
    protected $sent_by;

    /**
     * Notification Icon.
     *
     * @var string
     */
    protected $icon = '';

    /**
     * Notification origin plugin
     *
     * @var string
     */
    protected $origin = '';

    /**
     * Constructor
     *
     * @param void $origin
     */
    function __construct( $origin ) {
        $this->origin = $origin;
    }

    /**
     * Set Notification body
     *
     * @param  string $content
     *
     * @return WeDevs\Notification\Notify
     */
    public function body( $content ) {
        $content = trim( $content );

        if ( empty( $content ) ) {
            throw new Exception( __( 'Empty notification body given', 'admin-notification-center' ) );
        }

        $length_func = function_exists( 'mb_strlen' ) ? 'mb_strlen' : 'strlen';

        if ( $length_func( $content ) > self::LENGTH ) {
            throw new Exception( __( 'Notification body exceeds maximum allowed length.', 'admin-notification-center' ) );
        }

        $content = wp_kses( $content, [ 'strong' => [] ] );

        $this->body = $content;

        return $this;
    }

    /**
     * From plugin
     *
     * @param  string $basename
     *
     * @return WeDevs\Notification\Notify
     */
    public function from_plugin( $basename ) {
        $this->type = 'PLUGIN';

        if ( ! is_plugin_active( $basename ) ) {
            throw new Exception( __( 'The plugin is not active', 'admin-notification-center' ) );
        }

        $plugins = get_plugins();
        $plugin = $plugins[ $basename ];

        $this->sent_by = $plugin['Name'];
        $this->icon = WD_NOTIF_ASSETS . '/images/plugin.svg';

        return $this;
    }

    /**
     * Set link
     *
     * @param  string $link
     *
     * @return WeDevs\Notification\Notify
     */
    public function link( $link ) {
        $this->link = $link;

        return $this;
    }

    /**
     * Set from user
     *
     * @param  integer $user_id
     *
     * @return WeDevs\Notification\Notify
     */
    public function from_user( $user_id ) {
        $this->sent_by = (int) $user_id;
        $this->type = 'USER';

        $avatar = get_avatar_url( $user_id, [ 'size' => 64 ] );

        if ( $avatar ) {
            $this->icon = $avatar;
        }

        return $this;
    }

    /**
     * Notification sent to user id
     *
     * @param  integer $user_id
     *
     * @return WeDevs\Notification\Notify
     */
    public function to( $user_id ) {
        $this->to = (int) $user_id;

        return $this;
    }

    /**
     * Send the notification
     *
     * @return integer
     */
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
