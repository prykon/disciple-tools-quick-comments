<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class Disciple_Tools_Quick_Comments_Menu
 */
class Disciple_Tools_Quick_Comments_Menu extends Disciple_Tools_Abstract_Menu_Base {
    public $token = 'disciple_tools_quick_comments';
    private static $_instance = null;

    /**
     * Get all types of quick_comment types dynamically.
     * This helps ensure that even if a new post type is created by a plugin,
     * it can still have Quick Comment compatibility.
     */
    public static function get_all_quick_comment_types() {
        $current_user_id = 2;// @todo get_current_user_id();
        $dt_quick_comments = get_user_meta( $current_user_id, 'dt_quick_comments', false );
        $dt_quick_comment_types = array_keys( $dt_quick_comments[0] );
        return $dt_quick_comment_types;
    }

    /**
     * Disciple_Tools_Quick_Comments_Menu Instance
     *
     * Ensures only one instance of Disciple_Tools_Quick_Comments_Menu is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return Disciple_Tools_Quick_Comments_Menu instance
     */
    public static function instance() {        
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()


    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {
        add_action( "admin_menu", array( $this, "register_menu" ) );
    } // End __construct()

    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        add_submenu_page( 'dt_extensions', 'Quick Comments Plugin', 'Quick Comments Plugin', 'manage_dt', $this->token, [ $this, 'content' ] );
    }

    /**
     * Menu stub. Replaced when Disciple Tools Theme fully loads.
     */
    public function extensions_menu() {}

    /**
     * Builds page contents
     * @since 0.1
     */
    public function content() {

        if ( !current_user_can( 'manage_dt' ) ) { // manage dt is a permission that is specific to Disciple Tools and allows admins, strategists and dispatchers into the wp-admin
           wp_die( 'You do not have sufficient permissions to access this page.' );
        }

        $tabs = self::get_all_quick_comment_types();

        if ( isset( $_GET["tab"] ) ) {
            $tab = sanitize_key( wp_unslash( $_GET[ "tab"] ) );
        } else {
            $tab = 'all';
        }

        $link = 'admin.php?page='.$this->token.'&tab=';

        ?>
        <div class="wrap">
            <h2>Quick Comments Plugin</h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'all' ?>" class="nav-tab <?php echo esc_html( ( $tab == 'all' || !isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">All</a>
                <?php foreach($tabs as $t ) : ?>
                
                <a href="<?php echo esc_attr( $link ) . $t ?>" class="nav-tab <?php echo esc_html( ( $tab == $t || !isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>"><?php echo ucwords( esc_html( $t ) ); ?></a>
            <?php endforeach; ?>
            </h2>
            <?php
                $object = new Disciple_Tools_Quick_Comments_Tab( esc_html( $tab ) );
                $object->content( $tab );
            ?>
        </div><!-- End wrap -->

        <?php
    }
}
Disciple_Tools_Quick_Comments_Menu::instance();

/**
 * Class Disciple_Tools_Quick_Comments_Tab
 */
class Disciple_Tools_Quick_Comments_Tab {

    public function content( $post_type = 'all' ) {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->
                        <?php
                            $this->main_column( $post_type );
                        ?>
                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->

                        <?php $this->right_column() ?>

                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    /**
     * Get distinct values for the comment_content / comment_type combination
     * and a comment_id for referencing the exact comment_content in future comment_type updates
     **/
    public function get_quick_comments( $post_type = 'all' ){
        $current_user_id = 2;// @todo get_current_user_id();
        $dt_quick_comments = get_user_meta( $current_user_id, 'dt_quick_comments', false );
        $post_type = esc_html( $post_type );

        if ( $post_type !== 'all' ) {
            foreach( $dt_quick_comments[0] as $comment_post_type => $quick_comment ) {
                foreach( $quick_comment as $comment_content ) {
                    if ( $comment_post_type !== $post_type ){
                        unset($dt_quick_comments[0][ $comment_post_type ] );
                    }
                } 
            }
        }
        return $dt_quick_comments[0];       
    }

    public function main_column( $post_type = 'all') {
        $quick_comments = self::get_quick_comments( $post_type );
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Quick Comments</th>
                    <th>Type</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if( ! $quick_comments ) : ?>
                <tr>
                    <td colspan="3">
                        <i>No quick comments created yet</i>
                    </td>
                </tr>
            <?php endif; ?>
                <?php foreach( $quick_comments as $comment_post_type => $quick_comment ) : ?>
                    <?php foreach( $quick_comment as $comment_content ) : ?>
                <tr>
                    <td>
                        <?php echo esc_html( $comment_content ); ?>
                    </td>
                    <td>
                        <?php echo esc_html( $comment_post_type ); ?>
                    </td>
                    <td style="text-align: right;">
                        <a href="javascript:unquicken_comment(<?php echo $v; ?>);">un-quicken</a>
                    </td>
                </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>

        <script>
            function unquicken_comment( commentId ) {
                jQuery( function( $ ) {
                    $.ajax( {
                        type: "GET",
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: window.location.origin + '/wp-json/disciple_tools_quick_comments/v1/update_quick_comments/unquicken/' + commentId
                    } )
                    .done( function( data ) {
                        window.location.reload()
                    } );
                })
            }

            // jQuery( function( $ ) {
            //     $( document ).ready( function() {
            //         $( document ).on( 'click', '.unquicken-comment', function () {
            //             let commentId = $( this ).data( 'comment-id' );
            //             console.log($( this ).data( 'comment-id' ));
            //             $.ajax( {
            //                 type: "GET",
            //                 contentType: "application/json; charset=utf-8",
            //                 dataType: "json",
            //                 url: window.location.origin + '/wp-json/disciple_tools_quick_comments/v1/update_quick_comments/unquicken/' + commentId
            //             } )
            //             .done( function( data ) {
            //                 window.location.reload()
            //             } );
            //         } );
            //     } );
            // } );
        </script>
        <?php
    }



    public function right_column( $post_type = 'all' ) {
        ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Information</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Content</td>
                </tr>
            </tbody>
        </table>
        <br>
        <?php
    }
}

