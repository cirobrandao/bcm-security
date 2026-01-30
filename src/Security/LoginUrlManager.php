<?php
namespace SecurityWP\Security;

use SecurityWP\Util\Logger;

/**
 * Login URL customization.
 *
 * One slug to serve wp-login.php:
 *   /{slug}/           => wp-login.php
 *   /{slug}/register/  => wp-login.php?action=register
 *
 * When enabled, direct access to /wp-login.php returns 404.
 *
 * Requirements:
 * - Pretty permalinks must be enabled (Settings → Permalinks).
 */
final class LoginUrlManager {
  private const QUERY_VAR = 'securitywp_login';

  public function hooks(): void {
    add_filter('query_vars', [$this, 'query_vars']);
    add_action('init', [$this, 'add_rewrites'], 10);
    add_action('template_redirect', [$this, 'maybe_handle_custom_login']);

    // Block direct access to wp-login.php when enabled.
    add_action('login_init', [$this, 'maybe_block_default_login']);

    // Mark for rewrite flush when settings change.
    add_action('update_option_securitywp_settings', [$this, 'on_settings_update'], 10, 2);
  }

  public function query_vars(array $vars): array {
    $vars[] = self::QUERY_VAR;
    return $vars;
  }

  public function add_rewrites(): void {
    $s = get_option('securitywp_settings', []);
    $slug = $this->slug($s['login_slug'] ?? '');

    if ($slug) {
      // /slug/ (login)
      add_rewrite_rule('^' . $slug . '/?$', 'index.php?' . self::QUERY_VAR . '=login', 'top');
      // /slug/register/ (register)
      add_rewrite_rule('^' . $slug . '/register/?$', 'index.php?' . self::QUERY_VAR . '=register', 'top');
    }

    // One-shot flush if requested (after settings change).
    if (get_option('securitywp_flush_rewrite')) {
      delete_option('securitywp_flush_rewrite');
      flush_rewrite_rules(false);
    }
  }

  public function maybe_handle_custom_login(): void {
    $mode = get_query_var(self::QUERY_VAR);
    if (!$mode) {
      return;
    }

    if ($mode === 'register') {
      $_REQUEST['action'] = 'register';
    }

    Logger::info('Serving custom login URL', [
      'mode' => $mode,
      'ip' => Ip::client_ip(),
    ]);

    require_once ABSPATH . 'wp-login.php';
    exit;
  }

  public function maybe_block_default_login(): void {
    $s = get_option('securitywp_settings', []);
    $slug = $this->slug($s['login_slug'] ?? '');

    if (!$slug) {
      return;
    }

    $req = $_SERVER['REQUEST_URI'] ?? '';
    if (is_string($req) && str_contains($req, 'wp-login.php')) {
      status_header(404);
      nocache_headers();
      wp_die(
        esc_html__('Not found.', 'securitywp'),
        esc_html__('Not found', 'securitywp'),
        ['response' => 404]
      );
    }
  }

  public function on_settings_update($old, $new): void {
    $oldSlug = $this->slug($old['login_slug'] ?? '');
    $newSlug = $this->slug($new['login_slug'] ?? '');

    if ($oldSlug !== $newSlug) {
      update_option('securitywp_flush_rewrite', 1, false);
    }
  }

  private function slug($value): string {
    $value = is_string($value) ? trim($value) : '';
    if ($value === '') {
      return '';
    }

    // Keep it URL-safe.
    return sanitize_title($value);
  }
}
