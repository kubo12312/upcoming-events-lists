<?php

/**
 * Class Upcoming_Events
 */
class Upcoming_Events extends WP_Widget {

	/**
	 * Initializing the widget
	 */
	public function __construct() {
		$widget_ops = array(
			'class'			=>	'sis_upcoming_events',
			'description'	=>	__( 'A widget to display a list of upcoming events', 'upcoming-events' )
		);

		parent::__construct(
			'sis_upcoming_events',			//base id
			__( 'Upcoming Events', 'upcoming-events' ),	//title
			$widget_ops
		);
	}


	/**
	 * Displaying the widget on the back-end
	 * @param  array $instance An instance of the widget
	 */
	public function form( $instance ) {
		$widget_defaults = array(
			'title'			=>	'Upcoming Events',
			'number_events'	=>	5
		);

		$instance  = wp_parse_args( (array) $instance, $widget_defaults );
		?>
		
		<!-- Rendering the widget form in the admin -->
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'upcoming-events' ); ?></label>
			<input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" class="widefat" value="<?php echo esc_attr( $instance['title'] ); ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number_events' ); ?>"><?php _e( 'Number of events to show', 'upcoming-events' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'number_events' ); ?>" name="<?php echo $this->get_field_name( 'number_events' ); ?>" class="widefat">
				<?php for ( $i = 1; $i <= 10; $i++ ): ?>
					<option value="<?php echo $i; ?>" <?php selected( $i, $instance['number_events'], true ); ?>><?php echo $i; ?></option>
				<?php endfor; ?>
			</select>
		</p>

		<?php
	}


	/**
	 * Making the widget updateable
	 * @param  array $new_instance New instance of the widget
	 * @param  array $old_instance Old instance of the widget
	 * @return array An updated instance of the widget
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = $new_instance['title'];
		$instance['number_events'] = $new_instance['number_events'];

		return $instance;
	}


	/**
	 * Displaying the widget on the front-end
	 * @param  array $args     Widget options
	 * @param  array $instance An instance of the widget
	 */
	public function widget( $args, $instance ) {

		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );

		//Preparing the query for events
		$meta_quer_args = array(
			'relation'	=>	'AND',
			array(
				'key'		=>	'event-end-date',
				'value'		=>	time(),
				'compare'	=>	'>='
			)
		);

		$query_args = array(
			'post_type'				=>	'event',
			'posts_per_page'		=>	$instance['number_events'],
			'post_status'			=>	'publish',
			'ignore_sticky_posts'	=>	true,
			'meta_key'				=>	'event-start-date',
			'orderby'				=>	'meta_value_num',
			'order'					=>	'ASC',
			'meta_query'			=>	$meta_quer_args
		);

		$upcoming_events = new WP_Query( $query_args );

		//Preparing to show the events
		echo $before_widget;
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}
		?>
		
		<ul class="sis_event_entries">
			<?php while( $upcoming_events->have_posts() ): $upcoming_events->the_post();
				$event_start_date = get_post_meta( get_the_ID(), 'event-start-date', true );
				$event_end_date = get_post_meta( get_the_ID(), 'event-end-date', true );
				$event_venue = get_post_meta( get_the_ID(), 'event-venue', true );
				$event_image= wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'full' );
			?>
				<li class="sis_event_entry">
					<div>
						<a href="<?php the_permalink(); ?>">
							<img class="event_image" src="<?php echo $event_image[0]; ?>" alt="">
						</a>
					</div>
					<h4>
						<a href="<?php the_permalink(); ?>" class="sis_event_title"><?php the_title(); ?></a>
						<span class="event_venue">at <?php echo $event_venue; ?></span>
					</h4>
					<?php the_excerpt(); ?>
					<time class="sis_event_date"><?php echo date_i18n( get_option( 'date_format' ), $event_start_date ); ?> &ndash; <?php echo date_i18n( get_option( 'date_format' ), $event_end_date ); ?></time>
				</li>
			<?php endwhile; ?>
		</ul>

		<a href="<?php echo get_post_type_archive_link( 'event' ); ?>"><?php _e( 'View All Events', 'upcoming-events' ); ?></a>

		<?php
		wp_reset_query();

		echo $after_widget;

	}
}

function upcoming_events_lists_widget() {
	register_widget( 'Upcoming_Events' );
}
add_action( 'widgets_init', 'upcoming_events_lists_widget' );