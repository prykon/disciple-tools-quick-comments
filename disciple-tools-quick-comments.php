<?php
/**
 * Plugin Name: Disciple Tools - Quick Comments Plugin
 * Plugin URI: https://github.com/DiscipleTools/disciple-tools-quick-comments
 * Description: Disciple Tools - Quick Comments Plugin is intended to help developers and integrator jumpstart their extension of the Disciple Tools system.
 * Text Domain: disciple-tools-quick-comments
 * Domain Path: /languages
 * Version:  1.5
 * Author URI: https://github.com/DiscipleTools
 * GitHub Plugin URI: https://github.com/DiscipleTools/disciple-tools-quick-comments
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 5.6
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Refactoring (renaming) this plugin as your own:
 * 1. @todo Refactor all occurrences of the name Disciple_Tools_Quick_Comments, disciple_tools_quick_comments, disciple-tools-quick-comments, quick_comment, and "Quick Comments Plugin"
 * 2. @todo Rename the `disciple-tools-quick-comments.php and menu-and-tabs.php files.
 * 3. @todo Update the README.md and LICENSE
 * 4. @todo Update the default.pot file if you intend to make your plugin multilingual. Use a tool like POEdit
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Gets the instance of the `Disciple_Tools_Quick_Comments` class.
 *
 * @since  0.1
 * @access public
 * @return object|bool
 */
function disciple_tools_quick_comments() {
    $disciple_tools_quick_comments_required_dt_theme_version = '1.0';
    $wp_theme = wp_get_theme();
    $version = $wp_theme->version;

    /*
     * Check if the Disciple.Tools theme is loaded and is the latest required version
     */
    $is_theme_dt = strpos( $wp_theme->get_template(), "disciple-tools-theme" ) !== false || $wp_theme->name === "Disciple Tools";
    if ( $is_theme_dt && version_compare( $version, $disciple_tools_quick_comments_required_dt_theme_version, "<" ) ) {
        add_action( 'admin_notices', 'disciple_tools_quick_comments_hook_admin_notice' );
        add_action( 'wp_ajax_dismissed_notice_handler', 'dt_hook_ajax_notice_handler' );
        return false;
    }
    if ( !$is_theme_dt ){
        return false;
    }
    /**
     * Load useful function from the theme
     */
    if ( !defined( 'DT_FUNCTIONS_READY' ) ){
        require_once get_template_directory() . '/dt-core/global-functions.php';
    }

    return Disciple_Tools_Quick_Comments::instance();
}

add_action( 'after_setup_theme', 'disciple_tools_quick_comments', 20 );




/**
 * Singleton class for setting up the plugin.
 *
 * @since  0.1
 * @access public
 */
class Disciple_Tools_Quick_Comments {

    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        $is_rest = dt_is_rest();

        if ( strpos( dt_get_url_path(), 'disciple_tools_quick_comments_template' ) !== false ) {
            require_once( 'rest-api/rest-api.php' ); // adds starter rest api class
        } else {
            require_once( 'rest-api/rest-api.php' ); //@todo find out why dropdown menu won't work without this else
        }

        /**
         * @todo Decide if you want to support localization of your plugin
         * To remove: delete the line below and remove the folder named /languages
         */
        $this->i18n();

        /**
         * @todo Decide if you want to customize links for your plugin in the plugin admin area
         * To remove: delete the lines below and remove the function named
         */
        if ( is_admin() ) { // adds links to the plugin description area in the plugin admin list.
            add_filter( 'plugin_row_meta', [ $this, 'plugin_description_links' ], 10, 4 );
        }

