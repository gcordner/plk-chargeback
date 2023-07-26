<?php
/**
 * Plugin Name: PLK Chargeback Checker
 * Plugin URI: https://paylesskratom.com/
 * Description: This plugin checks to see if the customer should be flagged for chargebacks.
 * Version: 1.0
 * Author: Geoff Cordner
 * Author URI: https://geoffcordner.net/
 *
 * @package PLK_Chargeback_Checker
 */

// Initialize the plugin and add the admin page.
add_action( 'admin_menu', 'chargeback_blocker_add_admin_page' );

/**
 * Adds the Chargeback Blocker admin page to the WordPress dashboard menu.
 *
 * This function is hooked to the 'admin_menu' action, which allows us to add the plugin's admin page to the dashboard menu.
 * The admin page is accessible to users with the 'manage_options' capability.
 *
 * @since 1.0
 */
function chargeback_blocker_add_admin_page() {
	add_menu_page(
		'Chargeback Blocker',
		'Chargeback Blocker',
		'manage_options',
		'chargeback_blocker',
		'chargeback_blocker_admin_page'
	);
}

/**
 * Display the Chargeback Blocker admin page.
 *
 * This function outputs the HTML and form for the Chargeback Blocker admin page in the WordPress dashboard.
 * It handles form submissions for saving, updating, and deleting block list entries.
 *
 * @since 1.0
 */
function chargeback_blocker_admin_page() {
	// Save the block list entries if the form is submitted.
	if ( isset( $_POST['submit'] ) && isset( $_POST['chargeback_blocker_save_nonce'] ) && wp_verify_nonce( $_POST['chargeback_blocker_save_nonce'], 'chargeback_blocker_save_nonce' ) ) {
		chargeback_blocker_save_entries();
	}

	// Update "disable" field for selected entries if the form is submitted.
	if ( isset( $_POST['update'] ) && isset( $_POST['chargeback_blocker_save_nonce'] ) && wp_verify_nonce( $_POST['chargeback_blocker_save_nonce'], 'chargeback_blocker_save_nonce' ) ) {
		chargeback_blocker_update_entries();
	}

	// Delete selected entries if the form is submitted.
	if ( isset( $_POST['delete'] ) && isset( $_POST['chargeback_blocker_delete_nonce'] ) && wp_verify_nonce( $_POST['chargeback_blocker_delete_nonce'], 'chargeback_blocker_delete_nonce' ) ) {
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

			<!-- Status dropdown field -->
			<label for="status">Status:</label>
			<select name="status" id="status">
				<option value="Paid">Paid</option>
				<option value="Collection - FCR">Collection - FCR</option>
			</select>
			<br>

			<!-- Buttons to submit the form -->
			<input type="submit" name="submit" value="Save Entry">
		</form>

		<!-- Display existing block list entries -->
		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=chargeback_blocker' ) ); ?>">
			<?php wp_nonce_field( 'chargeback_blocker_delete_nonce', 'chargeback_blocker_delete_nonce' ); ?>

			<!-- Make the table sortable and searchable -->
			<?php
			$sortable_columns = array(
				'first_name'     => 'First Name',
				'last_name'      => 'Last Name',
				'street_address' => 'Street Address',
				'email'          => 'Email Address',
				'phone'          => 'Phone Number',
				'status'         => 'Status',
				'disable'        => 'Disable', // Add the "disable" field to sortable columns.
			);

			// Get the current ordering.
			$orderby = isset( $_GET['orderby'] ) && array_key_exists( $_GET['orderby'], $sortable_columns ) ? $_GET['orderby'] : 'first_name';

			// Get the current order direction.
			$order = isset( $_GET['order'] ) && in_array( strtoupper( $_GET['order'] ), array( 'ASC', 'DESC' ) ) ? strtoupper( $_GET['order'] ) : 'ASC';

			// Sort the block list entries.
			$sorted_block_list = chargeback_blocker_sort_entries( $block_list, $orderby, $order );
			?>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Delete</th>
						<?php foreach ( $sortable_columns as $column_key => $column_label ) : ?>
							<th>
								<a href="
								<?php
								echo esc_url(
									add_query_arg(
										array(
											'orderby' => $column_key,
											'order'   => 'ASC' === $order ? 'DESC' : 'ASC',
										)
									)
								);
								?>
											">
									<?php echo esc_html( $column_label ); ?>
								</a>
							</th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $sorted_block_list as $index => $entry ) : ?>
						<tr>
							<td><input type="checkbox" name="delete_entry[]" value="<?php echo $index; ?>"></td>
							<td><?php echo esc_html( $entry['first_name'] ); ?></td>
							<td><?php echo esc_html( $entry['last_name'] ); ?></td>
							<td><?php echo esc_html( $entry['street_address'] ); ?></td>
							<td><?php echo esc_html( $entry['email'] ); ?></td>
							<td><?php echo esc_html( $entry['phone'] ); ?></td>
							<td><?php echo esc_html( $entry['status'] ); ?></td>
							<td>
								<!-- Add the "disable" checkbox -->
								<input type="checkbox" name="disable_entry[<?php echo $index; ?>]" value="yes" <?php checked( $entry['disable'], 'yes' ); ?>>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<br>
			<!-- Buttons to delete selected entries and update "disable" field -->
			<input type="submit" name="delete" value="Delete Selected Entries">
			<input type="submit" name="update" value="Update">
		</form>
	</div>
	<?php
}


