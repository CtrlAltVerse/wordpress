<?php

namespace cavWP\SEO_Links;

class Utils
{
   public static function decimal_to_percent($decimal)
   {
      if (!is_numeric($decimal)) {
         return 0;
      }

      return number_format($decimal * 100);
   }

   public static function get_all_links()
   {
      $urls        = get_option('cav-seo_links-unique_urls', []);
      $custom_urls = get_option('cav-seo_links-custom_urls', []);

      if ('' === $custom_urls) {
         $custom_urls = [];
      }

      if (!empty($custom_urls)) {
         $custom_urls_array = preg_split('/\r\n|\r|\n/', $custom_urls);
         $custom_urls       = [];
         $unique_urls       = array_map(fn($link) => $link['url'], $urls);

         foreach ($custom_urls_array as $custom_url) {
            $custom_url = trim($custom_url);

            if (!str_starts_with($custom_url, home_url())) {
               continue;
            }

            if (in_array($custom_url, $unique_urls)) {
               continue;
            }

            $custom_urls[] = [
               'url'  => $custom_url,
               'file' => '',
               'type' => 'custom',
            ];
         }
      }

      return array_merge($urls, $custom_urls);
   }

   public static function get_reports()
   {
      $links = self::get_all_links();
      $urls  = array_map(fn($link) => $link['url'], $links);
      $urls  = "('" . implode("', '", $urls) . "')";

      global $wpdb;
      $table = self::get_table_name($wpdb->prefix);

      $reports = $wpdb->get_results("SELECT * FROM {$table} WHERE `url` IN {$urls} ORDER BY datetime DESC;", ARRAY_A);

      if (empty($reports)) {
         return [];
      }

      $links = array_map(function($link) use ($reports) {
         $url_reports = array_filter($reports, fn($report) => $report['url'] === $link['url']);

         if (!empty($url_reports)) {
            $report           = current($url_reports);
            $report['report'] = unserialize($report['report']);
            $link             = array_merge($link, $report);
         }

         return $link;
      }, $links);

      return $links;
   }

   public static function get_table_name($prefix): string
   {
      return $prefix . 'cav_seo_links_analysis';
   }

   public static function parse_audit($audit)
   {
      if (empty($audit['details']['items'])) {
         return '';
      }

      $return = [];

      foreach ($audit['details']['items'] as $item) {
         $return[] = $item['url'] ?? $item['node']['snippet'];
      }

      // scoreDisplayMode=
      // numeric
      // metricSavings
      // binary
      return implode(PHP_EOL, $return);
   }

   public static function parse_md_link($text)
   {
      return preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', "<a href='$2' target='_blank'>$1</a>", $text);
   }

   public static function save_report($url, $report)
   {
      global $wpdb;

      $table = self::get_table_name($wpdb->prefix);

      $reports = $wpdb->get_results(
         "SELECT analysis_ID FROM {$table} WHERE `url` = '{$url}' ORDER BY `datetime` ASC",
         ARRAY_A,
      );

      if (count($reports) > 1) {
         $wpdb->delete($table, [
            'analysis_ID' => $reports[0]['analysis_ID'],
         ]);
      }

      $entry           = $report;
      $entry['url']    = $url;
      $entry['report'] = serialize($report['report']);

      $success = $wpdb->insert($table, $entry);

      if (false === $success) {
         return $success;
      }

      do_action('cav_seo_links_analysis_added', $url, $report);

      return $report;
   }
}
