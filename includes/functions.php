<?php
/**
 * Required functions.
 *
 * @package RCP_IDPay
 * @since 1.0
 */

/**
 * Call the gateway endpoints.
 *
 * Try to get response from the gateway for 4 times.
 *
 * @param string $url
 * @param array $args
 * @return array|WP_Error
 */
function rcp_idpay_call_gateway_endpoint($url, $args)
{
	$tries = 4;

	while ($tries) {
		$response = wp_safe_remote_post($url, $args);
		if (is_wp_error($response)) {
			$tries--;
			continue;
		} else {
			break;
		}
	}

	return $response;
}

/**
 * Check the payment ID in the system.
 *
 * @param string $id
 * @return void
 */
function rcp_idpay_check_verification($id)
{

	global $wpdb;

	if (!function_exists('rcp_get_payment_meta_db_name')) {
		return;
	}

	$table = rcp_get_payment_meta_db_name();
	$check = $wpdb->get_row("SELECT * FROM {$table} WHERE meta_key='_verification_params' AND meta_value='idpay-{$id}'");
	if (!empty($check)) {
		wp_die(__('Duplicate payment record', 'idpay-for-rcp'));
	}
}

/**
 * Set the payment ID for later verifications.
 *
 * @param int $payment_id
 * @param string $param
 * @return void
 */
function rcp_idpay_set_verification($payment_id, $trans_id)
{
	global $wpdb;

	if (!function_exists('rcp_get_payment_meta_db_name')) {
		return;
	}

	$table = rcp_get_payment_meta_db_name();

	$wpdb->insert(
		$table,
		array(
			'payment_id' => $payment_id,
			'meta_key' => '_verification_params',
			'meta_value' => "idpay-{$trans_id}",
		),
		array('%d', '%s', '%s')
	);
}

/**
 * Return the error code message.
 *
 * @param int $code
 * @return string
 */
function rcp_idpay_fault_string($code)
{
	switch ($code) {
		case 1:
			return __('Payment has not been made.', 'idpay-for-rcp');

		case 2:
			return __('Payment has been unsuccessful.', 'idpay-for-rcp');

		case 3:
			return __('An error occurred.', 'idpay-for-rcp');

		case 4:
			return __('Payment has been blocked.', 'idpay-for-rcp');

		case 5:
			return __('Returned to the payer.', 'idpay-for-rcp');

		case 6:
			return __('System returned.', 'idpay-for-rcp');

		case 7:
			return __('User cancelled the payment.', 'idpay-for-rcp');

		case 8:
			return __('Redirected to bank.', 'idpay-for-rcp');

		case 10:
			return __('Pending verification.', 'idpay-for-rcp');

		case 100:
			return __('Payment has been verified.', 'idpay-for-rcp');

		case 101:
			return __('Payment has already been verified.', 'idpay-for-rcp');

		case 200:
			return __('To the payee was deposited.', 'idpay-for-rcp');

		default:
			return __('The code has not been defined.', 'idpay-for-rcp');
	}
}