/**
 * Process the update of the "disable" field for block list entries.
 *
 * This function handles the form submission when the "Update" button is clicked to update the "disable" field
 * for selected block list entries. It fetches the existing block list entries, updates the "disable" field
 * based on the submitted data, and saves the updated block list to the database.
 *
 * @since 1.0
 */
function chargeback_blocker_update_entries() {
	// Check if the "Update" button is clicked and the "disable_entry" data is present.
	if ( isset( $_POST['update'] ) && isset( $_POST['disable_entry'] ) ) {
		// Fetch existing block list entries.
		$block_list = get_option( 'chargeback_block_list', array() );

		// Get the updated disable entries.
		$disable_entries = $_POST['disable_entry'];

		// Update the "disable" field for each entry.
		foreach ( $block_list as $index => $entry ) {
			$block_list[ $index ]['disable'] = isset( $disable_entries[ $index ] ) ? 'yes' : 'no';
		}

		// Save the updated block list to the database.
		update_option( 'chargeback_block_list', $block_list );

		// Redirect the admin user back to the admin page after updating.
		wp_safe_redirect( admin_url( 'admin.php?page=chargeback_blocker' ) );
		exit;
	}
}



// Process the update action.
add_action( 'admin_init', 'chargeback_blocker_update_entries' );

/**
 * Delete selected entries from the database.
 */
