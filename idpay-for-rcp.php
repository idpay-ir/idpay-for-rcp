<?php
/**
 * Plugin Name: IDPay for Restrict Content Pro
 * Author: IDPay
 * Description: <a href="https://idpay.ir">IDPay</a> secure payment gateway for Restrict Content Pro
 * Version: 1.2.2
 * Author URI: https://idpay.ir
 * Text Domain: idpay-for-rcp
 * Domain Path: languages
 *
 * IDPay Gateway Utility is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * IDPay Gateway Utility is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with IDPay Gateway Utility. If not, see <http://www.gnu.org/licenses/>.
 */

// Exit, if access directly.
if (!defined('ABSPATH')) exit;

if (!class_exists('RCP_IDPay')):

	/**
	 * Main RCP_IDPay class.
	 */
	final class RCP_IDPay
	{
		/** Singleton *************************************************************/

		/**
		 * @var RCP_IDPay The one true RCP_IDPay instance
		 */
		private static $instance;

		/**
		 * Main RCP_IDPay instance.
		 *
		 * @static
		 * @staticvar array $instance
		 * @return object|RCP_IDPay The one true RCP_IDPay
		 * @uses RCP_IDPay:includes() Include the required files.
		 * @uses RCP_IDPay::load_textdomain() Load the language files.
		 * @see  RCP_IDPay()
		 * @uses RCP_IDPay::setup_constants() Setup the constants needed.
		 */
		public static function instance()
		{
			if (!isset(self::$instance) && !(self::$instance instanceof RCP_IDPay)) {
				self::$instance = new RCP_IDPay;
				self::$instance->setup_constants();

				add_action('plugins_loaded', [self::$instance, 'load_textdomain']);
				add_action('plugins_loaded', [self::$instance, 'includes']);
			}

			return self::$instance;
		}

		/**
		 * Setup plugin constants.
		 *
		 * @access private
		 * @return void
		 */
		private function setup_constants()
		{

			// Plugin version.
			if (!defined('RCP_IDPAY_VERSION')) {
				define('RCP_IDPAY_VERSION', '1.2.0');
			}

			// Plugin directory path.
			if (!defined('RCP_IDPAY_PLUGIN_DIR')) {
				define('RCP_IDPAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
			}

			// Plugin root file.
			if (!defined('RCP_IDPAY_PLUGIN_FILE')) {
				define('RCP_IDPAY_PLUGIN_FILE', __FILE__);
			}
		}

		/**
		 * Include required files.
		 *
		 * @access private
		 * @return void
		 */
		public function includes()
		{
			require_once RCP_IDPAY_PLUGIN_DIR . 'includes/functions.php';
			require_once RCP_IDPAY_PLUGIN_DIR . 'includes/filters.php';
			require_once RCP_IDPAY_PLUGIN_DIR . 'includes/admin/settings.php';
			require_once RCP_IDPAY_PLUGIN_DIR . 'includes/actions.php';
			require_once RCP_IDPAY_PLUGIN_DIR . 'includes/RCP_Payment_Gateway_IDPay.php';
		}

		/**
		 * Loads the plugin language files.
		 *
		 * @return void
		 * @since 1.4
		 */
		public function load_textdomain()
		{
			global $wp_version;
			/*
			 * Due to the introduction of language packs through translate.wordpress.org, loading our textdomain is complex.
			 *
			 * To support existing translation files from before the change, we must look for translation files in several places and under several names.
			 *
			 * - wp-content/languages/plugins/idpay-for-rcp (introduced with language packs)
			 * - wp-content/languages/idpay-for-rcp/ (custom folder we have supported since 1.4)
			 * - wp-content/plugins/idpay-for-rcp/languages/
			 *
			 * In wp-content/languages/idpay-for-rcp/ we must look for "idpay-for-rcp-{lang}_{country}.mo"
			 * In wp-content/languages/plugins/idpay-for-rcp/ we only need to look for "idpay-for-rcp-{lang}_{country}.mo" as that is the new structure
			 * In wp-content/plugins/idpay-for-rcp/languages/, we must look for both naming conventions. This is done by filtering "load_textdomain_mofile"
			 *
			 */
			add_filter('load_textdomain_mofile', array($this, 'load_old_textdomain'), 10, 2);
			// Set filter for plugin's languages directory.
			$plugin_lang_dir = dirname(plugin_basename(RCP_IDPAY_PLUGIN_FILE)) . '/languages/';

			// Traditional WordPress plugin locale filter.
			$locale = get_locale();
			if ($wp_version >= 4.7) {
				$locale = get_user_locale();
			}
			/**
			 * Defines the plugin language locale used in Easy Digital Downloads.
			 *
			 * @var $get_locale The locale to use. Uses get_user_locale()` in WordPress 4.7 or greater,
			 *                  otherwise uses `get_locale()`.
			 */
			$mofile = sprintf('%1$s-%2$s.mo', 'idpay-for-rcp', $locale);
			// Look for wp-content/languages/idpay-for-rcp/idpay-for-rcp-{lang}_{country}.mo
			$mofile_global1 = WP_LANG_DIR . '/idpay-for-rcp/idpay-for-rcp-' . $locale . '.mo';
			// Look for wp-content/languages/rcp-iday/{lang}_{country}.mo
			$mofile_global2 = WP_LANG_DIR . '/idpay-for-rcp/' . $locale . '.mo';
			// Look in wp-content/languages/plugins/idpay-for-rcp
			$mofile_global3 = WP_LANG_DIR . '/plugins/idpay-for-rcp/' . $mofile;
			if (file_exists($mofile_global1)) {
				load_textdomain('idpay-for-rcp', $mofile_global1);
			} elseif (file_exists($mofile_global2)) {
				load_textdomain('idpay-for-rcp', $mofile_global2);
			} elseif (file_exists($mofile_global3)) {
				load_textdomain('idpay-for-rcp', $mofile_global3);
			} else {
				// Load the default language files.
				load_plugin_textdomain('idpay-for-rcp', false, $plugin_lang_dir);
			}
		}

		/**
		 * Load a .mo file for the old textdomain if one exists.
		 *
		 * h/t: https://github.com/10up/grunt-wp-plugin/issues/21#issuecomment-62003284
		 */
		function load_old_textdomain($mofile, $textdomain)
		{
			if ($textdomain === 'idpay-for-rcp' && !file_exists($mofile)) {
				$mofile = dirname($mofile) . DIRECTORY_SEPARATOR . str_replace($textdomain, 'idpay-for-rcp', basename($mofile));
			}
			return $mofile;
		}
	}
endif;

return RCP_IDPay::instance();
