<?php

namespace cavWP;

final class After_Theme
{
   public function __construct()
   {
      if (\get_option('cav-theme-add_supports')) {
         \add_action('after_setup_theme', [$this, 'add_supports']);
      }

      if (get_option('cav-theme-title_tag')) {
         \add_action('after_setup_theme', [$this, 'add_title_tag']);
      }

      if (\get_option('cav-dashboard-hide_bar')) {
         \add_action('after_setup_theme', [$this, 'hide_admin_bar']);
      }

      if (\get_option('cav-theme-remove_tags')) {
         \add_action('after_setup_theme', [$this, 'remove_hooks']);
      }
   }

   public function add_supports(): void
   {
      add_theme_support('custom-background');
      add_theme_support('custom-logo');
      add_theme_support('dark-editor-style');
      add_theme_support('post-thumbnails');
      add_theme_support('responsive-embed');
      add_theme_support('menus');
      add_theme_support('html5', [
         'comment-form',
         'comment-list',
         'search-form',
         'gallery',
         'caption',
         'script',
         'style',
      ]);
   }

   public function add_title_tag(): void
   {
      add_theme_support('title-tag');
   }

   public function hide_admin_bar(): void
   {
      show_admin_bar(false);
   }

   public function remove_hooks(): void
   {
      /*
       * Removes the link to the Really Simple Discovery service endpoint.
       * <link rel="EditURI" type="application/rsd+xml" title="RSD" href="xmlrpc.php?rsd">
       */
      remove_action('wp_head', 'rsd_link');

      /*
       * Removes the XHTML generator that is generated on the wp_head hook.
       * <meta name="generator" content="WordPress X.XX">
       */
      remove_action('wp_head', 'wp_generator');

      /*
       * Remove the REST API link tag into page header.
       * <link rel="https://api.w.org/" href="*">
       * <link rel="alternate" type="application/json" href="*">
       */
      remove_action('wp_head', 'rest_output_link_wp_head');

      /*
       * Removes the links to the general feeds (RSS).
       * <link rel="alternate" type="*" title="*" href="*">
       */
      remove_action('wp_head', 'feed_links', 2);

      /*
       * Removes the links to the extra feeds (RSS) such as category feeds.
       * <link rel="alternate" type="*" title="*" href="*">
       */
      remove_action('wp_head', 'feed_links_extra', 3);

      /*
       * Removes the link to the Windows Live Writer manifest file.
       * <link rel="wlwmanifest" type="application/wlwmanifest+xml" href="wlwmanifest.xml">
       */
      remove_action('wp_head', 'wlwmanifest_link');

      // // remove the next and previous post links
      remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0);
      remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);

      /*
       * Removes rel=shortlink from the head if a shortlink is defined for the current page.
       * <link rel='shortlink' href='*'>
       */
      remove_action('wp_head', 'wp_shortlink_wp_head');

      /*
       * Removes the inline Emoji detection script.
       * <script>*</script>
       */
      remove_action('wp_head', 'print_emoji_detection_script', 7);
      remove_action('admin_print_scripts', 'print_emoji_detection_script');
      remove_action('embed_head', 'print_emoji_detection_script');

      /*
       * Remove the emoji-related styles.
       * <style>*</style>
       */
      remove_action('wp_print_styles', 'print_emoji_styles');
      remove_action('admin_print_styles', 'print_emoji_styles');

      // Remove emoji to img convertor in feed, comments and e-mail.
      remove_filter('the_content_feed', 'wp_staticize_emoji');
      remove_filter('comment_text_rss', 'wp_staticize_emoji');
      remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

      /*
       * Removes oEmbed discovery links in the website.
       * <link rel="alternate" type="application/json+oembed" href="*">
       * <link rel="alternate" type="text/xml+oembed" href="*">
       */
      remove_action('wp_head', 'wp_oembed_add_discovery_links');

      /*
       * Removes the necessary JavaScript to communicate with the embedded iframe.
       * Remove oEmbed-specific JavaScript from the front-end and back-end.
       */
      remove_action('wp_head', 'wp_oembed_add_host_js');

      // Turn off inspect the given URL for discoverable link tags.
      add_filter('embed_oembed_discover', '__return_false');

      // Disables JSONP for the REST API.
      add_filter('rest_jsonp_enabled', '__return_false');

      // Disables XMLRPC
      add_filter('xmlrpc_enabled', '__return_false');
      add_filter('xmlrpc_methods', '__return_empty_array');

      // Disable Link header for the REST API.
      remove_action('template_redirect', 'rest_output_link_header', 11, 0);

      /*
       * Disable alias redirects to /wp-admin and /wp-login.
       *
       * /admin != /wp-admin
       * /dashboard != /wp-admin
       * /login != /wp-login.php
       */
      remove_action('template_redirect', 'wp_redirect_admin_locations', 1000);
   }
}
