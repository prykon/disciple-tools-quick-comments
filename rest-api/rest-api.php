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
            ORDER BY comment_content ASC;
        ", 'qc_' . $post_type, /*get_current_user_id()*/ 2);

        $results = $wpdb->get_col( $query );
        return $results;
    }

    public function unquicken_comment( $comment_ID ) {
        global $wpdb;

        $query = $wpdb->prepare("
            UPDATE $wpdb->comments
            SET comment_type = 'comment'
            WHERE comment_id = %i;
            ", esq_sql( $comment_id ) );
        $response = $wpdb->get_results( $query, ARRAY_A );
        return $response;
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
