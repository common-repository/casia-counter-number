<?php
/**
Plugin Name: Casia Counter Number
Plugin URI: http://vividweb.eu/casia-plugin/casia-counter-number/
Description: Easily insert counters to your site.
Version: 1.1
Author: castellar120
License: GPLv2 or later
Text Domain: cascn
*/

//Actions
add_action( 'wp_enqueue_scripts', 'cascn_custom_styles' );
add_action( 'admin_enqueue_scripts', 'cascn_load_media_files' );
add_action('manage_posts_custom_column', 'cascn_sc_col_content', 10, 2);

//Filters
add_filter('manage_posts_columns', 'cascn_sc_col_title');

//Shortcodes
add_shortcode( 'cascn', 'cascn_shortcode' );

/**
 * Load plugin textdomain.
 *
 * @since 1.0.0
 */
function cascn_load_textdomain() {
  load_plugin_textdomain( 'cascn', false, basename( dirname( __FILE__ ) ) . '/languages' );
}

/**
 * Check if picture is already added into media library.
 *
 * @param  String $filename
 * @return bool    true if picture exists and false if it doesn't.
 */
function cascn_media_exists($filename) {
  global $wpdb;
  $file_without_ext = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filename);
  $file_no_space_around_dash = str_replace(' - ', '-', $file_without_ext);
  $dashed_name = str_replace(' ', '-', $file_no_space_around_dash);
  $query = "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_title='$dashed_name'";
  $count = intval($wpdb->get_var($query));
  return $count != 0;
}

/**
 * Insert an attachment from an URL address.
 *
 * @param  String $url
 * @param  Int    $parent_post_id
 * @return Int    Attachment ID
 */
function cascn_insert_attachment_from_url($url, $parent_post_id = null) {
  if( !class_exists( 'WP_Http' ) )
    include_once( ABSPATH . WPINC . '/class-http.php' );
  $http = new WP_Http();
  $response = $http->request( $url );
  if( $response['response']['code'] != 200 ) {
    return false;
  }
  $upload = wp_upload_bits( basename($url), null, $response['body'] );
  if( !empty( $upload['error'] ) ) {
    return false;
  }
  $file_path = $upload['file'];
  $file_name = basename( $file_path );
  $file_type = wp_check_filetype( $file_name, null );
  $attachment_title = sanitize_file_name( pathinfo( $file_name, PATHINFO_FILENAME ) );
  $wp_upload_dir = wp_upload_dir();
  $post_info = array(
    'guid'           => $wp_upload_dir['url'] . '/' . $file_name,
    'post_mime_type' => $file_type['type'],
    'post_title'     => $attachment_title,
    'post_content'   => '',
    'post_status'    => 'inherit',
  );
  // Create the attachment
  $attach_id = wp_insert_attachment( $post_info, $file_path, $parent_post_id );
  // Include image.php
  require_once( ABSPATH . 'wp-admin/includes/image.php' );
  // Define attachment metadata
  $attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
  // Assign metadata to attachment
  wp_update_attachment_metadata( $attach_id,  $attach_data );
  return $attach_id;
}

/**
 * Add images to uploads dir on plugin activation.
 *
 * @since 1.0.0
 */
