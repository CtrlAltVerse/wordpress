<?php

namespace cavWP;

use WP_Error;

/**
 * Handles cached data storage in files.
 */
final class DiskCache
{
   /** @ignore */
   private $filename;

   /**
    * Initiate a cache file handler.
    *
     * @param string $key The name for the cache file.
    *
     * @return void
    *
     * @throws WP_Error When the cache directory can't be created.
    */
   public function __construct(string $key)
   {
      /**
       * Filter the cache directory path.
       *
       * @param string[] $path Directories to  resolve as the path.
       *
       * @since 1.0.0 Introduced.
       */
      $path   = apply_filters('cav_cache_disk_path', [WP_CONTENT_DIR, 'cache', 'cav_cache']);
      $folder = Utils::make_dir($path);

      if (false === $folder) {
         throw new WP_Error('cav_cache_disk_folder_not_created');
      }

      $folder .= DIRECTORY_SEPARATOR;

      $this->filename = $folder . sanitize_file_name($key) . '.cache';
   }

   /**
    * Deletes the cache file previous created, if it still exists.
    *
     * @return bool Returns `true` on success; or `false` on failure
    */
   public function del()
   {
      if (file_exists($this->filename)) {
         if (is_readable($this->filename)) {
            return unlink($this->filename);
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
    */
   public function get(bool $only_content = true)
   {
      if (file_exists($this->filename) && is_readable($this->filename)) {
         $file_content = unserialize(file_get_contents($this->filename));

         if ($file_content['expires'] > time()) {
            if ($only_content) {
               return $file_content['content'];
            }

            return $file_content;
         }

         unlink($this->filename);
      }

      return false;
   }

   /**
    * Creates or replaces the cache file.
    *
     * @param mixed      $data     Data to store
     * @param int|string $duration Period of time in timestamp or string compatible with `strtotime` to keep the data. Default: 1 day.
    *
     * @return bool|int Returns the number of bytes that were store, or false on failure
    */
   public function set(mixed $data, int|string $duration = '1 day')
   {
      $expires = is_int($duration) ? $duration : strtotime($duration);

      if (false === $expires) {
         throw new WP_Error('cav_cache_disk_duration_invalid');
      }

      $cached_data = serialize([
         'expires' => $expires,
         'content' => $data,
      ]);

      return file_put_contents($this->filename, $cached_data);
   }
}
