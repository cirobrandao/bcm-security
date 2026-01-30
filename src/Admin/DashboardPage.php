<?php
namespace SecurityWP\Admin;

final class DashboardPage {
  public static function render_page(): void {
    if (!current_user_can('manage_options')) {
      return;
    }

    $s = get_option('securitywp_settings', []);

    $checks = [];
    $checks[] = [
      'label' => __('XML-RPC disabled', 'securitywp'),
      'ok' => !empty($s['disable_xmlrpc']),
      'help' => __('Recommended for most sites.', 'securitywp'),
    ];
    $checks[] = [
      'label' => __('REST hardening enabled', 'securitywp'),
      'ok' => !empty($s['rest_hardening']),
      'help' => __('Reduces exposure of sensitive endpoints to anonymous users.', 'securitywp'),
    ];
    $checks[] = [
      'label' => __('Login rate limiting enabled', 'securitywp'),
      'ok' => !empty($s['login_rate_limit']),
      'help' => __('Helps mitigate brute-force attacks.', 'securitywp'),
    ];

    $baseline = get_option('securitywp_integrity_baseline', null);
    $baselineOk = is_array($baseline) && !empty($baseline['files']);
    $checks[] = [
      'label' => __('Integrity baseline exists', 'securitywp'),
      'ok' => $baselineOk,
      'help' => __('Generate a baseline to enable meaningful integrity scans.', 'securitywp'),
    ];

    $last = get_option('securitywp_last_scan', []);
    $lastTime = isset($last['t']) ? (int)$last['t'] : 0;
    $lastSummary = isset($last['summary']) ? (string)$last['summary'] : '';

    $total = count($checks);
    $okCount = 0;
    foreach ($checks as $c) {
      if (!empty($c['ok'])) {
        $okCount++;
      }
    }
    $score = (int) round(($okCount / max(1, $total)) * 100);

    echo '<div class="wrap securitywp-wrap">';
    echo '<h1>' . esc_html__('BCM Security', 'securitywp') . '</h1>';

    echo '<div class="securitywp-card">';
    echo '<h2>' . esc_html__('Security overview', 'securitywp') . '</h2>';
    echo '<p>' . esc_html(sprintf(__('Score: %d/100', 'securitywp'), $score)) . '</p>';

    echo '<table class="widefat striped">';
    echo '<thead><tr><th>' . esc_html__('Check', 'securitywp') . '</th><th style="width:120px">' . esc_html__('Status', 'securitywp') . '</th></tr></thead>';
    echo '<tbody>';
    foreach ($checks as $c) {
      $status = !empty($c['ok']) ? __('OK', 'securitywp') : __('Needs attention', 'securitywp');
      echo '<tr>';
      echo '<td><strong>' . esc_html($c['label']) . '</strong><br><span style="color:#646970">' . esc_html($c['help']) . '</span></td>';
      echo '<td>' . esc_html($status) . '</td>';
      echo '</tr>';
    }
    echo '</tbody></table>';

    echo '<p style="margin-top:12px">';
    echo '<a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=securitywp-settings')) . '">' . esc_html__('Configure settings', 'securitywp') . '</a> ';
    echo '<a class="button button-secondary" href="' . esc_url(admin_url('admin.php?page=securitywp-events')) . '">' . esc_html__('View Security Events', 'securitywp') . '</a> ';
    echo '<a class="button button-secondary" href="' . esc_url(admin_url('admin.php?page=securitywp-about')) . '">' . esc_html__('About / Links', 'securitywp') . '</a>';
    echo '</p>';

    echo '</div>';

    echo '<div class="securitywp-card" style="margin-top:16px">';
    echo '<h2>' . esc_html__('System', 'securitywp') . '</h2>';
    echo '<ul style="list-style: disc; padding-left: 18px">';
    echo '<li>' . esc_html__('WordPress version:', 'securitywp') . ' <code>' . esc_html(get_bloginfo('version')) . '</code></li>';
    echo '<li>' . esc_html__('PHP version:', 'securitywp') . ' <code>' . esc_html(PHP_VERSION) . '</code></li>';
    if ($lastTime) {
      echo '<li>' . esc_html__('Last integrity scan:', 'securitywp') . ' <code>' . esc_html(date_i18n('Y-m-d H:i:s', $lastTime)) . '</code> ' . esc_html($lastSummary) . '</li>';
    } else {
      echo '<li>' . esc_html__('Last integrity scan:', 'securitywp') . ' <code>' . esc_html__('Never', 'securitywp') . '</code></li>';
    }
    echo '</ul>';
    echo '</div>';

    echo '</div>';
  }
}
