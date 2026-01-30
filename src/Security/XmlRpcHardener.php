<?php
namespace SecurityWP\Security;

final class XmlRpcHardener {
  public function hooks(): void {
    add_filter('xmlrpc_enabled', [$this, 'xmlrpc_enabled']);

    // Keep pingback.ping removed too.
    add_filter('xmlrpc_methods', function ($methods) {
      if (isset($methods['pingback.ping'])) {
        unset($methods['pingback.ping']);
      }
      return $methods;
    });

    add_filter('wp_headers', function ($headers) {
      if (isset($headers['X-Pingback'])) {
        unset($headers['X-Pingback']);
      }
      return $headers;
    });
  }

  public function xmlrpc_enabled(bool $enabled): bool {
    $s = get_option('securitywp_settings', []);
    if (!empty($s['disable_xmlrpc'])) {
      return false;
    }
    return $enabled;
  }
}
