=== Joinhook ===
Contributors: emrikol
Tags: webhook
Donate link: http://wordpressfoundation.org/donate/
Requires at least: 4.7.2
Tested up to: 4.7.2
License: GPL3

Creates a webhook endpoint to connect Join (Android App) to Sonarr

== Description ==
[Sonarr](https://sonarr.tv/) allows notifications of content downloads via webhooks (among other ways).  To get this working with [Join](https://joaoapps.com/join/), I wanted to use a webhook to capture the data and send it back out.  This is what the plugin does.

There is some setup needed, mainly adding a Device or Group ID to send to, which can be done via wp-admin under the Tools menu -> Joinhook (`/wp-admin/tools.php?page=joinhook`).

== Installation ==
Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.  View the Joinhook settings in the Tools administration menu to set up the plugin.

== Changelog ==

= 1.0.0 =

First Version