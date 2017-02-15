<?php
/**
 * Plugin Name: Joinhook
 * Plugin URI: https://github.com/emrikol/Joinhook
 * Description: Creates a webhook endpoint to connect Join (Android App) to Sonaar.
 * Version: 1.0.0
 * Author: Derrick Tennant
 * Author URI: https://emrikol.com/
 * License: GPL3
 * GitHub Plugin URI: https://github.com/emrikol/Joinhook/
 */

function joinhook_register_route() {
	register_rest_route( 'webhooks/v1', '/join', array(
		'methods' => array( 'POST' ),
		'callback' => 'joinhook_rest_api_callback',
	) );
}
add_action( 'rest_api_init', 'joinhook_register_route' );

function joinhook_rest_api_callback( WP_REST_Request $request ) {
	$options = get_option( 'joinhook_settings' );
	$icon_url = ( isset( $options['join_app_icon'] ) && ! empty( $options['join_app_icon'] ) ) ? $options['join_app_icon'] : 'https://avatars3.githubusercontent.com/u/1082903';

	if ( ! isset( $options['join_device_id'] ) || empty( $options['join_device_id'] ) ) {
		wp_send_json_error( 'Join Device or Group ID not set!' );
	}

	$device_id = sanitize_key( $options['join_device_id'] );

	if ( function_exists( 'jetpack_photon_url' ) ) {
		$icon_url = jetpack_photon_url( $icon_url );
	}

	$body = json_decode( $request->get_body() );

	$event_type = $body->EventType;
	$media_title = $body->Series->Title;
	$episodes = array();

	// Let's merge all episodes, CSV them, and clean them up
	foreach ( $body->Episodes as $episode ) {
		$episodes[] = $episode->Title . ' [S' . $episode->SeasonNumber . 'E' . $episode->EpisodeNumber . ']';
	}

	$text = implode( ', ', $episodes );
	$text = rtrim( trim( $text ), ',' );
	$title = $event_type . ': ' . $media_title;

	$join_url = add_query_arg( array(
		'title' => $title,
		'icon' => esc_url_raw( $icon_url ),
		'text' => $text,
		'deviceId' => $device_id,
	), 'https://joinjoaomgcd.appspot.com/_ah/api/messaging/v1/sendPush' );

	// Boom!
	wp_remote_get( $join_url, array( 'timeout' => 3 ) );
}


// Admin Settings
add_action( 'admin_menu', 'joinhook_add_admin_menu' );
add_action( 'admin_init', 'joinhook_settings_init' );

function joinhook_add_admin_menu() {
	add_submenu_page( 'tools.php', 'Joinhook', 'Joinhook', 'manage_options', 'joinhook', 'joinhook_options_page' );
}

function joinhook_settings_init() {
	register_setting( 'joinhook_settings_page', 'joinhook_settings' );

	add_settings_section(
		'joinhook_settings',
		esc_html__( 'Join Settings', 'joinhook' ),
		'joinhook_settings_section_callback',
		'joinhook_settings_page'
	);

	add_settings_field(
		'join_device_id',
		esc_html__( 'Join Device or Group ID', 'joinhook' ),
		'joinhook_device_id_render',
		'joinhook_settings_page',
		'joinhook_settings'
	);

	add_settings_field(
		'join_app_icon',
		esc_html__( 'URL to use as a notification icon', 'joinhook' ),
		'join_app_icon_render',
		'joinhook_settings_page',
		'joinhook_settings'
	);
}

function joinhook_device_id_render() {
	$options = get_option( 'joinhook_settings' );
	?>
	<input type='text' name='joinhook_settings[join_device_id]' value='<?php echo esc_attr( $options['join_device_id'] ); ?>'>
	<?php
}

function join_app_icon_render() {
	$options = get_option( 'joinhook_settings' );
	$icon_url = ( isset( $options['join_app_icon'] ) && ! empty( $options['join_app_icon'] ) ) ? $options['join_app_icon'] : 'https://avatars3.githubusercontent.com/u/1082903';
	?>
	<input type='text' name='joinhook_settings[join_app_icon]' value='<?php echo esc_url( $icon_url ); ?>'> (Preview: <img src="<?php echo esc_url( $icon_url ); ?>" width="16" height="16" alt="Preview" />)
	<?php
}

function joinhook_settings_section_callback() {
	echo wp_kses_post( sprintf( esc_html__( 'Settings for the Join App. More information at %s', 'joinhook' ), '<a href="https://joaoapps.com/join/api/" target="_blank">joaoapps.com</a>' ) );
}

function joinhook_options_page() {
	?>
	<form action='options.php' method='post'>

		<h2>joinhook</h2>

		<?php
		settings_fields( 'joinhook_settings_page' );
		do_settings_sections( 'joinhook_settings_page' );
		submit_button();
		?>

	</form>
	<?php
}