        add_action( 'dt_comment_action_quick_action', [ $this, 'quick_comments_menu' ] );
        add_action( 'dt_modal_help_text', [ $this, 'quick_comments_modal_help_text' ] );
    }

    /** Hooks help text into modal-help.php */
    public function quick_comments_modal_help_text(){
        ?>
        <div class="help-section" id="quick-comments-help-text" style="display: none">
          <h3>Quick Comments Buttons</h3>
          <p>These quick comments buttons are here to aid you in updating comments faster and in a descriptive fashion.</p>
          <p>You can create a posted comment into a quick comment by clicking on the \'create quick comment\' link next to it.</p>
        </div>
        <?php
            self::add_make_quick_comment_link();
    }

    public function add_make_quick_comment_link(){
        $qc_nonce = wp_create_nonce( 'qc_wp_rest' ); ?>
        ?>
          <script>
            // Get quick comments and add them to dropdown menu
            function get_quick_comments( postType ) {
              $.ajax( {
                type: "GET",
                contentType: "application/json; charset=utf-8",
                dataType: "json",
                url: window.location.origin + '/wp-json/disciple_tools_quick_comments/v1/get_quick_comments/' + postType,
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>' );
                },
                } ).done( function( data ) {
                  //First clear current links so the new response doesn't get appended to them
                  let new_quick_comment_text = 'new quick comment...';
                  $('#quick-answers-dropdown-menu').contents().remove();
                  $('#quick-answers-dropdown-menu').append( `
                    <li class="quick-comment-menu" data-open="create-quick-comment-modal" style="border-bottom:1px solid #cacaca;"'>
                      <a><i>` + new_quick_comment_text + `</i></a>
                    </li>`);

                  if ( data.length > 0 ) {
                    for( var i = 0; i < data.length; i++ ) {
                      $( '#quick-answers-dropdown-menu' ).append( `
                           <li class="quick-comment-menu" data-quick-comment-id="` + data[i][0] + `">
                               <a data-type="quick-comment">` + data[i][2] + `</a>
                           </li>` );
                    }                  
                  } else {
                      $( '#quick-answers-dropdown-menu' ).append( `
                          <li class="quick-comment-menu">
                              <a><i>no quick comments created yet for ` + postType + `</i></a>
                          </li>` );
                  }
                  $('#quick-answers-dropdown-menu').append( `
                    <li class="quick-comment-menu" data-open="manage-quick-comments-modal" style="border-top:1px solid #cacaca;"'>
                      <a><?php esc_html_e( 'manage quick comments', 'disciple_tools' ); ?></a>
                    </li>` );
              } );
            }

            $( '#create-quick-comment-return' ).on( 'click', function () {
            let commentContent = $('#new-quick-comment').val();
            let postId = window.detailsSettings.post_id;
            let postType = window.detailsSettings.post_type;
            window.API.post_comment( postType, postId, commentContent, 'comment' ).done( function( data ) {
              let last_comment_id = data.comment_ID;
                  $.ajax( {
                    type: "GET",
                     contentType: "application/json; charset=utf-8",
                     dataType: "json",
                     url: window.location.origin + '/wp-json/disciple_tools_quick_comments/v1/update_quick_comments/quicken/' + last_comment_id,
                     beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ) ?>' );
                    },
                  } );
                } ).done( function( new_data ) {
                    window.location.reload();
                } );
            } );

          // Post the quick comment from dropdown menu
          $( document ).on( 'click', 'a[data-type="quick-comment"]', function() {
            let postId = window.detailsSettings.post_id;
            let postType = window.detailsSettings.post_type;
            let commentContent = $( this ).text();
            let commentType = 'qc_' + postType;
            window.API.post_comment( postType, postId, commentContent, commentType ).done( data => {} ).catch( err => {
              console.log( 'error' );
              console.log( err );
              jQuery( '#errors' ).append( err.responseText);
            } )
          } );

            $( document ).ready( function() {
              let postType = window.detailsSettings.post_type;
              get_quick_comments( postType );
            })
        </script>
        
        <?php
    }

    public function quick_comments_menu( $post_type = '' ){
        ?>
        <ul class="dropdown menu" data-dropdown-menu style="display: inline-block">
                <li style="border-radius: 5px">
                    <a class="button menu-white-dropdown-arrow"
                       style="background-color: #00897B; color: white;">Quick Comments
                   </a>
                <ul id="quick-answers-dropdown-menu" class="menu is-dropdown-submenu" style="width: max-content">
                </ul>
            </li>
        </ul>
        
        <button class="help-button" data-section="quick-comments-help-text">
            <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() ); ?>/dt-assets/images/help.svg"/>
        </button>

        <div class="reveal" id="create-quick-comment-modal" data-reveal data-reset-on-close>
            <h3><?php esc_html_e( 'Quick Comments', 'disciple_tools' ); ?></h3>
            <p><?php esc_html_e( 'Add a new quick comment', 'disciple_tools' ); ?></p>

            <form class="js-create-quick-comment">
                <label for="title">
                    <?php esc_html_e( 'Quick Comment', 'disciple_tools' ); ?>
                </label>
                <input name="title" id="new-quick-comment" type="text" placeholder="<?php esc_html_e( "Quick Comments", 'disciple_tools' ); ?>" required aria-describedby="name-help-text">
                <p class="help-text" id="name-help-text"><?php esc_html_e( "This is required", "disciple_tools" ); ?></p>
            </form>

            <div class="grid-x">
                <button class="button button-cancel clear" data-close aria-label="Close reveal" type="button">
                    <?php echo esc_html__( 'Cancel', 'disciple_tools' )?>
                </button>
                <button class="button" data-close type="button" id="create-quick-comment-return">
                    <?php esc_html_e( 'Create and post quick comment', 'disciple_tools' ); ?>
                </button>
                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>

        <div class="reveal" id="manage-quick-comments-modal" data-reveal data-reset-on-close>
            <h3><?php esc_html_e( 'Manage Quick Comments', 'disciple_tools' )?></h3>
            <p><?php esc_html_e( 'Select which quick comments you want to un-quicken', 'disciple_tools' ); ?></p>
            <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <?php
                            $this->main_column( $post_type );
                        ?>
                    </div><!-- end post-body-content -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div>
        </div>
          <?php
    }

    public function main_column( $post_type = 'all') {
        if ( $post_type !== 'all' ) {
            $rest_request = new WP_REST_Request( 'GET', '/disciple_tools_quick_comments/v1/get_quick_comments/' . esc_sql( $post_type ) );
            $rest_request->set_query_params( [ 'post_type' => esc_sql( $post_type ) ] );
            $response = rest_do_request( $rest_request );
            $server = rest_get_server();
            $quick_comments = $server->response_to_data( $response, false );
        } else {
            $rest_request = new WP_REST_Request( 'GET', '/disciple_tools_quick_comments/v1/get_all_quick_comments' );
            $response = rest_do_request( $rest_request );
            $server = rest_get_server();
            $quick_comments = $server->response_to_data( $response, false );
        }

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
                <?php if ( ! $quick_comments ) : ?>
                <tr>
                    <td colspan="3">
                        <i>No quick comments created yet. <a data-open="create-quick-comment-modal">Create one now.</a></i>
                    </td>
                </tr>
            <?php endif; ?>
                <?php foreach ( $quick_comments as $qc ) : ?>
                    <?php
                        $comment_id = $qc[0];
                        $comment_post_type = $qc[1];
                        $comment_content = $qc[2];
                    ?>
                <tr>
                    <td>
                        <?php echo esc_html( $comment_content ); ?>
                    </td>
                    <td>
                        <?php echo esc_html( $comment_post_type ); ?>
                    </td>
                    <td style="text-align: right;">
                        <a href="javascript:unquicken_comment(<?php echo esc_html( $comment_id ); ?>);">un-quicken</a>
                    </td>
                </tr>
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
                        url: window.location.origin + '/wp-json/disciple_tools_quick_comments/v1/update_quick_comments/unquicken/' + commentId,
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ) ?>' );
                        },
                    } )
                    .done( function( data ) {
                        window.location.reload()
                    } );
                })
            }
        </script>
        <?php
    }

    /**
     * Filters the array of row meta for each/specific plugin in the Plugins list table.
     * Appends additional links below each/specific plugin on the plugins page.
     */
    public function plugin_description_links( $links_array, $plugin_file_name, $plugin_data, $status ) {
        if ( strpos( $plugin_file_name, basename( __FILE__ ) ) ) {
            // You can still use `array_unshift()` to add links at the beginning.

            $links_array[] = '<a href="https://disciple.tools">Disciple.Tools Community</a>'; // @todo replace with your links.
            // @todo add other links here
        }

        return $links_array;
    }


    /**
     * Method that runs only when the plugin is activated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function activation() {
      //update_user_meta acts as add_user_meta if name doesn't exist
      // $dt_quick_comments = array();
      // $dt_quick_comments['contacts'] = "";
      // $dt_quick_comments['groups'] = "";
      // update_user_meta('dt_quick_comments', 2, $dt_quick_comments );
    }

    /**
     * Loads the translation files.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function i18n() {
        $domain = 'disciple-tools-quick-comments';
        load_plugin_textdomain( $domain, false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ). 'languages' );
    }

    /**
     * Magic method to output a string if trying to use the object as a string.
     *
     * @since  0.1
     * @access public
     * @return string
     */
    public function __toString() {
        return 'disciple-tools-quick-comments';
    }

    /**
     * Magic method to keep the object from being cloned.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to keep the object from being unserialized.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to prevent a fatal error when calling a method that doesn't exist.
     *
     * @param string $method
     * @param array $args
     * @return null
     * @since  0.1
     * @access public
     */
    public function __call( $method = '', $args = array() ) {
        _doing_it_wrong( "disciple_tools_quick_comments::" . esc_html( $method ), 'Method does not exist.', '0.1' );
        unset( $method, $args );
        return null;
    }
}


