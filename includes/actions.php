<?php
/**
 * Actions.
 *
 * @package RCP_IDPay
 * @since 1.0
 */

/**
 * Creates a payment record remotely and redirects
 * the user to the proper page.
 *
 * @param array|object $subscription_data
 * @return void
 */
function rcp_idpay_create_payment( $subscription_data ) {

	global $rcp_options;

	$new_subscription_id = get_user_meta( $subscription_data['user_id'], 'rcp_subscription_level', true );
	if ( ! empty( $new_subscription_id ) ) {
		update_user_meta( $subscription_data['user_id'], 'rcp_subscription_level_new', $new_subscription_id );
	}

	$old_subscription_id = get_user_meta( $subscription_data['user_id'], 'rcp_subscription_level_old', true );
	update_user_meta( $subscription_data['user_id'], 'rcp_subscription_level', $old_subscription_id );

	// Start the output buffering.
	ob_start();

	$amount = str_replace( ',', '', $subscription_data['price'] );

	// Check if the currency is in Toman.
	if ( in_array( $rcp_options['currency'], array(
		'irt',
		'IRT',
		'تومان',
		__( 'تومان', 'rcp' ),
		__( 'تومان', 'idpay-for-rcp' )
	) ) ) {
		$amount = $amount * 10;
	}

	// Send the request to IDPay.
	$api_key = isset( $rcp_options['idpay_api_key'] ) ? $rcp_options['idpay_api_key'] : wp_die( __( 'IDPay API key is missing' ) );
	$sandbox = ( isset( $rcp_options['idpay_sandbox'] ) && $rcp_options['idpay_sandbox'] == 'yes' ) ? true : false;
	$callback = add_query_arg( 'gateway', 'idpay-for-rcp', $subscription_data['return_url'] );

	$data = array(
		'order_id'			=> $subscription_data['payment_id'],
		'amount'			=> intval( $amount ),
		'name'				=> $subscription_data['user_name'],
		'phone'				=> '',
		'mail'				=> $subscription_data['user_email'],
		'desc'				=> "{$subscription_data['subscription_name']} - {$subscription_data['key']}",
		'callback'			=> $callback,
	);

	$headers = array(
		'Content-Type'		=> 'application/json',
		'X-API-KEY'			=> $api_key,
		'X-SANDBOX'			=> $sandbox,
	);

	$args = array(
		'body'				=> json_encode( $data ),
		'headers'			=> $headers,
		'timeout'			=> 15,
	);

	$response = rcp_idpay_call_gateway_endpoint( 'https://api.idpay.ir/v1.1/payment', $args );
	if ( is_wp_error( $response ) ) {
		wp_die( sprintf( __( 'Unfortunately, the payment couldn\'t be processed due to the following reason: %s' ), $response->get_error_message() ) );
	}

	$http_status	= wp_remote_retrieve_response_code( $response );
	$result			= wp_remote_retrieve_body( $response );
	$result			= json_decode( $result );

	if ( 201 !== $http_status || empty( $result ) || empty( $result->link ) ) {
		wp_die( sprintf( __( 'Unfortunately, the payment couldn\'t be processed due to the following reason: %s' ), $result->error_message ) );
	}

	// Update transaction id into payment
	$rcp_payments = new RCP_Payments();
	$rcp_payments->update( $subscription_data['payment_id'], array( 'transaction_id' => $result->id ) );

	ob_end_clean();
	if ( headers_sent() ) {
		echo '<script type="text/javascript">window.onload = function() { top.location.href = "' . $result->link . '"; };</script>';
	} else {
		wp_redirect( $result->link );
	}

	exit;
}

add_action( 'rcp_gateway_idpay', 'rcp_idpay_create_payment' );

/**
 * Verify the payment when returning from the IPG.
 *
 * @return void
 */
