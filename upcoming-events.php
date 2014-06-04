<?php
/**
 * Plugin Name: Upcoming Events Lists
 * Plugin URI: http://wordpress.org/plugins/upcoming-events-lists
 * Description: A plugin to show a list of upcoming events on the front-end.
 * Version: 1.0
 * Author: Sayful Islam
 * Author URI: http://sayful.net
 * License: GPL2
 */

/**
 * Defining constants for later use
 */
define( 'ROOT', plugins_url( '', __FILE__ ) );
define( 'IMAGES', ROOT . '/img/' );
define( 'STYLES', ROOT . '/css/' );
define( 'SCRIPTS', ROOT . '/js/' );


/**
 * Registering custom post type for events
 */
function sis_custom_post_type() {
	$labels = array(
		'name'					=>	__( 'Events', 'sis' ),
		'singular_name'			=>	__( 'Event', 'sis' ),
		'add_new_item'			=>	__( 'Add New Event', 'sis' ),
		'all_items'				=>	__( 'All Events', 'sis' ),
		'edit_item'				=>	__( 'Edit Event', 'sis' ),
		'new_item'				=>	__( 'New Event', 'sis' ),
		'view_item'				=>	__( 'View Event', 'sis' ),
		'not_found'				=>	__( 'No Events Found', 'sis' ),
		'not_found_in_trash'	=>	__( 'No Events Found in Trash', 'sis' )
	);

	$supports = array(
		'title',
		'editor',
		'excerpt'
	);

	$args = array(
		'label'			=>	__( 'Events', 'sis' ),
		'labels'		=>	$labels,
		'description'	=>	__( 'A list of upcoming events', 'sis' ),
		'public'		=>	true,
		'show_in_menu'	=>	true,
		'menu_icon'		=>	IMAGES . 'event.svg',
		'has_archive'	=>	true,
		'rewrite'		=>	true,
		'supports'		=>	$supports
	);

	register_post_type( 'event', $args );
}
add_action( 'init', 'sis_custom_post_type' );


/**
 * Flushing rewrite rules on plugin activation/deactivation
 * for better working of permalink structure
 */
function sis_activation_deactivation() {
	sis_custom_post_type();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'sis_activation_deactivation' );


/**
 * Adding metabox for event information
 */
function sis_add_event_info_metabox() {
	add_meta_box(
		'sis-event-info-metabox',
		__( 'Event Info', 'sis' ),
		'sis_render_event_info_metabox',
		'event',
		'side',
		'core'
	);
}
add_action( 'add_meta_boxes', 'sis_add_event_info_metabox' );


/**
 * Rendering the metabox for event information
 * @param  object $post The post object
 */
function sis_render_event_info_metabox( $post ) {
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
		<label for="sis-event-start-date"><?php _e( 'Event Start Date:', 'sis' ); ?></label>
		<input type="text" id="sis-event-start-date" name="sis-event-start-date" class="widefat sis-event-date-input" value="<?php echo date( 'F d, Y', $event_start_date ); ?>" placeholder="Format: February 18, 2014">
	</p>
	<p>
		<label for="sis-event-end-date"><?php _e( 'Event End Date:', 'sis' ); ?></label>
		<input type="text" id="sis-event-end-date" name="sis-event-end-date" class="widefat sis-event-date-input" value="<?php echo date( 'F d, Y', $event_end_date ); ?>" placeholder="Format: February 18, 2014">
	</p>
	<p>
		<label for="sis-event-venue"><?php _e( 'Event Venue:', 'sis' ); ?></label>
		<input type="text" id="sis-event-venue" name="sis-event-venue" class="widefat" value="<?php echo $event_venue; ?>" placeholder="eg. Times Square">
	</p>
	<?php
}


/**
 * Enqueueing scripts and styles in the admin
 * @param  int $hook Current page hook
 */
function sis_admin_script_style( $hook ) {
	global $post_type;

	if ( ( 'post.php' == $hook || 'post-new.php' == $hook ) && ( 'event' == $post_type ) ) {
		wp_enqueue_script(
			'upcoming-events',
			SCRIPTS . 'script.js',
			array( 'jquery', 'jquery-ui-datepicker' ),
			'1.0',
			true
		);

		wp_enqueue_style(
			'jquery-ui-calendar',
			STYLES . 'jquery-ui-1.10.4.custom.min.css',
			false,
			'1.10.4',
			'all'
		);
	}
}
add_action( 'admin_enqueue_scripts', 'sis_admin_script_style' );


/**
 * Enqueueing styles for the front-end widget
 */
function sis_widget_style() {
	if ( is_active_widget( '', '', 'sis_upcoming_events', true ) ) {
		wp_enqueue_style(
			'upcoming-events',
			STYLES . 'upcoming-events.css',
			false,
			'1.0',
			'all'
		);
	}
}
add_action( 'wp_enqueue_scripts', 'sis_widget_style' );


/**
 * Saving the event along with its meta values
 * @param  int $post_id The id of the current post
 */
function sis_save_event_info( $post_id ) {
	//checking if the post being saved is an 'event',
	//if not, then return
	if ( 'event' != $_POST['post_type'] ) {
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
add_action( 'save_post', 'sis_save_event_info' );


/**
 * Custom columns head
 * @param  array $defaults The default columns in the post admin
 */
function sis_custom_columns_head( $defaults ) {
	unset( $defaults['date'] );

	$defaults['event_start_date'] = __( 'Start Date', 'sis' );
	$defaults['event_end_date'] = __( 'End Date', 'sis' );
	$defaults['event_venue'] = __( 'Venue', 'sis' );

	return $defaults;
}
add_filter( 'manage_edit-event_columns', 'sis_custom_columns_head', 10 );

/**
 * Custom columns content
 * @param  string 	$column_name The name of the current column
 * @param  int 		$post_id     The id of the current post
 */
function sis_custom_columns_content( $column_name, $post_id ) {
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
add_action( 'manage_event_posts_custom_column', 'sis_custom_columns_content', 10, 2 );


/**
 * Including the widget
 */
include( 'inc/widget-upcoming-events.php' );
