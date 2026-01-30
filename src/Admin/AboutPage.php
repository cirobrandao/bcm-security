<?php
namespace SecurityWP\Admin;

final class AboutPage {
  public static function render_page(): void {
    if (!current_user_can('manage_options')) {
      return;
    }

    $repo = defined('SECURITYWP_GITHUB_URL') ? SECURITYWP_GITHUB_URL : 'https://github.com/cirobrandao/bcm-security';
    $platform = defined('SECURITYWP_PLATFORM_URL') ? SECURITYWP_PLATFORM_URL : 'https://dev.zone.net.br/wordpress';
    $contrib = defined('SECURITYWP_CONTRIBUTION_URL') ? SECURITYWP_CONTRIBUTION_URL : 'https://bcmnetwork.com.br/contribution';

    echo '<div class="wrap securitywp-wrap">';
    echo '<h1>' . esc_html__('BCM Security — Information & Updates', 'securitywp') . '</h1>';

    echo '<div class="securitywp-grid">';

    // About card
    echo '<div class="securitywp-card">';
    echo '<h2>' . esc_html__('About', 'securitywp') . '</h2>';
    echo '<p>' . esc_html__('BCM Security is an open-source WordPress security plugin scaffold. It provides hardening toggles, login protection, integrity scanning, and alerts.', 'securitywp') . '</p>';
    echo '<p><strong>' . esc_html__('Installed version:', 'securitywp') . '</strong> ' . esc_html(defined('SECURITYWP_VERSION') ? SECURITYWP_VERSION : '-') . '</p>';
    echo '<p style="color:#646970">' . esc_html__('This page is meant to help you keep the plugin updated and collaborate with the project.', 'securitywp') . '</p>';
    echo '</div>';

    // Links / support
    echo '<div class="securitywp-card">';
    echo '<h2>' . esc_html__('Collaborate', 'securitywp') . '</h2>';
    echo '<ul style="list-style: disc; padding-left: 18px">';
    echo '<li><a href="' . esc_url($repo) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('GitHub Repository (issues / PRs)', 'securitywp') . '</a></li>';
    echo '<li><a href="' . esc_url($contrib) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Support the project (donate / sponsor)', 'securitywp') . '</a></li>';
    echo '<li><a href="' . esc_url($platform) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Platform / Services', 'securitywp') . '</a></li>';
    echo '</ul>';
    echo '<p style="color:#646970">' . esc_html__('These links are managed by the plugin authors and cannot be changed from the WordPress admin.', 'securitywp') . '</p>';
    echo '</div>';

    echo '</div>'; // grid

    echo '<div class="securitywp-card" style="margin-top:16px">';
    echo '<h2>' . esc_html__('Updates', 'securitywp') . '</h2>';
    echo '<p>' . esc_html__('BCM Security can check for updates via GitHub Releases. To distribute updates through WordPress update UI, publish a new Release with a semantic version tag (example: 0.5.3).', 'securitywp') . '</p>';
    echo '<p><a class="button button-secondary" href="' . esc_url($repo) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Open GitHub', 'securitywp') . '</a></p>';
    echo '</div>';

    echo '<p><a class="button button-secondary" href="' . esc_url(admin_url('admin.php?page=securitywp')) . '">' . esc_html__('Back to Settings', 'securitywp') . '</a></p>';

    echo '</div>';
  }
}
