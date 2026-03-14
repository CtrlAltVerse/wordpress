<?php

namespace cavWP\Caches;

use cavWP\Utils;

class _Hooks
{
   public function __construct()
   {
      if (!wp_next_scheduled('cav_caches_disk_clean')) {
         wp_schedule_event(strtotime('tomorrow 00:01'), 'daily', 'cav_caches_disk_clean');
      }
      add_action('cav_caches_disk_clean', [$this, 'trigger_clean_disk']);
   }

   public function trigger_clean_disk()
   {
      $path   = apply_filters('cav_caches_disk_path', [WP_CONTENT_DIR, 'cache', 'cav_cache']);
      $folder = Utils::path_resolve($path);

      if (!is_dir($folder) || !is_writable($folder)) {
         return;
      }

      $yesterday = date('Y-m-d', strtotime('-1 day'));

      $found = glob($folder . DIRECTORY_SEPARATOR . '*.' . $yesterday . '.cav', GLOB_NOSORT);

      foreach ($found as $file) {
         @unlink($file);
      }
   }
}