function rcp_idpay_verify() {

	if ( ! isset( $_GET['gateway'] ) )
		return;

	if ( ! class_exists( 'RCP_Payments' ) )
		return;

	if ( ! isset( $_POST['order_id'] ) )
		return;

	global $rcp_options, $wpdb, $rcp_payments_db_name;

	if ( 'idpay-for-rcp' !== sanitize_text_field( $_GET['gateway'] ) )
		return;

	$rcp_payments = new RCP_Payments();
	$payment_data = $rcp_payments->get_payment($_POST['order_id']);

	if ( empty( $payment_data ) )
		return;

	extract( (array) $payment_data );
	$user_id = intval( $user_id );
	$subscription_name = $subscription;

	if ( $payment_data->status == 'pending'
		&& $payment_data->gateway == 'idpay'
		&& $payment_data->amount == $_POST['amount']
		&& $payment_data->transaction_id == $_POST['id'] ) {

		$api_key = isset( $rcp_options['idpay_api_key'] ) ? $rcp_options['idpay_api_key'] : wp_die( __( 'IDPay API key is missing' ) );
		$sandbox = ( isset( $rcp_options['idpay_sandbox'] ) && $rcp_options['idpay_sandbox'] == 'yes' ) ? true : false;

		$status         = sanitize_text_field( $_POST['status'] );
		$track_id       = sanitize_text_field( $_POST['track_id'] );
		$id             = sanitize_text_field( $_POST['id'] );
		$order_id       = sanitize_text_field( $_POST['order_id'] );
		$amount         = sanitize_text_field( $_POST['amount'] );
		$card_no        = sanitize_text_field( $_POST['card_no'] );
		$hashed_card_no = sanitize_text_field( $_POST['hashed_card_no'] );
		$date           = sanitize_text_field( $_POST['date'] );

		if ( $status != 10 ) {
			$status = 'failed';
		}
		else {

			rcp_idpay_check_verification( $id );

			$data = array(
				'id'		=> $id,
				'order_id'	=> $order_id,
			);

			$headers = array(
				'Content-Type'		=> 'application/json',
				'X-API-KEY'			=> $api_key,
				'X-SANDBOX'			=> $sandbox,
			);

			$args = array(
				'body'				=> json_encode( $data ),
				'headers'			=> $headers,
				'timeout'			=> 15,
			);

			$response = rcp_idpay_call_gateway_endpoint( 'https://api.idpay.ir/v1.1/payment/verify', $args );
			if ( is_wp_error( $response ) ) {
				wp_die( sprintf( __( 'Unfortunately, the payment couldn\'t be processed due to the following reason: %s' ), $response->get_error_message() ) );
			}

			$http_status	= wp_remote_retrieve_response_code( $response );
			$result			= wp_remote_retrieve_body( $response );
			$result			= json_decode( $result );


			$status = '';
			$fault = '';

			if ( 200 !== $http_status ) {
				$status = 'failed';
				$fault = $result->error_message;
			}
			else {
				if ( $result->status >= 100 ) {
					$status = 'complete';
				} else {
					$status = 'failed';
					$fault = rcp_idpay_fault_string( $result->status );
				}
			}
		}

		// Let RCP plugin acknowledge the payment.
		if ( 'complete' === $status ) {

			$payment_data = array(
				'date'				=> date( 'Y-m-d g:i:s' ),
				'subscription'		=> $subscription_name,
				'payment_type'		=> $payment_method,
				'subscription_key'	=> $subscription_key,
				'amount'			=> $amount,
				'user_id'			=> $user_id,
				'transaction_id'	=> $id,
			);

			$rcp_payments = new RCP_Payments();
			$payment_id = $rcp_payments->insert( $payment_data );
			$rcp_payments->update( $order_id, array( 'status' => $status ) );
			rcp_idpay_set_verification( $payment_id, $id );

			$new_subscription_id = get_user_meta( $user_id, 'rcp_subscription_level_new', true );
			if ( ! empty( $new_subscription_id ) ) {
				update_user_meta( $user_id, 'rcp_subscription_level', $new_subscription_id );
			}

			rcp_set_status( $user_id, 'active' );

			if ( version_compare( RCP_PLUGIN_VERSION, '2.1.0', '<' ) ) {
				rcp_email_subscription_status( $user_id, 'active' );
				if ( ! isset( $rcp_options['disable_new_user_notices'] ) ) {
					wp_new_user_notification( $user_id );
				}
			}

			update_user_meta( $user_id, 'rcp_payment_profile_id', $user_id );

			update_user_meta( $user_id, 'rcp_signup_method', 'live' );
			update_user_meta( $user_id, 'rcp_recurring', 'no' );

			$subscription          = rcp_get_subscription_details( rcp_get_subscription_id( $user_id ) );
			$member_new_expiration = date( 'Y-m-d H:i:s', strtotime( '+' . $subscription->duration . ' ' . $subscription->duration_unit . ' 23:59:59' ) );
			rcp_set_expiration_date( $user_id, $member_new_expiration );
			delete_user_meta( $user_id, '_rcp_expired_email_sent' );

			$log_data = array(
				'post_title'   => __( 'Payment complete', 'idpay-for-rcp' ),
				'post_content' => __( 'Transaction ID: ', 'idpay-for-rcp' ) . $id . __( ' / Payment method: ', 'idpay-for-rcp' ) . $payment_method,
				'post_parent'  => 0,
				'log_type'     => 'gateway_error'
			);

			$log_meta = array(
				'user_subscription' => $subscription_name,
				'user_id'           => $user_id
			);

			WP_Logging::insert_log( $log_data, $log_meta );
		}

		if ( 'failed' === $status ) {

			$rcp_payments = new RCP_Payments();
			$rcp_payments->update( $order_id, array( 'status' => $status ) );

			$log_data = array(
				'post_title'   => __( 'Payment failed', 'idpay-for-rcp' ),
				'post_content' => __( 'Transaction did not succeed due to following reason:', 'rcp_zaringate' ) . $fault . __( ' / Payment method: ', 'idpay-for-rcp' ) . $payment_method,
				'post_parent'  => 0,
				'log_type'     => 'gateway_error'
			);

			$log_meta = array(
				'user_subscription' => $subscription_name,
				'user_id'           => $user_id
			);

			WP_Logging::insert_log( $log_data, $log_meta );
		}

		add_filter( 'the_content', function( $content ) use( $status, $track_id, $fault ) {
			$message = '';

			if ( $status == 'complete' ) {
				$message = '<br><center>' . __( 'Payment was successful. Transaction tracking number is: ', 'idpay-for-rcp' ) . $track_id . '</center>';
			}
			if ( $status == 'failed' ) {
				$message = '<br><center>' . __( 'Payment failed due to the following reason: ', 'idpay-for-rcp' ) . $fault . '<br>' . __( 'Your transaction tracking number is: ', 'idpay-for-rcp' ) . $track_id . '</center>';
			}

			return $content . $message;
		} );
	}
}

add_action( 'init', 'rcp_idpay_verify' );

/**
 * Change a user status to expired instead of cancelled.
 *
 * @param string $status
 * @param int $user_id
 * @return boolean
 */
function rcp_idpay_change_cancelled_to_expired( $status, $user_id ) {
	if ( 'cancelled' == $status ) {
		rcp_set_status( $user_id, 'expired' );
	}

	return true;
}

add_action( 'rcp_set_status', 'rcp_idpay_change_cancelled_to_expired', 10, 2 );
