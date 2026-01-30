<?php
namespace SecurityWP\Security;

use SecurityWP\Util\Logger;

final class RestHardener {
  public function hooks(): void {
    add_filter('rest_authentication_errors', [$this, 'rest_authentication_errors']);
    add_filter('rest_endpoints', [$this, 'rest_endpoints']);
  }

  /**
   * Block unauthenticated access to sensitive endpoints (minimal safe defaults).
   */
  public function rest_authentication_errors($result) {
    if (!empty($result)) {
      return $result;
    }

    $s = get_option('securitywp_settings', []);
    if (empty($s['rest_hardening'])) {
      return $result;
    }

    // Allow logged-in requests.
    if (is_user_logged_in()) {
      return $result;
    }

    $route = $_SERVER['REQUEST_URI'] ?? '';
    $route = is_string($route) ? $route : '';

    // If explicitly disabling users endpoint, block it for anonymous users.
    if (!empty($s['rest_block_users']) && (str_contains($route, '/wp-json/wp/v2/users'))) {
      Logger::warning('Blocked anonymous REST users endpoint', ['uri' => $route, 'ip' => Ip::client_ip()]);
      return new \WP_Error('securitywp_rest_blocked', 'REST endpoint is restricted.', ['status' => 403]);
    }

    return $result;
  }

  /**
   * Optionally remove endpoint registration so it doesn't appear in discovery.
   */
  public function rest_endpoints(array $endpoints): array {
    $s = get_option('securitywp_settings', []);
    if (empty($s['rest_hardening'])) {
      return $endpoints;
    }

    if (!empty($s['rest_hide_users'])) {
      foreach (array_keys($endpoints) as $route) {
        if (str_starts_with($route, '/wp/v2/users')) {
          unset($endpoints[$route]);
        }
      }
    }

    return $endpoints;
  }
}
