<?php function unaslms_options_page_html() {
	// check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
?>
<div class="wrap">
	<h1>
		<?php echo esc_html( get_admin_page_title() ); ?>
	</h1>
	<form action="options.php" method="post">
		<?php
			// output security fields for the registered setting "unaslms_options"
			settings_fields( 'unaslms_options' );
			// output setting sections and their fields
			// (sections are registered for "unaslms", each field is registered to a specific section)
			do_settings_sections( 'unaslms' );
			// output save settings button
			submit_button( __( 'Save Settings', 'textdomain' ) );
			?>
	</form>
</div>
<?php
}

function unaslms_options_page_html_submit() {
}

function unaslms_options_page() {
	$hookname = add_submenu_page(
		'tools.php',
		'Unas LMS Options',
		'Unas LMS Options',
		'manage_options',
		'unaslms',
		'unaslms_options_page_html'
	);

	add_action( 'load-' . $hookname, 'unaslms_options_page_html_submit' );
}

add_action('admin_menu', 'unaslms_options_page');

