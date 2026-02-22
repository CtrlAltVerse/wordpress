<?php

namespace cavWP;

/*
 * Plugin Name: CAV Utilities
 * Plugin URI: https://plugins.altvers.net/wp/
 * Description: All-in-one tool with resources for SEO, performance enhancement and more.
 * Version: 1.0.0
 * Author: CtrlAltVersœ
 * Author URI: https://ctrl.altvers.net/
 * Requires at least:
 * Requires PHP: 8.0
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: cav-utilities
 * Domain Path: /languages
 */

/**
 * @ignore
 */
define('CAV_WP_FILE', __FILE__);

/**
 * @ignore
 */
define('CAV_WP_DIR', plugin_dir_path(CAV_WP_FILE));

/**
 * @ignore
 */
define('CAV_WP_URL', plugin_dir_url(CAV_WP_FILE));

if (!defined('CURRENT_IP')) {
   define('CURRENT_IP', $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR']);
}

require 'functions.php';

$AutoLoader = \cav_autoloader();
$AutoLoader->add_namespace('cavWP', implode(DIRECTORY_SEPARATOR, [CAV_WP_DIR, 'classes']));

/**
 * @ignore
 */
final class cavWP
{
   public function __construct()
   {
      add_action('init', [$this, 'block_ips'], 8);
      add_action('init', [$this, 'init_hook'], 9);
      add_action('wp', [$this, 'load_parse_content']);

      new Dashboard\Plugin_Options();
      new Theme\Plugin_Options();
      new General\Hooks();
      new Dashboard\Hooks();
      new Theme\Hooks();
   }

   public function block_ips(): void
   {
      if (str_starts_with(CURRENT_IP, '47.82.60')) {
         wp_die('Blocked.');
      }
   }

   public function init_hook(): void
   {
      \add_rewrite_tag('%cav%', '([a-z-]{12})');

      new Networks\Register();
      new Admin_Page();
      new Register_Assets();
      new Reorder_Head_Hooks();
      new Misc();
      new Menu();
      new Columns();

      if (get_option('cav-seo_links')) {
         new SEO_Links\Register();
         new SEO_Links\Admin_Page();
      }

      if (get_option('cav-activity_log')) {
         new Activity_Log\Register();
         new Activity_Log\Admin_Page();
      }

      if (get_option('cav-health_check')) {
         new Health_Check();
      }

      if (get_option('cav-metatags')) {
         new Metatags();
      }

      if (get_option('cav-metatags-manifest')) {
         new ManifestJson();
      }

      if (get_option('cav-sitemaps-google_news')) {
         new Sitemap_Google_News();
      }

      if (get_option('cav-opensearch')) {
         new OpenSearch();
      }

      if (get_option('cav-caches-get_requests') && !is_admin()) {
         new Cache_Requests();
      }

      if (get_option('cav-autoshare')) {
         new Social_Share();
      }

      if (get_option('cav-links') && is_numeric(get_option('cav-links-menu'))) {
         new LinksPage();
      }

      $ads_txt = get_option('cav-sitemaps-adds_ads_txt');

      if (!empty($ads_txt)) {
         new Ads_txt($ads_txt);
      }
   }

   public function load_parse_content(): void
   {
      $cav_template = get_query_var('cav', false);

      if (!is_admin() && !is_login() && !is_robots() && !is_feed() || in_array($cav_template, ['links'])) {
         new Parse_HTML();
      }
   }
}

new cavWP();
