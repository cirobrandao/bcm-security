<?php
namespace SecurityWP\Admin;

final class Menu {
  public function hooks(): void {
    add_action('admin_menu', [$this, 'admin_menu']);
  }

  public function admin_menu(): void {
    // Dashboard
    add_menu_page(
      __('BCM Security', 'securitywp'),
      __('BCM Security', 'securitywp'),
      'manage_options',
      'securitywp',
      [DashboardPage::class, 'render_page'],
      'dashicons-shield-alt',
      80
    );

    add_submenu_page(
      'securitywp',
      __('Dashboard', 'securitywp'),
      __('Dashboard', 'securitywp'),
      'manage_options',
      'securitywp',
      [DashboardPage::class, 'render_page']
    );

    add_submenu_page(
      'securitywp',
      __('Settings', 'securitywp'),
      __('Settings', 'securitywp'),
      'manage_options',
      'securitywp-settings',
      [SettingsPage::class, 'render_page']
    );

    add_submenu_page(
      'securitywp',
      __('Security Events', 'securitywp'),
      __('Security Events', 'securitywp'),
      'manage_options',
      'securitywp-events',
      [EventsPage::class, 'render_page']
    );

    add_submenu_page(
      'securitywp',
      __('About', 'securitywp'),
      __('About', 'securitywp'),
      'manage_options',
      'securitywp-about',
      [AboutPage::class, 'render_page']
    );
  }
}
