<?php
/**
 * Filters.
 *
 * @package RCP_IDPay
 * @since 1.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Register IDPay payment gateway.
 *
 * @param array $gateways
 * @return array
 */
function rcp_idpay_register_gateway($gateways)
{

	$gateways['idpay'] = [
		'label' => __('IDPay Secure Gateway', 'idpay-for-rcp'),
		'admin_label' => __('IDPay Secure Gateway', 'idpay-for-rcp'),
		'class' => 'RCP_Payment_Gateway_IDPay',
	];

	return $gateways;

}

add_filter('rcp_payment_gateways', 'rcp_idpay_register_gateway');

/**
 * Add IRR and IRT currencies to RCP.
 *
 * @param array $currencies
 * @return array
 */
function rcp_idpay_currencies($currencies)
{
	unset($currencies['RIAL'], $currencies['IRR'], $currencies['IRT']);

	return array_merge(array(
		'IRT' => __('تومان ایران (تومان)', 'idpay-for-rcp'),
		'IRR' => __('ریال ایران (&#65020;)', 'idpay-for-rcp'),
	), $currencies);
}

add_filter('rcp_currencies', 'rcp_idpay_currencies');

/**
 * Save old roles of a user when updating it.
 *
 * @param WP_User $user
 * @return WP_User
 */
function rcp_idpay_registration_data($user)
{
	$old_subscription_id = get_user_meta($user['id'], 'rcp_subscription_level', true);
	if (!empty($old_subscription_id)) {
		update_user_meta($user['id'], 'rcp_subscription_level_old', $old_subscription_id);
	}

	$user_info = get_userdata($user['id']);
	$old_user_role = implode(', ', $user_info->roles);
	if (!empty($old_user_role)) {
		update_user_meta($user['id'], 'rcp_user_role_old', $old_user_role);
	}

	return $user;
}

add_filter('rcp_user_registration_data', 'rcp_idpay_registration_data');

/**
 * Sets decimal to zero.
 *
 * @param bool $is_zero_decimal_currency
 *
 * @return bool
 */
function rcp_idpay_is_zero_decimal_currency($is_zero_decimal_currency = FALSE)
{
	$currency = rcp_get_currency();
	if (in_array($currency, ['IRT', 'IRR'])) {
		return TRUE;
	}

	return $is_zero_decimal_currency;
}

add_filter('rcp_is_zero_decimal_currency', 'rcp_idpay_is_zero_decimal_currency');

/**
 * Format IRT currency Symbol.
 *
 * @return string
 */
function rcp_idpay_irr_symbol()
{
	global $rcp_options;
	return empty($rcp_options['idpay_symbol']) || $rcp_options['idpay_symbol'] == 'yes' ? ' &#65020; ' : '';
}

add_filter('rcp_irr_symbol', 'rcp_idpay_irr_symbol');

/**
 * Format IRT currency Symbol.
 *
 * @return string
 */
function rcp_idpay_irt_symbol()
{
	global $rcp_options;
	return empty($rcp_options['idpay_symbol']) || $rcp_options['idpay_symbol'] == 'yes' ? ' تومان ' : '';
}

add_filter('rcp_irt_symbol', 'rcp_idpay_irt_symbol');