function cascn_activate() {
  /**
   * Names of icons to upload.
   *
   * @since 1.0.0
   */
  $cascn_images = ['185020 - macintosh.png', '185021 - bomb bug.png', '185022 - computer macintosh vintage.png', '185023 - computer imac.png', '185024 - computer imac.png', '185025 - ibook laptop.png', '185026 - laptop macbook streamline.png', '185027 - computer network streamline.png', '185028 - streamline.png', '185029 - ipod streamline.png', '185030 - cook pan pot streamline.png', '185031 - shoes snickers streamline.png', '185032 - ipad streamline.png', '185033 - ipod music streamline.png', '185034 - ipod mini music streamline.png', '185035 - streamline.png', '185036 - remote control streamline.png', '185037 - browser streamline window.png', '185038 - home house streamline.png', '185039 - earth globe streamline.png', '185040 - map pin streamline.png', '185041 - arrow streamline target.png', '185042 - edit modify streamline.png', '185043 - ink pen streamline.png', '185044 - pen streamline.png', '185045 - pen streamline.png', '185046 - design graphic tablet streamline tablet.png', '185047 - pen streamline.png', '185048 - pen streamline.png', '185049 - design pencil rule streamline.png', '185050 - eye dropper streamline.png', '185051 - crop streamline.png', '185052 - paint bucket streamline.png', '185053 - brush paint streamline.png', '185054 - painting roll streamline.png', '185055 - painting pallet streamline.png', '185056 - stamp streamline.png', '185057 - magic magic wand streamline.png', '185058 - grid lines streamline.png', '185059 - handle streamline vector.png', '185060 - magnet streamline.png', '185061 - photo pictures streamline.png', '185062 - camera photo streamline.png', '185063 - camera photo polaroid streamline.png', '185064 - picture streamline.png', '185065 - frame picture streamline.png', '185066 - picture streamline.png', '185067 - camera streamline video.png', '185068 - music note streamline.png', '185069 - headset sound streamline.png', '185070 - micro record streamline.png', '185071 - music speaker streamline.png', '185072 - book read streamline.png', '185073 - book dowload streamline.png', '185074 - notebook streamline.png', '185075 - envellope mail streamline.png', '185076 - streamline suitcase travel.png', '185077 - first aid medecine shield streamline.png', '185078 - email mail streamline.png', '185079 - bubble comment streamline talk.png', '185080 - bubble love streamline talk.png', '185081 - speech streamline talk user.png', '185082 - man people streamline user.png', '185083 - like love streamline.png', '185084 - crown king streamline.png', '185085 - happy smiley streamline.png', '185086 - map streamline user.png', '185087 - link streamline.png', '185088 - lock locker streamline.png', '185089 - locker streamline unlock.png', '185090 - delete garbage streamline.png', '185091 - danger death delete destroy skull streamline.png', '185092 - clock streamline time.png', '185093 - dashboard speed streamline.png', '185094 - settings streamline.png', '185095 - settings streamline.png', '185096 - settings streamline.png', '185097 - database streamline.png', '185098 - streamline sync.png', '185099 - factory lift streamline warehouse.png', '185100 - caddie shop shopping streamline.png', '185101 - caddie shopping streamline.png', '185102 - receipt shopping streamline.png', '185103 - bag shopping streamline.png', '185104 - streamline umbrella weather.png', '185105 - drug medecine streamline syringue.png', '185106 - armchair chair streamline.png', '185107 - backpack streamline trekking.png', '185108 - chaplin hat streamline.png', '185109 - cocktail mojito streamline.png', '185110 - diving leisure sea sport streamline.png', '185111 - monocle mustache streamline.png', '185112 - barista coffee espresso streamline.png', '185113 - coffee streamline.png', '185114 - chef food restaurant streamline.png', '185115 - barbecue eat food streamline.png', '185116 - eat food hotdog streamline.png', '185117 - food ice cream streamline.png', '185118 - japan streamline tea.png', '185119.png'];

  foreach ($cascn_images as $key => $value) {
    if (!cascn_media_exists($value)) {
      cascn_insert_attachment_from_url( plugins_url( '/img/streamline/64/'  . $value, __FILE__ ) );
    }
  }
}

register_activation_hook( __FILE__, 'cascn_activate' );

// Register Style
function cascn_custom_styles() {
  wp_register_style( 'cascn_main', plugins_url( 'main.css', __FILE__ ), false, false );
  wp_enqueue_style( 'cascn_main' );
}

if ( ! function_exists('cascn_counter_sections') ) {

/**
 * create counter section post type
 *
 * @since 1.0.0
 */
function cascn_counter_sections() {

  $labels = array(
    'name'                  => _x( 'Counter Sections', 'Post Type General Name', 'cascn' ),
    'singular_name'         => _x( 'Counter Section', 'Post Type Singular Name', 'cascn' ),
    'menu_name'             => __( 'Counter Sections', 'cascn' ),
    'name_admin_bar'        => __( 'Counter Section', 'cascn' ),
    'archives'              => __( 'Item Archives', 'cascn' ),
    'attributes'            => __( 'Item Attributes', 'cascn' ),
    'parent_item_colon'     => __( 'Parent Item:', 'cascn' ),
    'all_items'             => __( 'All Items', 'cascn' ),
    'add_new_item'          => __( 'Add New Item', 'cascn' ),
    'add_new'               => __( 'Add New', 'cascn' ),
    'new_item'              => __( 'New Item', 'cascn' ),
    'edit_item'             => __( 'Edit Item', 'cascn' ),
    'update_item'           => __( 'Update Item', 'cascn' ),
    'view_item'             => __( 'View Item', 'cascn' ),
    'view_items'            => __( 'View Items', 'cascn' ),
    'search_items'          => __( 'Search Item', 'cascn' ),
    'not_found'             => __( 'Not found', 'cascn' ),
    'not_found_in_trash'    => __( 'Not found in Trash', 'cascn' ),
    'featured_image'        => __( 'Featured Image', 'cascn' ),
    'set_featured_image'    => __( 'Set featured image', 'cascn' ),
    'remove_featured_image' => __( 'Remove featured image', 'cascn' ),
    'use_featured_image'    => __( 'Use as featured image', 'cascn' ),
    'insert_into_item'      => __( 'Insert into item', 'cascn' ),
    'uploaded_to_this_item' => __( 'Uploaded to this item', 'cascn' ),
    'items_list'            => __( 'Items list', 'cascn' ),
    'items_list_navigation' => __( 'Items list navigation', 'cascn' ),
    'filter_items_list'     => __( 'Filter items list', 'cascn' ),
  );
  $args = array(
    'label'                 => __( 'Counter Section', 'cascn' ),
    'description'           => __( 'Post Type Description', 'cascn' ),
    'labels'                => $labels,
    'supports'              => array( 'title', 'custom-fields' ),
    'hierarchical'          => false,
    'public'                => true,
    'show_ui'               => true,
    'show_in_menu'          => true,
    'menu_position'         => 5,
    'menu_icon'             => 'dashicons-admin-generic',
    'show_in_admin_bar'     => true,
    'show_in_nav_menus'     => true,
    'can_export'            => true,
    'has_archive'           => true,
    'exclude_from_search'   => false,
    'publicly_queryable'    => true,
    'capability_type'       => 'page',
  );
  register_post_type( 'cascn_count_section', $args );

}
add_action( 'init', 'cascn_counter_sections', 0 );

}

