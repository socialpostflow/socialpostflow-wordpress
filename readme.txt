=== Auto Post, Auto Publish and Schedule to Social Media - Social Post Flow ===
Contributors: socialpostflow
Donate link: https://www.socialpostflow.com/integrations/wordpress
Tags: auto post, auto publish, social media scheduling, social media automation
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Auto post Pages, Posts or Custom Post Types to Social Media using your Social Post Flow (socialpostflow.com) account.

== Description ==

Auto post your Posts, Pages, Events, Products and more to Facebook, X / Twitter, Threads, Instagram, Mastodon and more with Social Post Flow.

Don't have a Social Post Flow account? Pricing starts at $99/year for 10 social accounts, and you can [sign up for a free 7 day trial](https://app.socialpostflow.com/register)

See our quick start tutorial to auto publish your WordPress content:

[youtube https://www.youtube.com/watch?v=IFjYKMjnRB4]

=== Smart Social Media Automation ===

Social Post Flow isn't another WP to Facebook, WP to Twitter / X or yet another auto posting plugin.

Each Post Type and social media profile may be configured to publish multiple, unique status messages, each with their own settings.

There's also the option to define status settings on a per-Post basis.

Conditionally publish Posts, Pages and Custom Post Types to social media based on the Post Author(s), Taxonomy Term(s) and/or Custom Field Values

Choose to schedule publication to social media at a specific date and time, or add to your existing Social Post Flow queue

With Dynamic Tags, you can build truly unique status updates, pulling in your WordPress Post's Title, Content, Excerpt, Custom Fields and more.

Our technology ensures you don't accidentally send the same status twice, and with built in protection to prevent your social media profiles being suspended, you'll safely grow, sustain and engage web site traffic and social media following.

=== Repost and Bulk Post Old Content ==

Automatically Revive Old Posts that haven't been updated in a while, choosing the number of days, weeks or years to re-share content on social media.

Manually re-share evergreen WordPress content and revive old posts with the Bulk Publish option

=== Full Image Control ===

Choose to leverage your SEO Plugin's OpenGraph data to present beautiful sharing cards when publishing your WordPress Posts to social media.

Alternatively, include one or more images in your status updates, from a variety of sources including:

- the Post's featured image,
- the Plugin's additional images option, available on every Post,
- an ACF image or gallery field,
- your Post's inline images

=== Integrations ===

Social Post Flow supports many third party WordPress Plugins across event management, galleries, autoblogging, WooCommerce and SEO:

- The Events Calendar
- Event Manager
- Modern Events Calendar
- User Submitted Posts
- WP Property Feed
- WPeMatico
- WP Job Manager
- All-In-One SEO Pack
- Rank Math
- SEOPress
- Yoast SEO

=== Developers ===

Optionally enable WP-Cron to send status updates via Cron, speeding up UI performance and/or choose to use WP-CLI for reposting old posts.

Leverage our actions and filters to integrate your own Plugins and content.

=== Simple Social Media Scheduling ===

There's no need to mess around with App IDs, authorization tokens or complicated technical steps.

Connecting your social media profiles is done in a few clicks through Social Post Flow, taking minutes.

Then just choose which of those profiles to use in the plugin, set your status message and your social media scheduling is set.

=== Supports Twitter / X ===

If you're a Jetpack Social, Blog2Social or NextScripts Social Networks Auto-Poster user looking for a replacement to auto publish to Twitter / X, Social Post Flow is one of the best auto post to Twitter / X solutions that still works.

=== What can I do with Social Post Flow? ===

- Automatically share and auto post your WordPress Posts, Pages and Custom Post Types to social media when scheduling, publishing or updating your WordPress content
- Define the status text dynamically, pulling in your WordPress Post's Title, Content, Excerpt and more
- WordPress to Facebook Page Auto Post
- WordPress to Threads Auto Post
- WordPress to Twitter / X Auto Post
- WordPress to Instagram Auto Post
- WordPress to Mastodon Auto Post

=== External Services ===

This plugin connects to the Social Post Flow API using OAuth 2.0 PKCE, to:
- Fetch your connected social media profile names and IDs, 
- Send your WordPress Posts to one or more of your social media profiles.

You may revoke access at any time by either:
- Clicking `Disconnect` in the Plugin's settings screen,
- Revoking access to the WordPress Plugin from your [Social Post Flow account](https://app.socialpostflow.com)

Refer to Social Post Flow's [Terms and Conditions](https://www.socialpostflow.com/terms-and-conditions/) and [Privacy Policy](https://www.socialpostflow.com/privacy-policy/) for more information.

== Support ==

Support for this WordPress Plugin can be requested on the <a href="https://wordpress.org/support/plugin/social-post-flow/">WordPress forums</a>, or via your Social Post Flow account.

== Installation ==

1. Install and Activate the Social Post Flow Plugin
2. Navigate to the Social Post Flow menu entry in WordPress, and click Authorize to connect your Social Post Flow account
3. Once connected, configure the Plugin's statuses to send to social media

== Frequently Asked Questions ==

= Do I need a paid Social Post Flow account? =

Yes - pricing starts from $99/year to post to up to 10 connected social profiles.  We offer a 7 day free trial. Register at https://app.socialpostflow.com.

Our aim is to provide a competitively priced, well featured Plugin and service without breaking the bank. 

= Can I still auto publish to Twitter / X? =

Yes! You can still auto publish to Twitter / X with the Social Post Flow WordPress Plugin.

= Which Social Media Profiles can I auto post / auto publish to with Social Post Flow? =

You can post to:

- Facebook Pages
- X / Twitter
- Threads
- Instagram
- Mastodon

LinkedIn, Pinterest and TikTok are coming soon.

== Screenshots ==

1. Settings Screen when Plugin is first installed.
2. Settings Screen when Social Post Flow is authorized.
3. Settings Screen showing available options for Posts.
4. Post-level Logging.

== Changelog ==

= 1.0.3 (2025-08-18) =
* Fix: Logs: Use nonce for <select> filter dropdowns
* Fix: Logs: Honor order by column

= 1.0.2 (2025-08-15) =
* Fix: Status: Link: Honor value in Link field, instead of always using the Post's URL

= 1.0.1 (2025-08-14) =
* Added: Logs: Search: Search `Status Response` when performing a search
* Added: Import & Export Configuration. See Docs: https://www.socialpostflow.com/documentation/wordpress-plugin/import-export-settings/
* Added: Support link in menu
* Fix: Settings: Authentication: Correct wording to remove `enter API Key`
* Fix: Status: Yoast SEO: Facebook and Twitter Title and Description tags: Read data from post meta if not in indexable table, to ensure correct output
* Fix: Repost: Fatal error when attempting to use Repost functionality

= 1.0.0 =
* First release.

== Upgrade Notice ==

