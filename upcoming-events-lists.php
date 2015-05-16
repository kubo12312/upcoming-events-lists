<?php
/**
 * Plugin Name: Upcoming Events Lists
 * Plugin URI: http://wordpress.org/plugins/upcoming-events-lists
 * Description: A plugin to show a list of upcoming events on the front-end.
 * Version: 1.2
 * Author: Sayful Islam
 * Author URI: http://sayful.net
 * Text Domain: upcoming-events
 * Domain Path: /languages/
 * License: GPL2
 */

if( !class_exists('Upcoming_Events_Lists') ):

class Upcoming_Events_Lists {

	public function __construct(){
		add_action( 'plugins_loaded', array( $this, 'load_textdomain') );
		add_action( 'wp_enqueue_scripts', array( $this, 'widget_style' ));
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_script_style' ) );
		add_action( 'init', array( $this, 'event_post_type'), 0 );
		add_action('do_meta_boxes', array( $this, 'events_img_box') );
		add_action('add_meta_boxes', array( $this, 'event_info_metabox') );
		add_action( 'save_post', array( $this, 'save_event_info' ) );

		add_filter( 'manage_edit-event_columns', array( $this, 'custom_columns_head'), 10 );
		add_action( 'manage_event_posts_custom_column', array( $this, 'custom_columns_content'), 10, 2 );
		add_filter( 'the_content', array( $this, 'upcoming_events_single_content') );

		// Include required files
		$this->includes();
	}

	// Including the widget
	public function includes(){
		include_once ( 'widget-upcoming-events.php' );
	}

