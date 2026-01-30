<?php
namespace SecurityWP\Security;

use SecurityWP\Util\Logger;
use SecurityWP\Util\Notifier;

final class LoginProtector {
  public function hooks(): void {
    add_filter('authenticate', [$this, 'maybe_block_auth'], 1, 3);
    add_action('wp_login_failed', [$this, 'on_login_failed'], 10, 1);
    add_action('wp_login', [$this, 'on_login_success'], 10, 2);
  }

  public function maybe_block_auth($user, $username, $password) {
    $s = get_option('securitywp_settings', []);
    if (empty($s['login_rate_limit'])) {
      return $user;
    }

    $ip = Ip::client_ip();
    if ($this->is_allowlisted($ip, $s)) {
      return $user;
    }

    $blockedUntil = (int) get_transient($this->block_key($ip));

    if ($blockedUntil > time()) {
      $mins = (int) ceil(($blockedUntil - time()) / 60);
      return new \WP_Error(
        'securitywp_blocked',
        sprintf(__('Too many login attempts. Try again in %d minute(s).', 'securitywp'), max(1, $mins))
      );
    }

    return $user;
  }

  public function on_login_failed(string $username): void {
    $s = get_option('securitywp_settings', []);
    if (empty($s['login_rate_limit'])) {
      return;
    }

    $ip = Ip::client_ip();
    if ($this->is_allowlisted($ip, $s)) {
      return;
    }

    $max = max(1, (int)($s['login_max_attempts'] ?? 5));
    $window = max(60, (int)($s['login_window_sec'] ?? 600));
    $baseBlock = max(60, (int)($s['login_block_sec'] ?? 900));

    $countKey = $this->count_key($ip);
    $count = (int) get_transient($countKey);
    $count++;
    set_transient($countKey, $count, $window);

    if ($count >= $max) {
      $strikesKey = $this->strikes_key($ip);
      $strikes = (int) get_transient($strikesKey);
      $strikes++;
      set_transient($strikesKey, $strikes, DAY_IN_SECONDS);

      $blockSec = (int) min(12 * HOUR_IN_SECONDS, $baseBlock * (2 ** max(0, $strikes - 1)));
      $until = time() + $blockSec;
      set_transient($this->block_key($ip), $until, $blockSec);

      Logger::warning('Login temporarily blocked', [
        'ip' => $ip,
        'username' => $username,
        'attempts' => $count,
        'window_sec' => $window,
        'block_sec' => $blockSec,
        'strikes' => $strikes,
      ]);

      Notifier::notify_admin(
        __('Login blocked', 'securitywp'),
        sprintf(
          __("SecurityWP blocked login attempts temporarily.\n\nIP: %s\nUsername: %s\nAttempts in window: %d\nBlock (sec): %d\n", 'securitywp'),
          $ip,
          $username,
          $count,
          $blockSec
        )
      );

      delete_transient($countKey);
    }
  }

  public function on_login_success(string $user_login, \WP_User $user): void {
    $s = get_option('securitywp_settings', []);
    if (empty($s['login_rate_limit'])) {
      return;
    }
    $ip = Ip::client_ip();
    if ($this->is_allowlisted($ip, $s)) {
      return;
    }
    delete_transient($this->count_key($ip));
  }

  private function is_allowlisted(string $ip, array $settings): bool {
    $raw = (string)($settings['login_allowlist_ips'] ?? '');
    if ($raw === '') {
      return false;
    }

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

  private function count_key(string $ip): string {
    return 'securitywp_login_count_' . md5($ip);
  }

  private function block_key(string $ip): string {
    return 'securitywp_login_block_' . md5($ip);
  }

  private function strikes_key(string $ip): string {
    return 'securitywp_login_strikes_' . md5($ip);
  }
}
