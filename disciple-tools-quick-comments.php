<?php
/**
 *Plugin Name: Disciple.Tools - Quick Comments
 * Plugin URI: https://github.com/prykon/disciple-tools-quick-comments
 * Description: Disciple.Tools - Quick Comments Plugin is intended to help users post updates more efficiently.
 * Text Domain: disciple-tools-quick-comments
 * Domain Path: /languages
 * Version:  1.2
 * Author URI: https://github.com/prykon
 * GitHub Plugin URI: https://github.com/prykon/disciple-tools-quick-comments
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 5.6
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
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
    $is_theme_dt = class_exists( "Disciple_Tools" );
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

        require_once( 'rest-api/rest-api.php' ); // adds starter rest api class

        $this->i18n();

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
          <h3><?php echo esc_html__( 'Quick Comments Buttons', 'disciple-tools-quick-comments' ); ?></h3>
          <p><?php echo esc_html__( 'These quick comments buttons are here to aid you in updating the state of contacts and groups in a faster and descriptive fashion.', 'disciple-tools-quick-comments' ); ?></p>
        </div>
        <?php
    }

    public function add_make_quick_comment_link() {
        ?>
        <script>
            // Create a new quick comment
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
                    //Check if there's a "no quick comments created yet" row and delete it
                    try {
                      $("#no-quick-comments").remove();
                    }
                    catch(err){
                      return true; // It's ok if there's no "no quick comments created yet" row.
                    }

                    $("ul").find("[data-open='create-quick-comment-modal']").after(`
                        <li class="quick-comment-menu" data-quick-comment-id="` + last_comment_id + `">
                               <a data-type="quick-comment" id="newest-quick-comment">` + commentContent + `</a>
                           </li>` );
                } )
            } );

          // Post the quick comment from dropdown menu
          $( document ).on( 'click', 'a[data-type="quick-comment"]', function() {
            let postId = window.detailsSettings.post_id;
            let postType = window.detailsSettings.post_type;
            let commentContent = $( this ).text();
            let commentType = 'comment';
            window.API.post_comment( postType, postId, commentContent, commentType ).done( data => {} ).catch( err => {
              console.log( 'error' );
              console.log( err );
              jQuery( '#errors' ).append( err.responseText);
            } )
          } );

            $( document ).ready( function() {
                try {
                    let postType = window.detailsSettings.post_type;
                }
                catch(err) {
                   console.log( 'error' );
                   console.log( err );
                   jQuery( '#errors' ).append( err.responseText);
                }
            })
        </script>
        <?php
    }

    public function quick_comments_menu( $post_type = '' ){
        ?>
        <ul class="dropdown menu" data-dropdown-menu style="display: inline-block">
            <li style="border-radius: 5px">
                <a class="button menu-white-dropdown-arrow"
                   style="background-color: #00897B; color: white;"><?php echo esc_html__( 'Quick Comments', 'disciple-tools-quick-comments' ); ?>
               </a>
                <ul id="quick-answers-dropdown-menu" class="menu is-dropdown-submenu" style="width: max-content">
                    <li class="quick-comment-menu" data-open="create-quick-comment-modal" style="border-bottom:1px solid #cacaca;">
                      <a><i><?php echo esc_html__( 'new quick comment...', 'disciple-tools-quick-comments' ); ?></i></a>
                    </li>
                    <?php $quick_comments = Disciple_Tools_Quick_Comments_Endpoints::get_quick_comments( $post_type );
                    if ( ! $quick_comments ) {
                        echo '
                            <li class="quick-comment-menu">
                                <a data-open="create-quick-comment-modal" id="no-quick-comments" style="color:#717171;"><i>' . esc_html__( "No quick comments created yet.", "disciple-tools-quick-comments" ) . '</i></a>
                            </li>';
                    } else {
                        foreach ( $quick_comments as $qc ) {
                            echo '
                              <li class="quick-comment-menu" data-quick-comment-id="' . esc_html( $qc[0] ) . '">
                                  <a data-type="quick-comment">' . esc_html( $qc[2] ) . '</a>
                              </li>';
                        }
                    }
                    ?>

                    <li class="quick-comment-menu" data-open="manage-quick-comments-modal" style="border-top:1px solid #cacaca;">
                      <a onclick="populate_quick_comments_manager('<?php echo esc_attr( $post_type ); ?>');"><?php echo esc_html__( 'manage quick comments', 'disciple-tools-quick-comments' ); ?></a>
                    </li>
                </ul>
            </li>
        </ul>

        <button class="help-button" data-section="quick-comments-help-text">
            <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() ); ?>/dt-assets/images/help.svg"/>
        </button>

        <div class="reveal" id="create-quick-comment-modal" data-reveal data-reset-on-close>
            <h3><?php esc_html__( 'Quick Comments', 'disciple-tools-quick-comments' ); ?></h3>
            <p><?php esc_html__( 'Add a new quick comment', 'disciple-tools-quick-comments' ); ?></p>

            <form class="js-create-quick-comment">
                <label for="title">
                    <?php esc_html_e( 'Quick Comment', 'disciple-tools-quick-comments' ); ?>
                </label>
                <input name="title" id="new-quick-comment" type="text" placeholder="<?php esc_html_e( "Quick Comments", 'disciple-tools-quick-comments' ); ?>" required aria-describedby="name-help-text">
                <p class="help-text" id="name-help-text"><?php esc_html_e( "This is required", "disciple-tools-quick-comments" ); ?></p>
            </form>

            <div class="grid-x">
                <button class="button button-cancel clear" data-close aria-label="Close reveal" type="button">
                    <?php echo esc_html__( 'Cancel', 'disciple-tools-quick-comments' )?>
                </button>
                <button class="button" data-close type="button" id="create-quick-comment-return">
                    <?php esc_html_e( 'Create and post quick comment', 'disciple-tools-quick-comments' ); ?>
                </button>
                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>

        <div class="reveal" id="manage-quick-comments-modal" data-reveal data-reset-on-close>
            <h3><?php esc_html_e( 'Manage Quick Comments', 'disciple-tools-quick-comments' )?></h3>
            <p><?php esc_html_e( 'Select which quick comments you want to un-quicken', 'disciple-tools-quick-comments' ); ?></p>
            <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__( 'Quick Comments', 'disciple-tools-quick-comments' ); ?></th>
                    <th><?php echo esc_html__( 'Type', 'disciple-tools-quick-comments' ); ?></th>
                    <th style="text-align: right;"><?php echo esc_html__( 'Action', 'disciple-tools-quick-comments' ); ?></th>
                </tr>
            </thead>
            <tbody id="manage-quick-comments-modal-body">
            </tbody>
        </table>
                    </div><!-- end post-body-content -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div>
        </div>
        <script>
            function unquicken_comment( commentId ) {
                jQuery( function( $ ) {
                    $.ajax( {
                        type: "GET",
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: window.location.origin + '/wp-json/disciple_tools_quick_comments/v1/update_quick_comments/unquicken/' + commentId,
                        beforeSend: function( xhr ) {
                            xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>' );
                        },
                    } )
                    .done( function( data ) {
                        window.location.reload()
                    } );
                })
            }

            function populate_quick_comments_manager( postType ) {
                // Get all quick comments for postType
                jQuery.ajax( {
                    type: "GET",
                    contentType: "application/json; charset=utf-8",
                    dataType: "json",
                    url: window.location.origin + '/wp-json/disciple_tools_quick_comments/v1/get_quick_comments/' + postType,
                    beforeSend: function( xhr ) {
                        xhr.setRequestHeader( 'X-WP-Nonce', '<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>')
                    },
                })

                // Add quick comments to manage-comments modal
                .done( jQuery.each( function( key, value ) {
                    if ( key.length <= 0 ) {
                        $('#manage-quick-comments-modal-body tr').remove();
                        $('#manage-quick-comments-modal-body').append(`
                            <tr>
                                <td colspan="3">
                                    <i>'<?php echo esc_html__( 'No quick comments created yet.', 'disciple-tools-quick-comments' ); ?>'<a data-open="create-quick-comment-modal"> <?php echo esc_html__( 'Create one now.', 'disciple-tools-quick-comments' ); ?></a></i>
                                </td>
                            </tr>
                            `);
                    } else {
                        $('#manage-quick-comments-modal-body tr').remove();
                        jQuery.each( key, function( k, v ) {
                           $('#manage-quick-comments-modal-body').append(`
                            <tr>
                                <td>
                                    ` + key[k][2] + `
                                </td>
                                <td>
                                    ` + key[k][1] + `
                                </td>
                                <td style="text-align: right;">
                                    <a href="javascript:unquicken_comment(` + key[k][0] + `);"><?php echo esc_html__( 'un-quicken', 'disciple-tools-quick-comments' ); ?></a>
                                </td>
                            </tr>
                        ` );
                        } );
                    }
                } ) );
            }
        </script>
          <?php
            self::add_make_quick_comment_link();
    }

    /**
     * Filters the array of row meta for each/specific plugin in the Plugins list table.
     * Appends additional links below each/specific plugin on the plugins page.
     */
    public function plugin_description_links( $links_array, $plugin_file_name, $plugin_data, $status ) {
        if ( strpos( $plugin_file_name, basename( __FILE__ ) ) ) {
            // You can still use `array_unshift()` to add links at the beginning.

            $links_array[] = '<a href="https://disciple.tools">Disciple.Tools Community</a>';

        }

        return $links_array;
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


if ( ! function_exists( 'disciple_tools_quick_comments_hook_admin_notice' ) ) {
    function disciple_tools_quick_comments_hook_admin_notice() {
        global $disciple_tools_quick_comments_required_dt_theme_version;
        $wp_theme = wp_get_theme();
        $current_version = $wp_theme->version;
        $message = "'Disciple.Tools - Quick Comments Plugin' plugin requires 'Disciple.Tools' theme to work. Please activate 'Disciple.Tools' theme or make sure it is latest version.";
        if ( $wp_theme->get_template() === "disciple-tools-theme" ){
            $message .= ' ' . sprintf( esc_html( 'Current Disciple.Tools version: %1$s, required version: %2$s' ), esc_html( $current_version ), esc_html( $disciple_tools_quick_comments_required_dt_theme_version ) );
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
 * To remove: delete the section of code below and delete the file called version-control.json in the plugin root
 *
 * This section runs the remote plugin updating service, so you can issue distributed updates to your plugin
 *
 * @note See the instructions for version updating to understand the steps involved.
 * @link https://github.com/DiscipleTools/disciple-tools-quick-comments/wiki/Configuring-Remote-Updating-System
 *
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
add_action( 'plugins_loaded', function (){
    if ( is_admin() && !( is_multisite() && class_exists( "DT_Multisite" ) ) || wp_doing_cron() ){
        // Check for plugin updates
        if ( ! class_exists( 'Puc_v4_Factory' ) ) {
            if ( file_exists( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' )){
                require( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' );
            }
        }
        if ( class_exists( 'Puc_v4_Factory' ) ){
            Puc_v4_Factory::buildUpdateChecker(
                'https://raw.githubusercontent.com/prykon/disciple-tools-quick-comments/master/version-control.json',
                __FILE__,
                'disciple-tools-quick-comments'
            );
        }
    }
} );
