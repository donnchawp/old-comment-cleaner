=== Old Comment Cleaner ===
Contributors: donncha
Tags: comments, cleaner, delete, old comments, privacy
Requires at least: 5.0
Tested up to: 6.6.2
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Clean up old comment data based on user-defined settings.

== Description ==

Old Comment Cleaner is a WordPress plugin that allows you to clean old comment data based on user-defined settings. You can specify the age of comments to clean which will replace email addresses and names, and delete author URLs associated with the comments.

The "Confirm Cleaning" checkbox must be checked before any destructive actions are taken. When comments are cleaned, they will have any or all of the following done:

* Email addresses will be replaced with "example@example.com".
* Names will be replaced with "Anonymous Guest".
* Website URLs will be replaced with an empty string.

== Installation ==

1. Upload the `old-comment-cleaner` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Settings -> Old Comment Cleaner to configure the plugin.

== Frequently Asked Questions ==

= How do I configure the plugin? =

After activating the plugin, go to Settings -> Old Comment Cleaner to configure the settings. You can specify the age of comments to clean and choose to clean email addresses, names, and website URLs associated with the comments.

= How do I clean old comments immediately? =

You can clean old comments immediately by clicking the "Clean Now" button on the settings page. Make sure that "Confirm Cleaning" is enabled.

== Changelog ==

= 1.2 =
* Fix formatting and clean up.

= 1.1 =
* Use WP API instead of SQL
* gmdate instead of date
* Other bug fixes.

= 1.0 =
* Initial release.

== Upgrade Notice ==
Minor changes to formatting

== License ==

This plugin is licensed under the GPLv2 or later. For more information, see https://www.gnu.org/licenses/gpl-2.0.html.