// Register activation hook.
register_activation_hook( __FILE__, [ 'Disciple_Tools_Quick_Comments', 'activation' ] );
register_deactivation_hook( __FILE__, [ 'Disciple_Tools_Quick_Comments', 'deactivation' ] );


if ( ! function_exists( 'disciple_tools_quick_comments_hook_admin_notice' ) ) {
    function disciple_tools_quick_comments_hook_admin_notice() {
        global $disciple_tools_quick_comments_required_dt_theme_version;
        $wp_theme = wp_get_theme();
        $current_version = $wp_theme->version;
        $message = "'Disciple Tools - Quick Comments Plugin' plugin requires 'Disciple Tools' theme to work. Please activate 'Disciple Tools' theme or make sure it is latest version.";
        if ( $wp_theme->get_template() === "disciple-tools-theme" ){
            $message .= ' ' . sprintf( esc_html( 'Current Disciple Tools version: %1$s, required version: %2$s' ), esc_html( $current_version ), esc_html( $disciple_tools_quick_comments_required_dt_theme_version ) );
        }
        // Check if it's been dismissed...
        if ( ! get_option( 'dismissed-disciple-tools-quick-comments', false ) ) { ?>
            <div class="notice notice-error notice-disciple-tools-quick-comments is-dismissible" data-notice="disciple-tools-quick-comments">
                <p><?php echo esc_html( $message );?></p>
            </div>
            <script>
                jQuery(function($) {
                    $( document ).on( 'click', '.notice-disciple-tools-quick-comments .notice-dismiss', function () {
                        $.ajax( ajaxurl, {
                            type: 'POST',
                            data: {
                                action: 'dismissed_notice_handler',
                                type: 'disciple-tools-quick-comments',
                                security: '<?php echo esc_html( wp_create_nonce( 'wp_rest_dismiss' ) ) ?>'
                            }
                        })
                    });
                });
            </script>
        <?php }
    }
}

