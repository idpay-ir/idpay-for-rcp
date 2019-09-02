<?php
/**
 * Filters.
 *
 * @package RCP_IDPay
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register IDPay payment gateway.
 *
 * @param array $gateways
 * @return array
 */
function rcp_idpay_register_gateway( $gateways ) {

	$gateways['idpay']	= [
		'label'			=> __( 'IDPay Secure Gateway', 'idpay-for-rcp' ),
		'admin_label'	=> __( 'IDPay Secure Gateway', 'idpay-for-rcp' ),
	];

	return $gateways;

}

add_filter( 'rcp_payment_gateways', 'rcp_idpay_register_gateway' );

/**
 * Add IRR and IRT currencies to RCP.
 *
 * @param array $currencies
 * @return array
 */
function rcp_idpay_currencies( $currencies ) {
	unset( $currencies['RIAL'], $currencies['IRR'], $currencies['IRT'] );

	return array_merge( array(
		'IRT'		=> __( 'تومان ایران', 'idpay-for-rcp' ),
		'IRR'		=> __( 'ریال ایران', 'idpay-for-rcp' ),
	), $currencies );
}

/**
 * Format IRR currency displaying.
 *
 * @param string $formatted_price
 * @param string $currency_code
 * @param int $price
 * @return string
 */
function rcp_idpay_irr_before( $formatted_price, $currency_code = null, $price = null ) {
	return __( 'ریال', 'idpay-for-rcp' ) . ' ' . ( $price ? $price : $formatted_price );
}

add_filter( 'rcp_irr_currency_filter_before', 'rcp_idpay_irr_before' );

/**
 * Format IRR currency displaying.
 *
 * @param string $formatted_price
 * @param string $currency_code
 * @param int $price
 * @return string
 */
function rcp_idpay_irr_after( $formatted_price, $currency_code = null, $price = null ) {
	return ( $price ? $price : $formatted_price ) . ' ' . __( 'ریال', 'idpay-for-rcp' );
}

add_filter( 'rcp_irr_currency_filter_after', 'rcp_idpay_irr_after' );

/**
 * Format IRT currency displaying.
 *
 * @param string $formatted_price
 * @param string $currency_code
 * @param int $price
 * @return string
 */
function rcp_idpay_irt_after( $formatted_price, $currency_code = null, $price = null ) {
	return ( $price ? $price : $formatted_price ) . ' ' . __( 'تومان', 'idpay-for-rcp' );
}

add_filter( 'rcp_irt_currency_filter_after', 'rcp_idpay_irt_after' );

/**
 * Format IRT currency displaying.
 *
 * @param string $formatted_price
 * @param string $currency_code
 * @param int $price
 * @return string
 */
function rcp_idpay_irt_before( $formatted_price, $currency_code = null, $price = null ) {
	return __( 'تومان', 'idpay-for-rcp' ) . ' ' . ( $price ? $price : $formatted_price );
}

add_filter( 'rcp_irt_currency_filter_before', 'rcp_idpay_irt_before' );

/**
 * Save old roles of a user when updating it.
 *
 * @param WP_User $user
 * @return WP_User
 */
function rcp_idpay_registration_data( $user ) {
	$old_subscription_id = get_user_meta( $user['id'], 'rcp_subscription_level', true );
	if ( ! empty( $old_subscription_id ) ) {
		update_user_meta( $user['id'], 'rcp_subscription_level_old', $old_subscription_id );
	}

	$user_info     = get_userdata( $user['id'] );
	$old_user_role = implode( ', ', $user_info->roles );
	if ( ! empty( $old_user_role ) ) {
		update_user_meta( $user['id'], 'rcp_user_role_old', $old_user_role );
	}

	return $user;
}

add_filter( 'rcp_user_registration_data', 'rcp_idpay_registration_data' );
