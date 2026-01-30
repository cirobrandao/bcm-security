<?php
/**
 * Plugin Name:       BCM Security
 * Plugin URI:        https://github.com/cirobrandao/bcm-security
 * Description:       Hardening, login protection, REST/XML-RPC restrictions, integrity scanning, and alerts.
 * Version:           0.5.3
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            cirobrandao
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       securitywp
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
  exit;
}

define('SECURITYWP_VERSION', '0.5.3');
define('SECURITYWP_PLUGIN_FILE', __FILE__);
define('SECURITYWP_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Public links (NOT user-configurable).
define('SECURITYWP_PLATFORM_URL', 'https://dev.zone.net.br/wordpress');
define('SECURITYWP_CONTRIBUTION_URL', 'https://bcmnetwork.com.br/contribution');
define('SECURITYWP_GITHUB_URL', 'https://github.com/cirobrandao/bcm-security');

autoload_securitywp();

function autoload_securitywp(): void {
  require_once SECURITYWP_PLUGIN_DIR . 'src/Util/Logger.php';
  require_once SECURITYWP_PLUGIN_DIR . 'src/Util/Notifier.php';
  require_once SECURITYWP_PLUGIN_DIR . 'src/Util/UpdateChecker.php';
  require_once SECURITYWP_PLUGIN_DIR . 'src/Util/I18n.php';

  require_once SECURITYWP_PLUGIN_DIR . 'src/Security/Ip.php';
  require_once SECURITYWP_PLUGIN_DIR . 'src/Security/AdminAccessGuard.php';
  require_once SECURITYWP_PLUGIN_DIR . 'src/Security/LoginUrlManager.php';
  require_once SECURITYWP_PLUGIN_DIR . 'src/Security/LoginProtector.php';
  require_once SECURITYWP_PLUGIN_DIR . 'src/Security/XmlRpcHardener.php';
  require_once SECURITYWP_PLUGIN_DIR . 'src/Security/RestHardener.php';

  require_once SECURITYWP_PLUGIN_DIR . 'src/Scanner/IntegrityScanner.php';

  require_once SECURITYWP_PLUGIN_DIR . 'src/Admin/DashboardPage.php';
  require_once SECURITYWP_PLUGIN_DIR . 'src/Admin/SettingsPage.php';
  require_once SECURITYWP_PLUGIN_DIR . 'src/Admin/EventsPage.php';
  require_once SECURITYWP_PLUGIN_DIR . 'src/Admin/AboutPage.php';
  require_once SECURITYWP_PLUGIN_DIR . 'src/Admin/Menu.php';

  require_once SECURITYWP_PLUGIN_DIR . 'src/Plugin.php';
}

add_action('plugins_loaded', function () {
  load_plugin_textdomain('securitywp', false, dirname(plugin_basename(__FILE__)) . '/languages');
  \SecurityWP\Plugin::instance()->boot();
});