/**
 * Create shortcode to display counter sections.
 *
 * @since 1.0.0
 */
// Add Shortcode
function cascn_shortcode( $atts ) {
  // Attributes
  $atts = shortcode_atts(
    array(
      'id' => '',
    ),
    $atts,
    'cascn'
  );

  ob_start();

  $cascn_group = get_post_meta( $atts['id'], 'cascn_repeater' ); //array of arrays where are counter info arrays
?>
  <div class="cascn_counter">
<?php
    foreach ($cascn_group[0] as $key => $value) {
?>
      <div class="cascn_counter__el">
        <?php if (strlen(reset($value['cascn_image']))!=0) {
        ?>
          <img src="<?php echo esc_url(reset($value['cascn_image'])) ?>">
        <?php
        } else {
        ?>
          <div class="cascn_counter__img-place"></div>
        <?php
        } ?>
        <p class="cascn_counter__number">
<?php
        if (is_numeric($value['cascn_number'])) {
          echo $value['cascn_number'];
        }
?>

        </p>
        <p class="cascn_counter__title">
<?php
          echo esc_html($value['cascn_title']);
?>

        </p>
      </div>
<?php
    }
?>
  </div>
<?php
  return ob_get_clean();
}

/**
 * Show shortcode column title in edit.php when on Counter Section PT page.
 *
 * @since 1.0.0
 */
function cascn_sc_col_title($cascn_defaults) {
    if (get_query_var('post_type') == 'cascn_count_section') {
      $cascn_defaults['cascn_shortcode'] = 'Shortcode';
    }
    return $cascn_defaults;
}
 
/**
 * Show shortcode in shortcode column edit.php when on Counter Section PT page.
 *
 * @since 1.0.0
 */
function cascn_sc_col_content($cascn_column_name, $cascn_post_ID) {
    if ($cascn_column_name == 'cascn_shortcode') {
      echo '[cascn id="' . $cascn_post_ID . '"]';
    }
}

function cascn_load_media_files() {
    wp_enqueue_media();
}

/**
 * Get the bootstrap!
 * (Update path to use cmb2 or CMB2, depending on the name of the folder.
 * Case-sensitive is important on some systems.)
 */
require_once __DIR__ . '/cmb2/init.php';

add_action( 'cmb2_admin_init', 'cascn_repeater_metaboxes' );

/**
 * Define the metabox and field configurations.
 */
function cascn_repeater_metaboxes() {
  /**
   * Initiate the metabox
   */
  $cascn_cmb = new_cmb2_box( array(
    'id'            => 'cascn_box',
    'title'         => __( 'Counters Section', 'cascn' ),
    'object_types'  => array( 'cascn_count_section' ),
    'context'       => 'normal',
    'priority'      => 'high',
    'show_names'    => true,
  ) );
  $cascn_group = $cascn_cmb->add_field( array(
    'id'          => 'cascn_repeater',
    'type'        => 'group',
    'repeatable'  => true,
    'options'     => array(
      'group_title'   => 'Counter {#}',
      'add_button'    => 'Add Another Counter',
      'remove_button' => 'Remove Counter',
      'closed'        => true,
      'sortable'      => true,
    ),
  ) );
  $cascn_cmb->add_group_field( $cascn_group, array(
    'name' => __( 'Image', 'cascn' ),
    'id' => 'cascn_image',
    'type' => 'file_list',
  ) );
  $cascn_cmb->add_group_field( $cascn_group, array(
    'name' => __( 'Number', 'cascn' ),
    'id' =>'cascn_number',
    'type' => 'text_small',
    'attributes' => array(
      'type' => 'number',
      'pattern' => '\d*',
    ),
    'sanitization_cb' => 'absint',
    'escape_cb'       => 'absint',
  ) );
  $cascn_cmb->add_group_field( $cascn_group, array(
    'name' => __( 'Title', 'cascn' ),
    'id' => 'cascn_title',
    'type' => 'text_small',
  ) );
}