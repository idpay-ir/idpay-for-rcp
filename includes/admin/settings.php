<?php
/**
 * IDPay gateway settings.
 *
 * @package RCP_IDPay
 * @since 1.0
 */

if (!defined('ABSPATH')) exit;


function rcp_idpay_settings($rcp_options)
{

	?>
	<hr>

	<table class="form-table">
		<tr valign="top">
			<th colspan="2">
				<h3><?php _e('IDPay gateway settings', 'idpay-for-rcp'); ?></h3>
			</th>
		</tr>
		<tr valign="top">
			<th>
				<label for="rcp_settings[idpay_api_key]"
					   id="idpayApiKey"><?php _e('API Key', 'idpay-for-rcp'); ?></label>
			</th>
			<td>
				<input class="regular-text" name="rcp_settings[idpay_api_key]" id="idpayApiKey"
					   value="<?php echo isset($rcp_options['idpay_api_key']) ? $rcp_options['idpay_api_key'] : ''; ?>">
				<p class="description"><?php _e('You can create an API Key by going to your <a href="https://idpay.ir/dashboard/web-services">IDPay account</a>.', 'idpay-for-rcp'); ?></p>
			</td>
		</tr>
		<tr valign="top">
			<th>
				<label for="rcp_settings[idpay_sandbox]"
					   id="idpaySandbox"><?php _e('Sandbox mode', 'idpay-for-rcp'); ?></label>
			</th>
			<td>
				<p class="description">
					<select id="rcp_settings[idpay_sandbox]" name="rcp_settings[idpay_sandbox]">
						<option
							value="yes" <?php selected('yes', isset($rcp_options['idpay_sandbox']) ? $rcp_options['idpay_sandbox'] : ''); ?>><?php _e('Yes', 'idpay-for-rcp'); ?></option>
						<option
							value="no" <?php selected('no', isset($rcp_options['idpay_sandbox']) ? $rcp_options['idpay_sandbox'] : ''); ?>><?php _e('No', 'idpay-for-rcp'); ?></option>
					</select>
					<?php _e('If you check this option, the gateway will work in Test (Sandbox) mode.', 'idpay-for-rcp'); ?>
				</p>
			</td>
		</tr>
		<tr valign="top">
			<th>
				<label for="rcp_settings[idpay_symbol]"
					   id="idpaySymbol"><?php _e('Show currency?', 'idpay-for-rcp'); ?></label>
			</th>
			<td>
				<p class="description">
					<select id="rcp_settings[idpay_symbol]" name="rcp_settings[idpay_symbol]">
						<option
							value="yes" <?php selected('yes', isset($rcp_options['idpay_symbol']) ? $rcp_options['idpay_symbol'] : ''); ?>><?php _e('Yes', 'idpay-for-rcp'); ?></option>
						<option
							value="no" <?php selected('no', isset($rcp_options['idpay_symbol']) ? $rcp_options['idpay_symbol'] : ''); ?>><?php _e('No', 'idpay-for-rcp'); ?></option>
					</select>
				</p>
			</td>
		</tr>
	</table>
	<?php
}

add_action('rcp_payments_settings', 'rcp_idpay_settings');
