<?php

/**
 * Create the notify object
 *
 * @return WeDevs\Notification\Notify
 */
function wd_notify() {
    $backtrace = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 3 );

    if ( ! wd_notify_from_plugin( $backtrace ) ) {
        throw new \Exception( __( 'Notifications are only allowed from a plugin', 'wd-notification-center' ) );
    }

    $slug = wd_notify_get_caller_slug( $backtrace );

    return new WeDevs\Notification\Notify( $slug );
}

/**
 * If the notification was invoked from a plugin
 *
 * @param  array $backtrace
 *
 * @return boolean
 */
function wd_notify_from_plugin( $backtrace ) {
    // if caller file is in the plugin dir
    $boolean = substr( $backtrace[0]['file'], 0, strlen( WP_PLUGIN_DIR ) ) === WP_PLUGIN_DIR;

    return $boolean;
}

/**
 * Get the caller plugin directory name
 *
 * @param  array $backtrace
 *
 * @return string | false
 */
function wd_notify_get_caller_slug( $backtrace ) {
    $trail = str_replace( WP_PLUGIN_DIR . '/', '', $backtrace[0]['file'] );
    $parts = explode( '/', $trail );

    return isset( $parts[0] ) ? $parts[0] : false;
}

/**
 * Fetch notifications
 *
 * @param  array  $args
 *
 * @return array
 */
function wd_notify_get_notifications( $args = [] ) {
    global $wpdb;

    $defaults = [
        'user'   => get_current_user_id(),
        'limit'  => 20,
        'offset' => 0,
        'since'  => false,
    ];

    $args  = wp_parse_args( $args, $defaults );
    $where = $args['since'] ? sprintf( 'AND UNIX_TIMESTAMP(sent_at) > %d', $args['since'] ) : '';

    $query = $wpdb->prepare(
        "SELECT *, UNIX_TIMESTAMP(sent_at) as sent_timestamp FROM {$wpdb->prefix}wd_notifications
        WHERE `to` = %d $where
        ORDER BY sent_at DESC
        LIMIT %d, %d",
        $args['user'], $args['offset'], $args['limit']
    );

    $items = $wpdb->get_results( $query );

    return $items;
}

/**
 * Get the count of total notifications of a user
 *
 * @return int
 */
function wd_notify_get_count( $user_id = null ) {
    global $wpdb;

    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }

    return (int) $wpdb->get_var(
        "SELECT count(id) FROM {$wpdb->prefix}wd_notifications WHERE `to` = " . intval( $user_id )
    );
}

/**
 * Get a single notification
 *
 * @param  int $notification_id
 *
 * @return object
 */
function wd_notify_get_notification( $notification_id ) {
    global $wpdb;

    return $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wd_notifications WHERE id = %d", $notification_id )
    );
}

/**
 * Set a single notification read/unread status
 *
 * @param  int $notification_id
 *
 * @return object
 */
function wd_notify_change_notification_status( $notification_id, $status = 'read' ) {
    global $wpdb;

    $read = ( $status == 'read' ) ? 1 : 0;
    $date = $read ? current_time( 'mysql' ) : null;

    $updated = $wpdb->update(
        $wpdb->prefix . 'wd_notifications',
        [
            'read'    => $read,
            'read_at' => $date,
        ],
        [ 'id' => $notification_id ],
        [ '%d', '%s' ],
        [ '%d' ]
    );

    return $updated;
}

/**
 * Mark all notifications as read of a user.
 *
 * @param  int $user_id
 *
 * @return int|false The number of rows updated, or false on error.
 */
function wd_notify_mark_all_read( $user_id = null ) {
    global $wpdb;

    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }

    $updated = $wpdb->update(
        $wpdb->prefix . 'wd_notifications',
        [
            'read'    => 1,
            'read_at' => current_time( 'mysql' ),
        ],
        [ 'to' => $user_id ],
        [ '%d', '%s' ],
        [ '%d' ]
    );

    return $updated;
}
