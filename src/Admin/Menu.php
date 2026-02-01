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
      [HubPage::class, 'render_page'],
      'dashicons-shield-alt',
      80
    );
  }
}
