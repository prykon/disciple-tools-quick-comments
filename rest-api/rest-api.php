<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Disciple_Tools_Quick_Comments_Endpoints
{
    /**
     * @todo Set the permissions your endpoint needs
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/capabilities.md
     * @var string[]
     */
    public $permissions = [ 'access_contacts', 'dt_all_access_contacts', 'view_project_metrics' ];

    public function add_api_routes() {
        $namespace = 'disciple_tools_quick_comments/v1';

        register_rest_route(
            $namespace, '/get_quick_comments/(?P<post_type>\w+)/(?P<user_id>\d+)', [
                'methods'  => 'GET',
                'callback' => [ $this, 'get_quick_comments' ],
            ]
        );

        register_rest_route(
            $namespace, 'get_all_quick_comments/(?P<user_id>\d+)', [
                'methods' => 'GET',
                'callback' => [ $this, 'get_all_quick_comments' ],
            ]
        );

        register_rest_route(
            $namespace, '/update_quick_comments/(?P<comment_action>\w+)/(?P<comment_id>\d+)/(?P<user_id>\d+)', [
                'methods' => 'GET',
                'callback' => [ $this, 'update_quick_comments' ],
            ]
        );

        register_rest_route(
            $namespace, '/unquicken_quick_comment_by_id/(?P<comment_id>\d+)/(?P<user_id>\d+)', [
                'methods' => 'GET',
                'callback' => [ $this, 'unquicken_quick_comment_by_id' ],
            ]
        );
    }

    // Get the quick comments for the dropdown menu
    public function get_quick_comments( WP_REST_Request $request ) {
        $params = $request->get_params();
        $current_user_id = esc_sql( $params['user_id'] );
        $post_type = esc_sql( $request['post_type'] );
        // $current_user_id = esc_sql( $request['user_id'] );
        // $qc_nonce = esc_sql( $request['qc_nonce'] );
        // $nonce_state = wp_verify_nonce( '1c8f068122', 'qc_wp_rest' );

        // //Verify the nonce
        // if ( ! isset( $qc_nonce ) ) {
        //     return new WP_Error( 'missing_nonce');
        // }

        // elseif ( 1 !== $nonce_state ) {
        //     return new WP_Error( 'nonce_failure' );
        // }

        $dt_quick_comments = get_user_meta( $current_user_id, 'dt_quick_comments', false );
        // Filter comment ids by post type
        $dt_quick_comments_filtered = [];

        foreach ( $dt_quick_comments[0] as $comment_id ) {
            //var_export($comment_data);
            $comment_data = get_comment( $comment_id, ARRAY_A );
            $comment_post_id = $comment_data['comment_post_ID'];
            $comment_post_type = get_post_type( $comment_post_id );
            $comment_content = $comment_data['comment_content'];

            if ( $comment_post_type === $post_type ) {
                array_push( $dt_quick_comments_filtered, [ $comment_id, $comment_post_type, $comment_content ] );
            }
        }
        return $dt_quick_comments_filtered;
    }

    public function get_all_quick_comments() {
        $params = $request->get_params();
        $current_user_id = esc_sql( $params['user_id'] );
        $dt_quick_comments = get_user_meta( $current_user_id, 'dt_quick_comments', false );
        $dt_quick_comments_complete = [];

        foreach ( $dt_quick_comments[0] as $comment_id ) {
            $comment_data = get_comment( $comment_id, ARRAY_A );
            $comment_post_id = $comment_data['comment_post_ID'];
            $comment_post_type = get_post_type( $comment_post_id );
            $comment_content = $comment_data['comment_content'];
            array_push( $dt_quick_comments_complete, [ $comment_id, $comment_post_type, $comment_content ] );
        }

        return $dt_quick_comments_complete;
    }

    public function update_quick_comments( WP_REST_Request $request ) {
        // Get passed parameters
        $params = $request->get_params();
        $comment_action = esc_sql( $params['comment_action'] );
        $comment_id = esc_sql( $params['comment_id'] );
        $current_user_id = esc_sql( $params['user_id'] );

        if ( empty( $comment_id ) ) {
            return 'error: comment_id is missing';
        }

        if ( empty( $comment_action ) ) {
            return 'error: comment_action is missing';
        }

        // Get comment content
        // $comment_data = get_comment( $comment_id, ARRAY_A );
        // $comment_post_id = $comment_data['comment_post_ID'];
        // $comment_content = $comment_data['comment_content'];
        // $post_type = get_post_type( $comment_post_id );

        // Get quick comments
        $dt_quick_comments = get_user_meta( $current_user_id, 'dt_quick_comments' ); //@todo get_current_user_id
        $dt_current_comments = $dt_quick_comments[0];
        $new_quick_comments = array();
        $new_quick_comments[] = $comment_id;

        if ( is_array( $dt_current_comments ) ) {
            foreach ( $dt_current_comments as $key => $val ) {
                $new_quick_comments[] = $val;
            }
        }


        switch ( $comment_action ) {
            // Quicken the comment
            case 'quicken':
            //update_user_meta( $current_user_id, 'dt_quick_comments', []);die();
            //var_export($dt_quick_comments[0]);die();

                // Check if the quick comment isn't already in saved
                if ( is_array( $dt_current_comments ) ) {
                    if ( in_array( $comment_id, $dt_current_comments ) ) {
                        return "error: that comment is already a quick comment";
                    } else {
                        // Quicken the comment
                        delete_user_meta( $current_user_id, 'dt_quick_comments' );
                        update_user_meta( $current_user_id, 'dt_quick_comments', $new_quick_comments );
                        return "comment added to quick comments";
                    }
                }
                break;

            // Un-quicken the comment
            case 'unquicken':
                // Check if the comment is a quick comment so we can un-quicken it
                if ( is_array( $dt_current_comments ) ) {
                    if ( ! in_array( $comment_id, $dt_current_comments ) ) {
                        return "error: that isn't a quick comment so we can't un-quicken it";
                    } else {

                        // Un-quicken the comment
                        unset( $dt_current_comments[ array_search( $comment_id, $dt_current_comments ) ] );
                        $dt_current_comments = array_values( $dt_current_comments ); // Reset array index
                        update_user_meta( $current_user_id, 'dt_quick_comments', $dt_current_comments );
                        return "comment removed from quick comments";
                    }
                } else {
                    return "error: no quick comments to un-quicken";
                }
                break;

            default:
                return 'error: we can\'t understand what you\'re trying to do... ¯\_(ツ)_/¯';
        }
    }

    public function unquicken_quick_comment_by_id( WP_REST_Request $request ) {
        $params = $request->get_params();
        $current_user_id = esc_sql( $params['user_id'] );
        $comment_id = esc_sql( $params['comment_id'] );

        $dt_quick_comments = get_user_meta( $current_user_id, 'dt_quick_comments', false ); //false returns data in an array

        return $dt_quick_comments[0][$post_type];
    }

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    }

    public function has_permission() {
        $pass = false;
        foreach ( $this->permissions as $permission ) {
            if ( current_user_can( $permission ) ) {
                $pass = true;
            }
        }
        return $pass;
    }
}

Disciple_Tools_Quick_Comments_Endpoints::instance();
