<?php
namespace SecurityWP\Admin;

final class EventsPage {
  public static function hooks(): void {
    add_action('admin_post_securitywp_clear_log', [self::class, 'clear_log']);
  }

  public static function clear_log(): void {
    if (!current_user_can('manage_options')) {
      wp_die(__('Forbidden', 'securitywp'));
    }
    check_admin_referer('securitywp_clear_log');

    delete_option('securitywp_log');

    wp_safe_redirect(admin_url('admin.php?page=securitywp-events&cleared=1'));
    exit;
  }

  public static function render_page(): void {
    if (!current_user_can('manage_options')) {
      return;
    }

    $level = isset($_GET['level']) ? sanitize_text_field((string)$_GET['level']) : '';
    $q = isset($_GET['q']) ? sanitize_text_field((string)$_GET['q']) : '';

    $rows = get_option('securitywp_log', []);
    if (!is_array($rows)) {
      $rows = [];
    }

    $filtered = array_values(array_filter($rows, function ($row) use ($level, $q) {
      if (!is_array($row)) {
        return false;
      }
      if ($level && (($row['level'] ?? '') !== $level)) {
        return false;
      }
      if ($q) {
        $hay = strtolower((string)($row['msg'] ?? ''));
        $hay2 = strtolower(wp_json_encode($row['ctx'] ?? []));
        $needle = strtolower($q);
        if (!str_contains($hay, $needle) && !str_contains($hay2, $needle)) {
          return false;
        }
      }
      return true;
    }));

    echo '<div class="wrap securitywp-wrap">';
    echo '<h1>' . esc_html__('Security Events', 'securitywp') . '</h1>';
    echo '<p style="max-width: 1000px">' . esc_html__('This page shows recent security-related events detected by BCM Security. Use filters to find what you need.', 'securitywp') . '</p>';

    if (!empty($_GET['cleared'])) {
      echo '<div class="notice notice-success"><p>' . esc_html__('Log cleared.', 'securitywp') . '</p></div>';
    }

    // Filters
    echo '<form method="get" style="margin: 12px 0">';
    echo '<input type="hidden" name="page" value="securitywp-events">';

    echo '<label style="margin-right: 10px">' . esc_html__('Level', 'securitywp') . ' ';
    echo '<select name="level">';
    echo '<option value="">' . esc_html__('All', 'securitywp') . '</option>';
    foreach (['INFO','WARN','ERROR'] as $lvl) {
      printf('<option value="%s" %s>%s</option>', esc_attr($lvl), selected($level, $lvl, false), esc_html($lvl));
    }
    echo '</select></label>';

    echo '<label style="margin-right: 10px">' . esc_html__('Search', 'securitywp') . ' ';
    printf('<input type="text" name="q" value="%s" class="regular-text">', esc_attr($q));
    echo '</label>';

    submit_button(__('Filter', 'securitywp'), 'secondary', '', false);
    echo '</form>';

    // Actions
    echo '<p>';
    echo '<a class="button button-secondary" href="' . esc_url(admin_url('admin.php?page=securitywp')) . '">' . esc_html__('Back to Settings', 'securitywp') . '</a> ';
    echo '<a class="button button-secondary" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=securitywp_clear_log'), 'securitywp_clear_log')) . '">' . esc_html__('Clear log', 'securitywp') . '</a>';
    echo '</p>';

    echo '<div class="securitywp-card">';
    echo '<h2>' . esc_html__('Recent log entries', 'securitywp') . '</h2>';

    if (empty($filtered)) {
      echo '<p>' . esc_html__('No entries found.', 'securitywp') . '</p>';
      echo '</div></div>';
      return;
    }

    echo '<table class="widefat striped">';
    echo '<thead><tr>';
    echo '<th style="width:160px">' . esc_html__('Time', 'securitywp') . '</th>';
    echo '<th style="width:80px">' . esc_html__('Level', 'securitywp') . '</th>';
    echo '<th>' . esc_html__('Message', 'securitywp') . '</th>';
    echo '</tr></thead>';
    echo '<tbody>';

    foreach (array_slice($filtered, 0, 200) as $row) {
      $t = isset($row['t']) ? date_i18n('Y-m-d H:i:s', (int)$row['t']) : '';
      $lvl = (string)($row['level'] ?? '');
      $msg = (string)($row['msg'] ?? '');
      $ctx = $row['ctx'] ?? [];

      echo '<tr>';
      echo '<td>' . esc_html($t) . '</td>';
      echo '<td><strong>' . esc_html($lvl) . '</strong></td>';
      echo '<td>';
      echo esc_html($msg);
      if (!empty($ctx)) {
        echo '<details style="margin-top:6px"><summary>' . esc_html__('Context', 'securitywp') . '</summary>';
        echo '<pre style="white-space: pre-wrap; margin: 8px 0 0;">' . esc_html(wp_json_encode($ctx, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '</pre>';
        echo '</details>';
      }
      echo '</td>';
      echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
    echo '</div>';
  }
}
