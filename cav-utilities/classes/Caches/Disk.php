<?php

namespace cavWP\Caches;

use cavWP\Utils;
use WP_Error;

/**
 * Handles cached data storage in files.
 */
final class Disk
{
   /** @ignore */
   private $filename;

   /** @ignore */
   private $folder;

   /**
    * Initiate a cache file handler.
    *
    * IMPORTANT: If using this class, keep wp-cron running at least daily, or configure it in the system crontab (with the correct path to the cache folder):
    * 59 23 * * * find /var/www/htdocs/wp-content/cache/cav-cache/ -type f -name "*.$(date +%F).cav" -delete 2>/dev/null
    *
     * @param string $key The name for the cache file.
    *
     * @return void
    *
     * @throws WP_Error When the cache directory can't be created.
    *
    * @since 1.0.0 Introduced.
    */
   public function __construct(string $key)
   {
      /**
       * Filter the cache directory path.
       *
       * @param string[] $path Directories to resolve as the path.
       *
       * @since 1.0.0 Introduced.
       */
      $path   = apply_filters('cav_caches_disk_path', [WP_CONTENT_DIR, 'cache', 'cav-cache']);
      $folder = Utils::make_dir($path);

      if (false === $folder) {
         throw new WP_Error('cav_caches_disk_folder_not_created');
      }

      $this->folder   = $folder . DIRECTORY_SEPARATOR;
      $this->filename = sanitize_file_name($key);
   }

   /**
    * Deletes the cache file previous created, if it still exists.
    *
     * @return bool Returns `true` on success; or `false` on failure
    *
    * @since 1.0.0 Introduced.
    */
   public function del()
   {
      $filename = $this->_find_file();

      if ($filename) {
         if (is_readable($filename)) {
            return unlink($filename);
         }

         return false;
      }

      return true;
   }

   /**
    * Checks and gets the cache file content previous stored.
    *
     * @param bool $only_content If set to false, returns an array with `content` and `expires`. Default: true.
    *
     * @return array|bool|mixed returns false if not found or expired, or the file content
    *
    * @since 1.0.0 Introduced.
    */
   public function get(bool $only_content = true)
   {
      $filename = $this->_find_file();

      if (is_readable($filename)) {
         $file_content = unserialize(file_get_contents($filename));

         if ($file_content['expires'] > time()) {
            if ($only_content) {
               return $file_content['content'];
            }

            return $file_content;
         }

         unlink($filename);
      }

      return false;
   }

   /**
    * Creates the cache file.
    *
     * @param mixed      $data     Data to store
     * @param int|string $duration Period of time in timestamp or string compatible with `strtotime` to keep the data. Default: 1 day.
    *
     * @return bool|int Returns the number of bytes that were store, or false on failure
    *
    * @since 1.0.0 Introduced.
    */
   public function set(mixed $data, int|string $duration = '1 day')
   {
      $expires = is_int($duration) ? $duration : strtotime($duration);

      if (false === $expires) {
         throw new WP_Error('cav_caches_disk_duration_invalid');
      }

      $cached_data = serialize([
         'expires' => $expires,
         'content' => $data,
      ]);

      return file_put_contents($this->folder . $this->filename . '.' . date('Y-m-d', $expires) . '.cav', $cached_data);
   }

   /**
    * @ignore
    */
   private function _find_file()
   {
      $found = glob($this->folder . $this->filename . '.????-??-??.cav', GLOB_NOSORT);

      if (empty($found)) {
         return false;
      }

      return $found[0];
   }
}
