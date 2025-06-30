<?php

namespace cavWP;

/*
 * Plugin Name: CAV WP Plugin
 * Plugin URI: https://plugins.altvers.net/wp/
 * Description: All-in-one tool with resources for SEO and performance enhancement and more.
 * Version: 1.0.0
 * Author: CtrlAltVerso
 * Author URI: https://ctrl.altvers.net/
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
      add_action('init', [$this, 'init_hook'], 9);
      add_action('wp', [$this, 'load_parse_content']);

      $this->load_classes();
   }

   public function init_hook(): void
   {
      add_rewrite_tag('%cav%', '([a-z-]{12})');
   }

   public function load_classes(): void
   {
      new Register_Assets();
      new Admin_Page();
      new Reorder_Head_Hooks();
      new Misc();
      new Menu();

      if (get_option('cav-seo_links')) {
         new SEO_Links\Register();
         new SEO_Links\Admin_Page();
      }

      if (get_option('cav-health_check')) {
         new Health_Check();
      }

      if (get_option('cav-metatags')) {
         new Metatags();
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
