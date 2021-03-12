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
            $namespace, '/get_quick_comments/(?P<post_type>\w+)', [
                'methods'  => 'GET',
                'callback' => [ $this, 'get_quick_comments' ],
            ]
        );

        register_rest_route(
            $namespace, '/update_quick_comments/(?P<comment_action>\w+)/(?P<comment_id>\d+)', [
                'methods' => 'GET',
                'callback' => [ $this, 'update_quick_comments' ],
            ]
        );

        register_rest_route(
            $namespace, '/unquicken_quick_comment_by_id/(?P<comment_id>\d+)', [
                'methods' => 'GET',
                'callback' => [ $this, 'unquicken_quick_comment_by_id' ],
            ]
        );
    }

    // Get the quick comments for the dropdown menu
    public function get_quick_comments( WP_REST_Request $request ) {
        $params = $request->get_params();
        $post_type = esc_sql( $request[ 'post_type' ] );
        $current_user_id = 2;// get_current_user_id();

        $dt_quick_comments = get_user_meta( $current_user_id, 'dt_quick_comments', false ); //false returns data in an array
var_export($dt_quick_comments);die();
        // Filter comment ids by post type
        $dt_quick_comments_filtered = [];

        foreach( $dt_quick_comments[0] as $comment_id ) {
            $comment_data = get_comment ( $comment_id, ARRAY_A );
            $comment_post_ID = $comment_data['comment_post_ID'];
            $comment_post_type = get_post_type( $comment_post_ID );
            $comment_content = $comment_data['comment_content'];

            echo "comment_id: $comment_id
            ";
            echo "comment_post_id: $comment_post_id
            ";
            echo "comment_post_type: $comment_post_type
            ";
            echo "comment_content: $comment_content

            ";

            if ( $comment_post_type === $post_type ) {
                $dt_quick_comments_filtered[] = [ $comment_id, $comment_post_type, $comment_content];
            }
        }
        return $dt_quick_comments_filtered;
    }

    public function update_quick_comments( WP_REST_Request $request ) {
        // Get passed parameters
        $params = $request->get_params();
        $comment_action = esc_sql( $params['comment_action'] );
        $comment_id = $params['comment_id'];
        $current_user_id = 2;

        if ( empty( $comment_id ) ) {
            return 'error: comment_id is missing';
        }

        if ( empty( $comment_action ) ) {
            return 'error: comment_action is missing';
        }

        // Get comment content
        // $comment_data = get_comment( $comment_id, ARRAY_A );
        // $comment_post_ID = $comment_data['comment_post_ID'];
        // $comment_content = $comment_data['comment_content'];
        // $post_type = get_post_type( $comment_post_ID );
        
        // Get quick comments
        $dt_quick_comments =  get_user_meta( $current_user_id, 'dt_quick_comments', false); //@todo get_current_user_id
        
        switch( $comment_action ) {
            
            // Quicken the comment
            case 'quicken':
            //update_user_meta( $current_user_id, 'dt_quick_comments', []);die();
            //var_export($dt_quick_comments[0]);die();
                
                // Check if the quick comment isn't already in saved
                if ( in_array( $comment_id, $dt_quick_comments ) ) {
                    return "error: that comment is already a quick comment";
                } else {
                    // Quicken the comment
                    $dt_quick_comments[] = $comment_id;
                    //array_push($dt_quick_comments, $comment_id);
                    update_user_meta( $current_user_id, 'dt_quick_comments', array() );
                    update_user_meta( $current_user_id, 'dt_quick_comments', $dt_quick_comments );
                    return "comment added to quick comments";
                }
                break;
            
            // Un-quicken the comment
            case 'unquicken':
                // Check if the comment is a quick comment so we can un-quicken it
                if ( !in_array( $comment_content, $dt_quick_comments[$post_type] ) ) {
                    return "error: that isn't a quick comment for '$post_type' so we can't un-quicken it";
                } else {

                    // Un-quicken the comment
                    unset( $dt_quick_comments[ array_search( $comment_id, $dt_quick_comments) ] );
                    update_user_meta( $current_user_id, 'dt_quick_comments', $dt_quick_comments );
                    return "comment removed from quick comments for '$post_type'";
                }
                break;

            default:
                return 'error: we can\'t understand what you\'re trying to do... ¯\_(ツ)_/¯';
        }
    }

    public function unquicken_quick_comment_by_id( WP_REST_Request $request ) {
        $current_user_id = 2;// get_current_user_id();
        $comment_id = esc_sql( $request->get_params()['comment_id'] );

        $dt_quick_comments = get_user_meta( $current_user_id, 'dt_quick_comments', false ); //false returns data in an array
        var_export($dt_quick_comments);die();
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
