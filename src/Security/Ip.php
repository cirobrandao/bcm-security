<?php
namespace SecurityWP\Security;

final class Ip {
  /**
   * Conservative: use REMOTE_ADDR only.
   * If you are behind a reverse proxy/CDN, add a trusted-proxy feature later.
   */
  public static function client_ip(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ip = is_string($ip) ? trim($ip) : '';

    // Basic normalization.
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
      return $ip;
    }

    return '0.0.0.0';
  }
}