function chargeback_blocker_delete_entries() {
	// Verify the nonce for security.
	if ( ! isset( $_POST['chargeback_blocker_delete_nonce'] ) || ! wp_verify_nonce( $_POST['chargeback_blocker_delete_nonce'], 'chargeback_blocker_delete_nonce' ) ) {
		return;
	}

	// Sanitize the $_POST['delete_entry'] data before processing.
	$entries_to_delete = isset( $_POST['delete_entry'] ) ? wp_unslash( $_POST['delete_entry'] ) : array();

	// Fetch existing block list entries.
	$block_list = get_option( 'chargeback_block_list', array() );

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

/**
 * Sort the block list entries.
 *
 * @param array  $block_list The list of block entries.
 * @param string $orderby    The column to order by.
 * @param string $order      The sorting order ('ASC' or 'DESC').
 * @return array The sorted block list entries.
 */
function chargeback_blocker_sort_entries( $block_list, $orderby, $order ) {
	usort(
		$block_list,
		function( $a, $b ) use ( $orderby, $order ) {
			$cmp = strnatcmp( $a[ $orderby ], $b[ $orderby ] );
			return ( 'ASC' === $order ) ? $cmp : -$cmp;
		}
	);

	return $block_list;
}

/**
 * Save the block list entries to the database.
 */
function chargeback_blocker_save_entries() {
	// Verify the nonce for security.
	if ( ! isset( $_POST['chargeback_blocker_save_nonce'] ) || ! wp_verify_nonce( $_POST['chargeback_blocker_save_nonce'], 'chargeback_blocker_save_nonce' ) ) {
		return;
	}

	// Retrieve the existing block list entries.
	$block_list = get_option( 'chargeback_block_list', array() );

	// Get the data from the form submission.
	$first_name     = sanitize_text_field( $_POST['first_name'] );
	$last_name      = sanitize_text_field( $_POST['last_name'] );
	$street_address = sanitize_text_field( $_POST['street_address'] );
	$email          = sanitize_email( $_POST['email'] );
	$phone          = sanitize_text_field( $_POST['phone'] );
	$status         = sanitize_text_field( $_POST['status'] );

	// Create a new block entry.
	$new_entry = array(
		'first_name'     => $first_name,
		'last_name'      => $last_name,
		'street_address' => $street_address,
		'email'          => $email,
		'phone'          => $phone,
		'status'         => $status,
		'disable'        => 'no', // Default value for the "disable" field.
	);

	// Add the new entry to the block list.
	$block_list[] = $new_entry;

	// Save the updated block list to the database.
	update_option( 'chargeback_block_list', $block_list );

	// Redirect the admin user back to the admin page after saving.
	wp_safe_redirect( admin_url( 'admin.php?page=chargeback_blocker' ) );
	exit;
}

/**
 * Check for chargebacks and update order status if needed.
 *
 * This function runs when an order payment is approved and the order status is about to change from pending to processing.
 * It checks for chargebacks in the chargeback_block_list array and updates the order status accordingly.
 *
 * @param int $order_id Order ID.
 */
function plk_chargeback_check( $order_id ) {
	// Unserialize the chargeback_block_list array.
	$chargeback_block_list = get_option( 'chargeback_block_list', array() );
	$chargeback_block_list = unserialize( $chargeback_block_list );

	// Get the order object.
	$order = wc_get_order( $order_id );

	if ( ! empty( $chargeback_block_list ) && is_array( $chargeback_block_list ) ) {
		// Get order data for comparison.
		$billing_first_name  = $order->get_billing_first_name();
		$billing_last_name   = $order->get_billing_last_name();
		$billing_address_1   = $order->get_billing_address_1();
		$billing_email       = $order->get_billing_email();
		$billing_phone       = preg_replace( '/\D/', '', $order->get_billing_phone() ); // Strip non-numeric characters.
		$shipping_first_name = $order->get_shipping_first_name();
		$shipping_last_name  = $order->get_shipping_last_name();
		$shipping_address_1  = $order->get_shipping_address_1();
		$shipping_email      = $order->get_shipping_email();
		$shipping_phone      = preg_replace( '/\D/', '', $order->get_shipping_phone() ); // Strip non-numeric characters.

		// Loop through each entry in chargeback_block_list.
		foreach ( $chargeback_block_list as $entry ) {
			// Strip non-numeric characters from entry phone number.
			$entry_phone = preg_replace( '/\D/', '', $entry['phone'] );

			// Check if billing first and last name match the entry.
			if (
				0 === strcasecmp( $billing_first_name . ' ' . $billing_last_name, $entry['first_name'] . ' ' . $entry['last_name'] )
				&& 'yes' !== $entry['disable']
			) {
				plk_update_order_status( 'on-hold', $order_id, $order );
				return; // Exit the function once the order status is updated.
			}

			// Check if billing address matches the entry.
			if ( 0 === strcasecmp( $billing_address_1, $entry['street_address'] ) && 'yes' !== $entry['disable'] ) {
				plk_update_order_status( 'on-hold', $order_id, $order );
				return;
			}

			// Check if billing email matches the entry.
			if ( 0 === strcasecmp( $billing_email, $entry['email'] ) && 'yes' !== $entry['disable'] ) {
				plk_update_order_status( 'on-hold', $order_id, $order );
				return;
			}

			// Check if billing phone matches the entry.
			if ( 0 === strcasecmp( $billing_phone, $entry_phone ) && 'yes' !== $entry['disable'] ) {
				plk_update_order_status( 'on-hold', $order_id, $order );
				return;
			}

			// Check if shipping first and last name match the entry.
			if (
				0 === strcasecmp( $shipping_first_name . ' ' . $shipping_last_name, $entry['first_name'] . ' ' . $entry['last_name'] )
				&& 'yes' !== $entry['disable']
			) {
				plk_update_order_status( 'on-hold', $order_id, $order );
				return;
			}

			// Check if shipping address matches the entry.
			if ( 0 === strcasecmp( $shipping_address_1, $entry['street_address'] ) && 'yes' !== $entry['disable'] ) {
				plk_update_order_status( 'on-hold', $order_id, $order );
				return;
			}

			// Check if shipping email matches the entry.
			if ( 0 === strcasecmp( $shipping_email, $entry['email'] ) && 'yes' !== $entry['disable'] ) {
				plk_update_order_status( 'on-hold', $order_id, $order );
				return;
			}

			// Check if shipping phone matches the entry.
			if ( 0 === strcasecmp( $shipping_phone, $entry_phone ) && 'yes' !== $entry['disable'] ) {
				plk_update_order_status( 'on-hold', $order_id, $order );
				return;
			}
		}
	}
}
add_action( 'woocommerce_order_status_pending_to_processing', 'plk_chargeback_check' );


