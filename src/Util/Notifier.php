<?php
namespace SecurityWP\Util;

final class Notifier {
  public static function notify_admin(string $subject, string $message): void {
    $settings = get_option('securitywp_settings', []);
    $enabled = !empty($settings['email_alerts']);
    if (!$enabled) {
      return;
    }

    $to = !empty($settings['alert_email']) ? sanitize_email($settings['alert_email']) : '';
    if (empty($to)) {
      $to = get_option('admin_email');
    }

    $site = wp_parse_url(home_url(), PHP_URL_HOST) ?: home_url();
    $fullSubject = sprintf('[SecurityWP][%s] %s', $site, $subject);

    wp_mail($to, $fullSubject, $message);
  }
}