/**
 * AJAX handler to store the state of dismissible notices.
 */
if ( ! function_exists( "dt_hook_ajax_notice_handler" )){
    function dt_hook_ajax_notice_handler(){
        check_ajax_referer( 'wp_rest_dismiss', 'security' );
        if ( isset( $_POST["type"] ) ){
            $type = sanitize_text_field( wp_unslash( $_POST["type"] ) );
            update_option( 'dismissed-' . $type, true );
        }
    }
}

/**
 * Plugin Releases and updates
 * @todo Uncomment and change the url if you want to support remote plugin updating with new versions of your plugin
 * To remove: delete the section of code below and delete the file called version-control.json in the plugin root
 *
 * This section runs the remote plugin updating service, so you can issue distributed updates to your plugin
 *
 * @note See the instructions for version updating to understand the steps involved.
 * @link https://github.com/DiscipleTools/disciple-tools-quick-comments/wiki/Configuring-Remote-Updating-System
 *
 * @todo Enable this section with your own hosted file
 * @todo An example of this file can be found in (version-control.json)
 * @todo Github is a good option for delivering static json.
 */
/**
 * Check for plugin updates even when the active theme is not Disciple.Tools
 *
 * Below is the publicly hosted .json file that carries the version information. This file can be hosted
 * anywhere as long as it is publicly accessible. You can download the version file listed below and use it as
 * a template.
 * Also, see the instructions for version updating to understand the steps involved.
 * @see https://github.com/DiscipleTools/disciple-tools-version-control/wiki/How-to-Update-the-Starter-Plugin
 */
//add_action( 'plugins_loaded', function (){
//    if ( is_admin() ){
//        // Check for plugin updates
//        if ( ! class_exists( 'Puc_v4_Factory' ) ) {
//            if ( file_exists( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' )){
//                require( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' );
//            }
//        }
//        if ( class_exists( 'Puc_v4_Factory' ) ){
//            Puc_v4_Factory::buildUpdateChecker(
//                'https://raw.githubusercontent.com/DiscipleTools/disciple-tools-facebook/master/version-control.json',
//                __FILE__,
//                'disciple-tools-facebook'
//            );
//
//        }
//    }
//} );
