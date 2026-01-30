=== BCM Security ===
Contributors: cirobrandao
Tags: security, hardening
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress security plugin (scaffold) with login protection, REST/XML-RPC hardening, integrity scanning, and alerts.

== Description ==
BCM Security aims to provide hardening toggles and security checks, similar in spirit to plugins like Jetpack/Wordfence, but lightweight and customizable.

== Installation ==
1. Upload the plugin folder to /wp-content/plugins/securitywp/
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Settings → BCM Security.
4. Generate a baseline and optionally run a scan.

== Changelog ==
= 0.2.1 =
* Improved admin UI/UX.
* Added EN/pt-BR i18n support (textdomain + translations).
* Baseline/Scan buttons now show progress and results.

= 0.2.0 =
* Login rate limiting + temporary IP block.
* XML-RPC disable toggle.
* REST hardening (block + hide users endpoints).
* Integrity scanner (baseline + daily scan) with email/log alerts.

= 0.1.0 =
* Initial scaffold: settings page + basic hardening.
