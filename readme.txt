=== Dashboard Log Monitor ===
Contributors: onnimonni, ottok
Tags: dashboard, admin, log, access log, seravo
Donate link: http://seravo.fi/
Requires at least: 3.8
Tested up to: 4.4.1
Stable tag: 1.0.4
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Monitor your logs straight from your WordPress admin dashboard

== Description ==

This plugin is useful e.g. if you want the WP Admin to know about potential problems that can be seen in the access logs.

The code is optimized to be fast and does not for example do any database lookups or use cookies.

This plugin is made by [Seravo Oy](http://seravo.fi/), which specializes in open source support services and among others is the only company in Finland to provide [WordPress Premium Hosting](http://seravo.fi/wordpress-palvelu).

Source available at https://github.com/Seravo/wp-dashboard-log-monitor

== Installation ==

1. Upload plugin to the `/wp-content/plugins/` directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Make sure the `wp-config.php` defines the needed constants.

== Frequently Asked Questions ==


== Screenshots ==


== Changelog ==

Note that complete commit log is available at https://github.com/Seravo/wp-dashboard-log-monitor/commits/master
= 1.0.4 =
Fixed php tags which were not working in all systems

= 1.0.3 =
Fixed User permissions. Only admins can see this widget

= 1.0.2 =
Fixed PHP Notices from undefined variables

= 1.0.1 =
Small fixes on default settings

= 1.0 =
* Mature enough for official 1.0 release
