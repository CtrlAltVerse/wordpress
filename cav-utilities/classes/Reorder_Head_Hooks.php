<?php

namespace cavWP;

/**
 * Changes the order of `wp_head` hooks. To include a specific content, use one of the follows:
 *
 * `wp_preload_resources` (wp filter) [order: 5]
 * Preloads metas.
 *
 * `wp_resource_hints` (wp filter) [order: 6]
 * Resource hints metas to browsers: dns-prefetch, preconnect, prefetch, prerender.
 *
 * `cav_head_metas` (custom action) [order: 10]
 * SEO, social, and others metas info tag.
 *
 * `wp_get_custom_css` (wp filter) [order: 10]
 * Inline CSS styles (if can't be done with `wp_add_inline_style`):
 *
 * `wp_print_styles` (wp action) [order: 15]
 * External CSS links (if can't be done with `wp_enqueue_style`):
 *
 * `cav_head_scripts` (custom action) [order: 20]
 * Script tags (if can't be done with `wp_enqueue_script`):
 */

/**
 * @ignore
 */
final class Reorder_Head_Hooks
{
   /**
    * Initiate class.
    */
   public function __construct()
   {
      /**
       * Allows choose which defaults hooks will not be reorder.
       *
       * Accepts 'title', 'preload', 'resource', 'styles', 'scripts' and 'feeds'.
       */
      $not_reorder = apply_filters('cav_head_hooks_not_reorder', []);

      if (!in_array('title', $not_reorder) && !$this->has_yoast()) {
         \remove_action('wp_head', '_wp_render_title_tag', 1);

         \add_action('wp_head', '_wp_render_title_tag', 9);
      }

      if (!in_array('preload', $not_reorder)) {
         remove_action('wp_head', 'wp_preload_resources', 1);

         add_action('wp_head', 'wp_preload_resources', 5);
      }

      if (!in_array('styles', $not_reorder)) {
         remove_action('wp_head', 'wp_print_styles', 8);
         add_action('wp_head', 'wp_print_styles', 15);

         remove_action('wp_head', 'locale_stylesheet');
         add_action('wp_head', 'locale_stylesheet', 16);
      }

      if (!in_array('resource', $not_reorder)) {
         remove_action('wp_head', 'wp_resource_hints', 2);

         add_action('wp_head', 'wp_resource_hints', 6);
      }

      if (!in_array('scripts', $not_reorder)) {
         remove_action('wp_head', 'wp_print_head_scripts', 9);

         add_action('wp_head', 'wp_print_head_scripts', 20);
      }

      if (!in_array('feeds', $not_reorder)) {
         remove_action('wp_head', 'feed_links', 2);
         remove_action('wp_head', 'feed_links_extra', 3);

         add_action('wp_head', 'feed_links', 10);
         add_action('wp_head', 'feed_links_extra', 10);
      }

      add_action('wp_head', '_cav_prints_meta_charset', 1);
      add_action('wp_head', [$this, 'prints_metas'], 10);
      add_action('wp_head', [$this, 'prints_scripts'], 20);
   }

   public function prints_metas(): void
   {
      do_action('cav_head_metas');
   }

   public function prints_scripts(): void
   {
      do_action('cav_head_scripts');
   }

   private function has_yoast()
   {
      return
         is_plugin_active('wordpress-seo/wp-seo.php')
         || is_plugin_active('wordpress-seo-premium/wp-seo-premium.php');
   }
}
