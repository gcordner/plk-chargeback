<?php
/**
 * Plugin Name: PLK Chargeback Checker
 * Plugin URI: https://paylesskratom.com/
 * Description: This plugin checks to see if the customer should be flagged for chargebacks.
 * Version: 1.0
 * Author: Geoff Cordner
 * Author URI: https://geoffcordner.net/
 */


// Initialize the plugin and add the admin page.
add_action( 'admin_menu', 'chargeback_blocker_add_admin_page' );
function chargeback_blocker_add_admin_page() {
	add_menu_page(
		'Chargeback Blocker',
		'Chargeback Blocker',
		'manage_options',
		'chargeback_blocker',
		'chargeback_blocker_admin_page'
	);
}

// Display the admin page
function chargeback_blocker_admin_page() {
	// Save the block list entries if the form is submitted.
	if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['submit'] ) ) {
		chargeback_blocker_save_entries();
	}

	// Delete selected entries if the form is submitted.
	if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['delete'] ) ) {
		chargeback_blocker_delete_entries();
	}

	// Fetch existing block list entries.
	$block_list = get_option( 'chargeback_block_list', array() );

	// Add your admin page HTML and form here.
	?>
	<div class="wrap">
		<h1>Chargeback Blocker Settings</h1>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=chargeback_blocker' ) ); ?>">
			<?php wp_nonce_field( 'chargeback_blocker_save_nonce', 'chargeback_blocker_save_nonce' ); ?>

<!-- Input fields for First & Last Name, Street Address, Email Address, and Phone Number -->
	<label for="first_name">First Name:</label>
			<input type="text" name="first_name" id="first_name">
			<br>

			<label for="last_name">Last Name:</label>
			<input type="text" name="last_name" id="last_name">
			<br>

			<label for="street_address">Street Address:</label>
			<input type="text" name="street_address" id="street_address">
			<br>

			<label for="email">Email Address:</label>
			<input type="email" name="email" id="email">
			<br>

			<label for="phone">Phone Number:</label>
			<input type="text" name="phone" id="phone">
			<br>

			<!-- Buttons to submit the form and add/edit/delete entries -->
			<input type="submit" name="submit" value="Save Entry">
		</form>

		<!-- Display existing block list entries -->
		<h2>Block List Entries:</h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=chargeback_blocker' ) ); ?>">
			<?php wp_nonce_field( 'chargeback_blocker_delete_nonce', 'chargeback_blocker_delete_nonce' ); ?>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Delete</th>
						<th>First Name</th>
						<th>Last Name</th>
						<th>Street Address</th>
						<th>Email Address</th>
						<th>Phone Number</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $block_list as $index => $entry ) : ?>
						<tr>
							<td><input type="checkbox" name="delete_entry[]" value="<?php echo $index; ?>"></td>
							<td><?php echo esc_html( $entry['first_name'] ); ?></td>
							<td><?php echo esc_html( $entry['last_name'] ); ?></td>
							<td><?php echo esc_html( $entry['street_address'] ); ?></td>
							<td><?php echo esc_html( $entry['email'] ); ?></td>
							<td><?php echo esc_html( $entry['phone'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<br>
			<!-- Button to delete selected entries -->
			<input type="submit" name="delete" value="Delete Selected Entries">
		</form>
	</div>
	<?php
}

// Save the block list entries to the database
function chargeback_blocker_save_entries() {
	// Verify the nonce for security
	if ( ! isset( $_POST['chargeback_blocker_save_nonce'] ) || ! wp_verify_nonce( $_POST['chargeback_blocker_save_nonce'], 'chargeback_blocker_save_nonce' ) ) {
		return;
	}

	// Perform data validation and sanitation here.
	$first_name     = sanitize_text_field( $_POST['first_name'] );
	$last_name      = sanitize_text_field( $_POST['last_name'] );
	$street_address = sanitize_text_field( $_POST['street_address'] );
	$email          = sanitize_email( $_POST['email'] );
	$phone          = sanitize_text_field( $_POST['phone'] );

	// Fetch existing block list entries.
	$block_list = get_option( 'chargeback_block_list', array() );

	// Add the new entry to the block list.
	$block_list[] = array(
		'first_name'     => $first_name,
		'last_name'      => $last_name,
		'street_address' => $street_address,
		'email'          => $email,
		'phone'          => $phone,
	);

	// Save the updated block list to the database.
	update_option( 'chargeback_block_list', $block_list );

	// Redirect the admin user back to the admin page after saving.
	wp_safe_redirect( admin_url( 'admin.php?page=chargeback_blocker' ) );
	exit;
}

// Delete selected entries from the database.
function chargeback_blocker_delete_entries() {
	// Verify the nonce for security.
	if ( ! isset( $_POST['chargeback_blocker_delete_nonce'] ) || ! wp_verify_nonce( $_POST['chargeback_blocker_delete_nonce'], 'chargeback_blocker_delete_nonce' ) ) {
		return;
	}

	// Fetch existing block list entries.
	$block_list = get_option( 'chargeback_block_list', array() );

	// Get the selected entry indices to delete.
	$entries_to_delete = isset( $_POST['delete_entry'] ) ? $_POST['delete_entry'] : array();

	// Delete selected entries from the block list.
	foreach ( $entries_to_delete as $index ) {
		if ( isset( $block_list[ $index ] ) ) {
			unset( $block_list[ $index ] );
		}
	}

	// Reindex the block list array after deletion.
	$block_list = array_values( $block_list );

	// Save the updated block list to the database.
	update_option( 'chargeback_block_list', $block_list );

	// Redirect the admin user back to the admin page after deletion.
	wp_safe_redirect( admin_url( 'admin.php?page=chargeback_blocker' ) );
	exit;
}