	public function load_textdomain(){
  		load_plugin_textdomain( 'upcoming-events', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Enqueueing styles for the front-end widget
	 */
	public function widget_style() {
		if ( is_active_widget( '', '', 'sis_upcoming_events', true ) ) {
			wp_enqueue_style('upcoming-events', plugins_url('css/upcoming-events.css', __FILE__));
		}
	}

	/**
	 * Enqueueing scripts and styles in the admin
	 * @param  int $hook Current page hook
	 */
	public function admin_script_style( $hook ) {
		global $post_type;

		if ( ( 'post.php' == $hook || 'post-new.php' == $hook ) && ( 'event' == $post_type ) ) {

			wp_enqueue_script( 'upcoming-events', plugins_url('/js/upcoming-script.js', __FILE__ ), array( 'jquery', 'jquery-ui-datepicker' ), false, true );
			wp_enqueue_style('jquery-ui-calendar', plugins_url('css/jquery-ui-1.10.4.custom.min.css', __FILE__));
		}
	}

	// Register Custom Post Type
	public function event_post_type() {

		$labels = array(
			'name'                => _x( 'Events', 'Post Type General Name', 'upcoming-events' ),
			'singular_name'       => _x( 'Event', 'Post Type Singular Name', 'upcoming-events' ),
			'menu_name'           => __( 'Event', 'upcoming-events' ),
			'parent_item_colon'   => __( 'Parent Event:', 'upcoming-events' ),
			'all_items'           => __( 'All Events', 'upcoming-events' ),
			'view_item'           => __( 'View Event', 'upcoming-events' ),
			'add_new_item'        => __( 'Add New Event', 'upcoming-events' ),
			'add_new'             => __( 'Add New', 'upcoming-events' ),
			'edit_item'           => __( 'Edit Event', 'upcoming-events' ),
			'update_item'         => __( 'Update Event', 'upcoming-events' ),
			'search_items'        => __( 'Search Event', 'upcoming-events' ),
			'not_found'           => __( 'Not found', 'upcoming-events' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'upcoming-events' ),
		);
		$args = array(
			'label'               => __( 'event', 'upcoming-events' ),
			'description'         => __( 'A list of upcoming events', 'upcoming-events' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 35,
			'menu_icon'           => 'dashicons-calendar-alt',
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'page',
		);
		register_post_type( 'event', $args );

	}

	// Move featured image box under title
	public function events_img_box(){
	    remove_meta_box( 'postimagediv', 'event', 'side' );
	    add_meta_box('postimagediv', __('Upload Event Image', 'upcoming-events'), 'post_thumbnail_meta_box', 'event', 'side', 'low');
	}

	//Adding metabox for event information
	public function event_info_metabox() {
		add_meta_box( 'sis-event-info-metabox', __( 'Event Info', 'upcoming-events' ), array( $this, 'render_event_info_metabox'), 'event','side', 'core' );
	}

	/**
	 * Rendering the metabox for event information
	 * @param  object $post The post object
	 */
	public function render_event_info_metabox( $post ) {
		//generate a nonce field
		wp_nonce_field( basename( __FILE__ ), 'sis-event-info-nonce' );

		//get previously saved meta values (if any)
		$event_start_date = get_post_meta( $post->ID, 'event-start-date', true );
		$event_end_date = get_post_meta( $post->ID, 'event-end-date', true );
		$event_venue = get_post_meta( $post->ID, 'event-venue', true );

		//if there is previously saved value then retrieve it, else set it to the current time
		$event_start_date = ! empty( $event_start_date ) ? $event_start_date : time();

		//we assume that if the end date is not present, event ends on the same day
		$event_end_date = ! empty( $event_end_date ) ? $event_end_date : $event_start_date;

		?>
		<p> 
			<label for="sis-event-start-date"><?php _e( 'Event Start Date:', 'upcoming-events' ); ?></label>
			<input type="text" id="sis-event-start-date" name="sis-event-start-date" class="widefat sis-event-date-input" value="<?php echo date( 'F d, Y', $event_start_date ); ?>" placeholder="Format: February 18, 2014">
		</p>
		<p>
			<label for="sis-event-end-date"><?php _e( 'Event End Date:', 'upcoming-events' ); ?></label>
			<input type="text" id="sis-event-end-date" name="sis-event-end-date" class="widefat sis-event-date-input" value="<?php echo date( 'F d, Y', $event_end_date ); ?>" placeholder="Format: February 18, 2014">
		</p>
		<p>
			<label for="sis-event-venue"><?php _e( 'Event Venue:', 'upcoming-events' ); ?></label>
			<input type="text" id="sis-event-venue" name="sis-event-venue" class="widefat" value="<?php echo $event_venue; ?>" placeholder="eg. Times Square">
		</p>
		<?php
	}

	/**
	 * Saving the event along with its meta values
	 * @param  int $post_id The id of the current post
	 */
	function save_event_info( $post_id ) {
		//checking if the post being saved is an 'event',
		//if not, then return
		if ( isset($_POST['post_type']) && 'event' != $_POST['post_type'] ) {
			return;
		}

		//checking for the 'save' status
		$is_autosave = wp_is_post_autosave( $post_id );
		$is_revision = wp_is_post_revision( $post_id );
		$is_valid_nonce = ( isset( $_POST['sis-event-info-nonce'] ) && ( wp_verify_nonce( $_POST['sis-event-info-nonce'], basename( __FILE__ ) ) ) ) ? true : false;

		//exit depending on the save status or if the nonce is not valid
		if ( $is_autosave || $is_revision || ! $is_valid_nonce ) {
			return;
		}

		//checking for the values and performing necessary actions
		if ( isset( $_POST['sis-event-start-date'] ) ) {
			update_post_meta( $post_id, 'event-start-date', strtotime( $_POST['sis-event-start-date'] ) );
		}

		if ( isset( $_POST['sis-event-end-date'] ) ) {
			update_post_meta( $post_id, 'event-end-date', strtotime( $_POST['sis-event-end-date'] ) );
		}

		if ( isset( $_POST['sis-event-venue'] ) ) {
			update_post_meta( $post_id, 'event-venue', sanitize_text_field( $_POST['sis-event-venue'] ) );
		}
	}

	/**
	 * Custom columns head
	 * @param  array $defaults The default columns in the post admin
	 */
	function custom_columns_head( $defaults ) {
		unset( $defaults['date'] );

		$defaults['event_start_date'] = __( 'Start Date', 'upcoming-events' );
		$defaults['event_end_date'] = __( 'End Date', 'upcoming-events' );
		$defaults['event_venue'] = __( 'Venue', 'upcoming-events' );

		return $defaults;
	}

	/**
	 * Custom columns content
	 * @param  string 	$column_name The name of the current column
	 * @param  int 		$post_id     The id of the current post
	 */
	function custom_columns_content( $column_name, $post_id ) {
		if ( 'event_start_date' == $column_name ) {
			$start_date = get_post_meta( $post_id, 'event-start-date', true );
			echo date( 'F d, Y', $start_date );
		}

		if ( 'event_end_date' == $column_name ) {
			$end_date = get_post_meta( $post_id, 'event-end-date', true );
			echo date( 'F d, Y', $end_date );
		}

		if ( 'event_venue' == $column_name ) {
			$venue = get_post_meta( $post_id, 'event-venue', true );
			echo $venue;
		}
	}


	function upcoming_events_single_content( $content ){
		if ( is_singular('event') || is_post_type_archive('event') ) {

			$event_start_date = get_post_meta( get_the_ID(), 'event-start-date', true );
			$event_end_date = get_post_meta( get_the_ID(), 'event-end-date', true );
			$event_venue = get_post_meta( get_the_ID(), 'event-venue', true );

			$event  = '<table>';
			$event .= '<tr>';
			$event .= '<td><strong>'.__('Event Start Date:', 'upcoming-events').'</strong><br>'.date_i18n( get_option( 'date_format' ), $event_start_date ).'</td>';
			$event .= '<td><strong>'.__('Event End Date:', 'upcoming-events').'</strong><br>'.date_i18n( get_option( 'date_format' ), $event_end_date ).'</td>';
			$event .= '<td><strong>'.__('Event Vanue:', 'upcoming-events').'</strong><br>'.$event_venue.'</td>';
			$event .= '</tr>';
			$event .= '</table>';

			$content = $event . $content;
		}
		return $content;
	}
}

$upcoming_events_lists = new Upcoming_Events_Lists();
endif;

/**
 * Flushing rewrite rules on plugin activation/deactivation
 * for better working of permalink structure
 */
function upcoming_events_lists_activation_deactivation() {
	$events = new Upcoming_Events_Lists();
	$events->event_post_type();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'upcoming_events_lists_activation_deactivation' );