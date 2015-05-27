=== Plugin Name ===
Contributors: boris.smirnoff
Donate link:
Tags: usps, woocommerce, address verification, address standardization
Requires at least: 3.0.1
Tested up to: 4.2.2
Stable tag: 0.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A plugin that contacts USPS API and verifies, fixes address mistakes such as typos etc.

== Description ==

This plugin is using USPS API to verify the address customer entered on woocommerce checkout page, without disturbing the customer and blocking payment.
If USPS API can correct and standardize the address it does just that, if not then it puts the order ON-HOLD and sends an email to the admin
that order is waiting for manual inspection after the paypal order is processed.
For now it only works with PayPal. If you need with some other payment gateway contact me I'll develop it for you.

We were getting 1 mistake out of 20 orders. What this plugin does is simple. It takes the address written like this:

205 bagwel ave (bagwel is spelled bagwell, I intentionally made a mistake)
nutter fort
ZIP: 26301

and converts it to:

205 BAGWELL AVE
NUTTER FORT
26301

It standardizes all addresses and fixes minor problems such as typos, or even a wrong zip code if possible.

Plugin is using USPS API. You must register on USPS web site to use this, here:
https://www.usps.com/business/web-tools-apis/address-information.htm

And in plugin settings you just type in your username and email on which you wish notifications.

If you have any question, feature request, feedback or anything else, feel free to email me.

== Installation ==

Click install plugin on this page, or if you wish to do it manually, upload folder woocommerce-usps-verification into your wp-content/plugins folder
and on plugins page in your admin area, click activate.

== Frequently Asked Questions ==

= On which email do I contact you ? =

smirnoff@geek.rs.ba

= Feature X doesn't exist or is not working =

Contact me on my email address provided above.

= Which payment gateways are available ? =

Only PayPal for now.

== Screenshots ==


== Changelog ==

= 0.1.1 =
* First release. Code requires some reorganization and cleanup but it's quite simple and it's working.

== Upgrade Notice ==

== Arbitrary section ==

