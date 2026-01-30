<?php
namespace SecurityWP;

use SecurityWP\Admin\AboutPage;
use SecurityWP\Admin\DashboardPage;
use SecurityWP\Admin\EventsPage;
use SecurityWP\Admin\Menu;
use SecurityWP\Admin\SettingsPage;
use SecurityWP\Scanner\IntegrityScanner;
use SecurityWP\Security\AdminAccessGuard;
use SecurityWP\Security\LoginProtector;
use SecurityWP\Security\LoginUrlManager;
use SecurityWP\Security\RestHardener;
use SecurityWP\Security\XmlRpcHardener;

final class Plugin {
  private static ?Plugin $instance = null;

  public static function instance(): Plugin {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function boot(): void {
    // Admin UI.
    if (is_admin()) {
      (new Menu())->hooks();
      (new SettingsPage())->hooks();
      EventsPage::hooks();
      // AboutPage and DashboardPage are render-only.
    }

    // Security modules.
    (new AdminAccessGuard())->hooks();
    (new LoginUrlManager())->hooks();
    (new LoginProtector())->hooks();
    (new XmlRpcHardener())->hooks();
    (new RestHardener())->hooks();

    // Integrity scanner (cron + ajax tools).
    (new IntegrityScanner())->hooks();

    $this->hide_wp_version();
  }

  private function hide_wp_version(): void {
    remove_action('wp_head', 'wp_generator');
    add_filter('the_generator', '__return_empty_string');
  }
}
