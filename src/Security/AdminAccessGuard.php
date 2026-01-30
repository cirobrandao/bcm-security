<?php
namespace SecurityWP\Security;

use SecurityWP\Util\Logger;

final class AdminAccessGuard {
  public function hooks(): void {
    add_action('admin_init', [$this, 'maybe_block_admin'], 0);
  }

  public function maybe_block_admin(): void {
    // Do not block AJAX to avoid breaking frontend.
    if (wp_doing_ajax()) {
      return;
    }

    $s = get_option('securitywp_settings', []);
    $raw = (string)($s['admin_allowlist_ips'] ?? '');
    $raw = trim($raw);

    if ($raw === '') {
      return;
    }

    $ip = Ip::client_ip();

    $allowed = $this->is_allowlisted($ip, $raw);
    if ($allowed) {
      return;
    }

    Logger::warning('Blocked wp-admin access by IP', [
      'ip' => $ip,
      'uri' => $_SERVER['REQUEST_URI'] ?? '',
    ]);

    status_header(403);
    nocache_headers();
    wp_die(
      esc_html__('Access denied: your IP is not allowed to access wp-admin.', 'securitywp'),
      esc_html__('Forbidden', 'securitywp'),
      ['response' => 403]
    );
  }

  private function is_allowlisted(string $ip, string $raw): bool {
    $lines = preg_split('/\r?\n/', $raw) ?: [];
    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '') {
        continue;
      }
      if ($line === $ip) {
        return true;
      }
    }
    return false;
  }
}
