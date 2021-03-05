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

    /**
     * @todo define the name of the $namespace
     * @todo define the name of the rest rout
     * @todo defne method (CREATABLE, READABLE)
     * @todo apply permission strategy. '__return_true' essentially skips the permission check.
     */
    //See https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication




    public function add_api_routes() {
        $namespace = 'disciple_tools_quick_comments/v1';

        register_rest_route(
            $namespace, '/quick_comments/(?P<comment_type>\w+)', [
                'methods'  => 'GET',
                'callback' => [ $this, 'get_quick_comments' ],
            ]
        );

        register_rest_route(
            $namespace, '/change_comment_type/(?P<action_type>\w+)/(?P<comment_id>\d+)', [
                'methods' => 'GET',
                'callback' => [ $this, 'change_comment_type'],
            ]
        );
    }





    public function get_quick_comments( WP_REST_Request $request) {
        global $wpdb;
        
        $params = $request->get_params();
        $post_type = $request['comment_type'];
        
        $query = $wpdb->prepare( "
            SELECT comment_content, ANY_VALUE( comment_id )
            FROM $wpdb->comments
            WHERE comment_type = %s
            AND user_id = %d
            GROUP BY comment_content
            ORDER BY comment_content ASC;",
            esc_sql( 'qc_' . $post_type ),
            esc_sql( get_current_user_id() )
        );

        $results = $wpdb->get_col( $query );
        return $results;
    }


    public function get_comment_by_id( int $comment_id ) {
        global $wpdb;

        $query = $wpdb->prepare( "
            SELECT
                comment_content,
                comment_type,
                comment_post_ID,
                user_id
            FROM $wpdb->comments
            WHERE comment_ID = %d;
            ",

            esc_sql( $comment_id )
        );

        $result = $wpdb->get_results( $query, ARRAY_A );
        return $result[0];
    }


    public function get_post_type_by_post_id( int $post_id ) {
        global $wpdb;

        $query = $wpdb->prepare( "
            SELECT post_type
            FROM $wpdb->posts
            WHERE ID = %d;
            ",

            esc_sql( $post_id )
        );

        $result = $wpdb->get_var( $query );
        return $result;
    }


    public function change_comment_type( WP_REST_Request $request ) {
        // Quickens or un-quickens a comment
        global $wpdb;
        $params = $request->get_params();
        if ( !$params[ 'comment_id' ] ) {
            return 'error: comment_id parameter missing';
        } 
        
        $comment_id = (int)$params[ 'comment_id' ];


        // Get comment data from its id
        $data_comment = self::get_comment_by_id( $comment_id );  
        

        switch( $request[ 'action_type' ] ) {
            case 'unquicken':
                $new_comment_type = 'comment';
                break;

            case 'quicken':
                // Get post type
                $post_type = self::get_post_type_by_post_id( (int)$data_comment[ 'comment_post_ID' ] );
                $new_comment_type = 'qc_' . $post_type;
                break;

            default:
                return "error: unknown action type; must be 'quicken' or 'unquicken'.";
        }

        $query = $wpdb->prepare( "
            UPDATE $wpdb->comments
            SET comment_type = %s
            WHERE comment_content = %s
            AND comment_type = %s
            AND user_id = %d;", 
            
            esc_sql( $new_comment_type ),
            esc_sql( $data_comment[ 'comment_content' ] ),
            esc_sql( $data_comment[ 'comment_type'] ),
            esc_sql( (int)$data_comment[ 'user_id'] )
        );

        $result = $wpdb->query( $query );
        return $result;
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
    public function has_permission(){
        $pass = false;
        foreach ( $this->permissions as $permission ){
            if ( current_user_can( $permission ) ){
                $pass = true;
            }
        }
        return $pass;
    }
}
Disciple_Tools_Quick_Comments_Endpoints::instance();
