<?php


function wp_schedule_events( $timestamp, $recurrence, $hook, $args = array(), $wp_error = false ) {
    // Make sure timestamp is a positive integer.
	// die(var_dump('sdiufgsf'));
    if ( ! is_numeric( $timestamp ) || $timestamp <= 0 ) {
        if ( $wp_error ) {
            return new WP_Error(
                'invalid_timestamp',
                __( 'Event timestamp must be a valid Unix timestamp.' )
            );
        }
 
        return false;
    }
 
    $schedules = wp_get_schedules();
    if ( ! isset( $schedules[ $recurrence ] ) ) {
        if ( $wp_error ) {
            return new WP_Error(
                'invalid_schedule',
                __( 'Event schedule does not exist.' )
            );
        }
 
        return false;
    }
    $event = (object) array(
        'hook'      => $hook,
        'timestamp' => $timestamp,
        'schedule'  => $recurrence,
        'args'      => $args,
        'interval'  => $schedules[ $recurrence ]['interval'],
    );

	add_action ('mycronjob', 'my_repeat_function'); 
    /** This filter is documented in wp-includes/cron.php */
    $pre = apply_filters( 'pre_schedule_event', null, $event, $wp_error );
	
    if ( null !== $pre ) {
        if ( $wp_error && false === $pre ) {
            return new WP_Error(
                'pre_schedule_event_false',
                __( 'A plugin prevented the event from being scheduled.' )
            );
        }
 
        if ( ! $wp_error && is_wp_error( $pre ) ) {
            return false;
        }
 
        return $pre;
    }
 
    /** This filter is documented in wp-includes/cron.php */
    $event = apply_filters( 'schedule_event', $event );

    // A plugin disallowed this event.
    if ( ! $event ) {
        if ( $wp_error ) {
            return new WP_Error(
                'schedule_event_false',
                __( 'A plugin disallowed this event.' )
            );
        }
 
        return false;
    }
 
    $key = md5( serialize( $event->args ) );
 
    $crons = _get_cron_array();
    $crons[ $event->timestamp ][ $event->hook ][ $key ] = array(
        'schedule' => $event->schedule,
        'args'     => $event->args,
        'interval' => $event->interval,
    );
    uksort( $crons, 'strnatcasecmp' );
 
    return _set_cron_array( $crons, $wp_error );
}
function my_activation() {
    if ( !wp_next_scheduled( 'testing_again_two' ) ) {
        wp_schedule_events(time(), 'every_minute', 'testing_again_two');
    }
}
function add_wp_action( $hook, $method )
{
    die(var_dump($method));
    add_action( $hook, array( $this, $method ) ); 
}

function test()
{
	add_action('wp', 'my_activation');
	$res = my_activation();
     $this->add_wp_action('testing_again_two' , 'do_this_hourly' );
    // add_action('testing_again_two', 'do_this_hourly');
}

function do_this_hourly() {
	$recepients = 'mrusamariaz1@gmail.com';
	$subject = 'Hello from your Cron Job';
	$message = 'This is a test mail sent by WordPress automatically as per your schedule.';
	
	// let's send it 
	mail($recepients, $subject, $message);
    return $message;

}