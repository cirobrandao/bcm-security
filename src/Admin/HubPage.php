<?php
namespace SecurityWP\Admin;

final class HubPage {
  public static function render_page(): void {
    if (!current_user_can('manage_options')) {
      return;
    }

    $tab = isset($_GET['tab']) ? sanitize_key((string)$_GET['tab']) : 'dashboard';
    if (!in_array($tab, ['dashboard', 'events', 'about'], true)) {
      $tab = 'dashboard';
    }

    echo '<div class="wrap securitywp-wrap">';
    echo '<h1>' . esc_html__('BCM Security', 'securitywp') . '</h1>';

    // Tabs
    $base = admin_url('admin.php?page=securitywp');
    echo '<h2 class="nav-tab-wrapper" style="margin-bottom:12px">';
    self::tab_link($base, 'dashboard', $tab, __('Painel', 'securitywp'));
    self::tab_link($base, 'events', $tab, __('Eventos', 'securitywp'));
    self::tab_link($base, 'about', $tab, __('Sobre', 'securitywp'));
    echo '</h2>';

    if ($tab === 'events') {
      EventsPage::render_panel();
      echo '</div>';
      return;
    }

    if ($tab === 'about') {
      AboutPage::render_panel();
      echo '</div>';
      return;
    }

    DashboardPage::render_panel();
    echo '</div>';
  }

  private static function tab_link(string $base, string $key, string $current, string $label): void {
    $url = add_query_arg(['tab' => $key], $base);
    $cls = ($key === $current) ? 'nav-tab nav-tab-active' : 'nav-tab';
    echo '<a class="' . esc_attr($cls) . '" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
  }
}
