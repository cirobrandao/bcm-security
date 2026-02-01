<?php
namespace SecurityWP\Admin;

final class Menu {
  public function hooks(): void {
    add_action('admin_menu', [$this, 'admin_menu']);
  }

  public function admin_menu(): void {
    add_menu_page(
      __('BCM Security', 'securitywp'),
      __('BCM Security', 'securitywp'),
      'manage_options',
      'securitywp',
      [HubPage::class, 'render_page'],
      'dashicons-shield-alt',
      80
    );

    // Hub (same as parent)
    add_submenu_page(
      'securitywp',
      __('Painel', 'securitywp'),
      __('Painel', 'securitywp'),
      'manage_options',
      'securitywp',
      [HubPage::class, 'render_page']
    );

    // Settings (keep separate)
    add_submenu_page(
      'securitywp',
      __('Settings', 'securitywp'),
      __('Settings', 'securitywp'),
      'manage_options',
      'securitywp-settings',
      [SettingsPage::class, 'render_page']
    );
  }
}
