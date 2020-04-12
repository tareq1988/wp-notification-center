<?php

namespace WeDevs\Notification;

use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;

/**
 * API Class
 */
class API extends WP_REST_Controller {

    protected $schema;

    function __construct() {
        $this->namespace = 'wd-notifications/v1';
        $this->rest_base = 'notifications';

        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_items' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                    'args'                => $this->get_collection_params(),
                ],
                'schema' => [ $this, 'get_item_schema' ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/read',
            [
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'mark_all_read' ],
                    'permission_callback' => function() {
                        return is_user_logged_in();
                    },
                    'args'                => []
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/dismiss',
            [
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'dismiss_bell' ],
                    'permission_callback' => function() {
                        return is_user_logged_in();
                    },
                    'args'                => []
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            [
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'update_item' ],
                    'permission_callback' => [ $this, 'update_item_permissions_check' ],
                    'args'                => [
                        'status' => [
                            'description' => __( 'A named status for the object.' ),
                            'type'        => 'string',
                            'enum'        => [ 'read', 'unread' ],
                            'context'     => [ 'view', 'edit' ],
                            'required'    => true,
                        ],
                    ],
                ],
                'schema' => [ $this, 'get_item_schema' ],
            ]
        );
    }

    /**
     * Check permissions for the posts.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_items_permissions_check( $request ) {
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view the post resource.' ), [ 'status' => rest_authorization_required_code() ] );
        }

        return true;
    }

    /**
     * Checks if a given request has access to update a notification.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function update_item_permissions_check( $request ) {
        $notification = wd_notify_get_notification( $request['id'] );

        if ( ! $notification ) {
            return new WP_Error(
                'rest_not_found',
                __( 'Sorry, this notification can not be found.' ),
                array( 'status' => 404 )
            );
        }

        if ( intval( $notification->to ) !== get_current_user_id() ) {
            return new WP_Error(
                'rest_cannot_edit',
                __( 'Sorry, you are not allowed to edit this notification.' ),
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Grabs the five most recent posts and outputs them as a rest response.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_items( $request ) {
        $args = [];
        $data = [];

        $params = $this->get_collection_params();

        $parameter_mappings = [
            'per_page' => 'limit',
        ];

        foreach ( $params as $key => $value ) {
            $param_key          = isset( $parameter_mappings[ $key ] ) ? $parameter_mappings[ $key ] : $key;
            $args[ $param_key ] = isset( $request[ $key ] ) ? $request[ $key ] : $value['default'];
        }

        $notifications = wd_notify_get_notifications( $args );

        if ( empty( $notifications ) ) {
            return rest_ensure_response( $data );
        }

        foreach ( $notifications as $notification ) {
            $response = $this->prepare_item_for_response( $notification, $request );
            $data[] = $this->prepare_response_for_collection( $response );
        }

        $user_id   = get_current_user_id();
        $notifs    = rest_ensure_response( $data );
        $total     = wd_notify_get_count( $user_id );
        $max_pages = ceil( $total / (int) $args['limit'] );
        $has_bell  = (bool) get_user_meta( $user_id, '_wd_notify_alert', true );

        $notifs->header( 'X-WP-Total', (int) $total );
        $notifs->header( 'X-WP-TotalPages', (int) $max_pages );
        $notifs->header( 'X-WP-Notification', $has_bell ? 'yes' : 'no' );

        return $notifs;
    }

    /**
     * Matches the post data to the schema we want.
     *
     * @param WP_Post $post The comment object whose response is being prepared.
     */
    public function prepare_item_for_response( $notification, $request ) {
        $data = [];
        $fields = $this->get_fields_for_response( $request );

        if ( in_array( 'id', $fields, true ) ) {
            $data['id'] = (int) $notification->id;
        }

        if ( in_array( 'content', $fields, true ) ) {
            $data['content'] = $notification->body;
        }

        if ( in_array( 'link', $fields, true ) ) {
            $data['link'] = $notification->link;
        }

        if ( in_array( 'read', $fields, true ) ) {
            $data['read'] = (bool) $notification->read;
        }

        if ( in_array( 'icon', $fields, true ) ) {
            $data['icon'] = $notification->icon;
        }

        if ( in_array( 'date', $fields, true ) ) {
            $data['date'] = mysql_to_rfc3339( $notification->sent_at );
        }

        $response = rest_ensure_response( $data );

        $links = $this->prepare_links( $notification );
        $response->add_links( $links );

        return $response;
    }

    /**
     * Prepares links for the request.
     *
     * @since 4.7.0
     *
     * @param WP_Post $post Post object.
     * @return array Links for the given post.
     */
    protected function prepare_links( $notification ) {
        $base = sprintf( '%s/%s', $this->namespace, $this->rest_base );

        // Entity meta.
        $links = array(
            'self'       => array(
                'href' => rest_url( trailingslashit( $base ) . $notification->id ),
            ),
            'collection' => array(
                'href' => rest_url( $base ),
            ),
        );

        return $links;
    }

    /**
     * Retrieves the query params for collections.
     *
     * @since 4.7.0
     *
     * @return array Comments collection parameters.
     */
    public function get_collection_params() {
        $query_params = parent::get_collection_params();

        unset( $query_params['search'] );
        unset( $query_params['context'] );

        $query_params['per_page']['default'] = 20;

        $query_params['since'] = [
            'description'       => __( 'Items to be returned after the timestamp.' ),
            'default'           => 0,
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'validate_callback' => 'rest_validate_request_arg',
        ];

        return apply_filters( 'rest_wd_notification_collection_params', $query_params );
    }

    /**
     * Updates a single notification status.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function update_item( $request ) {
        wd_notify_change_notification_status( $request['id'], $request['status'] );

        $notification = wd_notify_get_notification( $request['id'] );
        $response = $this->prepare_item_for_response( $notification, $request );

        return rest_ensure_response( $response );
    }

    /**
     * Updates all notifications status to read.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function mark_all_read( $request ) {
        wd_notify_mark_all_read();

        return rest_ensure_response( [
            'success' => true
        ] );
    }

    /**
     * Dismiss the bell notifications.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function dismiss_bell( $request ) {
        update_user_meta( get_current_user_id(), '_wd_notify_alert', false );

        return rest_ensure_response( [
            'success' => true
        ] );
    }

    /**
     * Get our sample schema for a post.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_item_schema() {
        if ( $this->schema ) {
            return $this->add_additional_fields_schema( $this->schema );
        }

        $schema = [
            // This tells the spec of JSON Schema we are using which is draft 4.
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            'title'                => 'notification',
            'type'                 => 'object',
            'properties'           => [
                'id' => [
                    'description'  => esc_html__( 'Unique identifier for the object.', 'my-textdomain' ),
                    'type'         => 'integer',
                    'context'      => [ 'view', 'edit' ],
                    'readonly'     => true,
                ],
                'content' => [
                    'description'  => esc_html__( 'The content for the object.', 'my-textdomain' ),
                    'type'         => 'string',
                    'context'     => [ 'view', 'edit' ],
                ],
                'link' => [
                    'description' => __( 'URL to the object.' ),
                    'type'        => 'string',
                    'format'      => 'uri',
                    'context'     => [ 'view', 'edit' ],
                ],
                'read' => [
                    'description' => __( 'Status of the notification.' ),
                    'type'        => 'boolean',
                    'format'      => 'uri',
                    'context'     => [ 'view', 'edit' ],
                ],
                'icon' => [
                    'description' => __( 'URL to the object icon.' ),
                    'type'        => 'string',
                    'format'      => 'uri',
                    'context'     => [ 'view', 'edit' ],
                ],
                'date' => [
                    'description' => __( "The date the object was published, in the site's timezone." ),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                ],
            ],
        ];

        $this->schema = $schema;

        return $this->add_additional_fields_schema( $this->schema );
    }
}
