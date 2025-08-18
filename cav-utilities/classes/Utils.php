<?php

namespace cavWP;

use cavWP\Networks\Utils as NetworksUtils;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Collection of utility methods for WordPress development.
 *
 * @since 1.0.0 Introduced.
 */
final class Utils
{
   /**
    * Calculates a standardized `mid_size` value for use with `paginate_links()`.
    *
    * Ensures that the `mid_size` is dynamically adjusted based on the desired quantity,
    * current page, and total number of pages.
    *
     * @param int $target  Desired mid_size value.
     * @param int $current Current page number.
     * @param int $total   Total number of pages.
    *
     * @return int Calculated mid_size value.
    *
    * @see Utils::paginate_links()
    * @since 1.0.0 Introduced.
    */
   public static function calc_mid_size(int $target, int $current, int $total)
   {
      if ($target <= 1) {
         return 1;
      }

      if ($target > 5 && $current > 99) {
         $target = 5;
      }

      if ($target > 3 && $current > 999) {
         $target = 3;
      }

      $mid_size = floor($target / 2);

      if ($current <= $mid_size) {
         return $target - $current;
      }

      if ($total - $mid_size < $current) {
         return $target - ($total - $current) - 1;
      }

      return $mid_size;
   }

   public static function calc_text_color($background_color)
   {
      $background_color = str_replace('#', '', $background_color);

      $r = hexdec(substr($background_color, 0, 2));
      $g = hexdec(substr($background_color, 2, 2));
      $b = hexdec(substr($background_color, 4, 2));

      $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

      return $luminance < 0.5 ? 'w' : 'b';
   }

   public static function changes_timezone($datetime, $format = 'Y-m-d H:i:s')
   {
      $date = new DateTimeImmutable($datetime, new DateTimeZone('UTC'));

      $date->setTimezone(wp_timezone());

      return $date->format($format);
   }

   /**
    * Remove the protocol from an URL.
    *
     * @param string $url
    *
     * @return string URL without protocol.
    *
    * @since 1.0.0
    */
   public static function clean_domain($url)
   {
      $domain = preg_replace('|https?://|', '', $url);

      return untrailingslashit($domain);
   }

   /**
    * Cleans a string to be use as hashtag.
    *
    * Removes  invalid characters to ensure the string can be safely used
    * as a hashtag in social media or other contexts.
    *
     * @param string $text text to be converted into a hashtag.
    *
     * @return string the cleaned hashtag text .
    *
    * @since 1.0.0 Introduced.
    */
   public static function clean_hashtag(string $text)
   {
      return preg_replace('/[^_0-9A-Za-zÀ-ÖØ-öø-ÿ]/', '', $text);
   }

   /**
    * Deletes multiple options that start with the given prefix.
    *
    * Performs a direct SQL query to delete all rows from the options table
    * where the option_name begins with the specified prefix.
    *
     * @param string $prefix Prefix that option names should start with.
    *
     * @return bool|int Number of rows affected on success; or false on error.
    *
    * @since 1.0.0 Introduced.
    */
   public static function delete_options(string $prefix)
   {
      global $wpdb;

      return $wpdb->query(
         $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $prefix . '%',
         ),
      );
   }

   /**
    * Generates a key, typically for database use.
    *
     * @param string $key
     * @param string $method Method of conversion. Accepts `raw`, `hash` or `sanitize`. Default: sanitize.
     * @param int    $length Maximum length of the key to allow. Default: 172.
    *
     * @return string The generated key.
    *
     * @throws WP_Error when no valid method is given.
     * @throws WP_Error when the key is too long.
    *
    * @since 1.0.0 Introduced.
    */
   public static function generate_key(string $key, string $method = 'sanitize', int $length = 172)
   {
      $new_key = match ($method) {
         'raw'      => $key,
         'hash'     => hash('md5', $key),
         'sanitize' => sanitize_key($key),
         default    => throw new \WP_Error('cav_generate_key_method_invalid', sprintf(esc_attr__('%s is not a valid method.', 'cav-utilities'), $length)),
      };

      if (strlen($new_key) > $length) {
         throw new \WP_Error('cav_generate_key_too_long', sprintf(esc_attr__('Key length must be %s or less.', 'cav-utilities'), $length));
      }

      return $new_key;
   }

   public static function get($key, $default = null)
   {
      if (!isset($_GET[$key])) {
         return $default;
      }

      $value = (string) $_GET[$key];

      return substr($value, 0, 35);
   }

   /**
    * Retrieves all public post types, taxonomies and the homepage.
    *
    * Depending on the specified type, returns post types, taxonomies, or both.
    * Useful for dynamically listing all content structures within the site.
    *
     * @param string $type The type of content to retrieve. Accepts `post_type`, `taxonomy`, or `all`. Default: all.
    *
     * @return string[] List of post type and/or taxonomy names.
    *
    * @since 1.0.0 Introduced.
    */
   public static function get_content_types(string $type = 'all')
   {
      $content_types['home'] = [
         'label' => __('Homepage'),
         'type'  => get_option('show_on_front'),
      ];

      $content_types['page'] = [
         'label' => __('Pages'),
         'type'  => 'pages',
      ];

      $post_types = get_post_types([
         'publicly_queryable' => true,
      ], 'objects');

      unset($post_types['attachment']);

      $post_types = array_map(fn($post_type) => [
         'label' => $post_type->label,
         'type'  => 'post_type',
      ], $post_types);

      $taxonomies = get_taxonomies([
         'public' => true,
      ], 'objects');

      unset($taxonomies['post_format']);

      $taxonomies = array_map(fn($taxonomy) => [
         'label' => $taxonomy->label,
         'type'  => 'taxonomy',
      ], $taxonomies);

      $content_types = array_merge($content_types, $post_types, $taxonomies);

      if ('all' !== $type) {
         $content_types = array_filter($content_types, fn($content_type) => $type === $content_type['type']);
      }

      return array_map(fn($content_type) => $content_type['label'], $content_types);
   }

   public static function get_current_url()
   {
      return (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
   }

   /**
    * Retrieves the date archive link based from input, current date, or current query.
    *
    * Pass an integer for explicit use; null to omit; false for current date; or true for queried date archive.
    *
     * @param bool|int      $year  Year parameter. Default: false.
     * @param null|bool|int $month Month parameter. Default: null.
     * @param null|bool|int $day   Day parameter. Default: null.
    *
     * @return string The data archive link.
    *
    * @since 1.0.0 Introduced.
    */
   public static function get_date_link(bool|int $year = false, null|bool|int $month = null, null|bool|int $day = null): string
   {
      if (true === $year) {
         $year = get_query_var('year', false);
      }

      if (true === $month) {
         $month = get_query_var('monthnum', null);
      }

      if (true === $day) {
         $day = get_query_var('day', null);
      }

      if (empty($month)) {
         return get_year_link($year);
      }

      if (empty($day)) {
         return get_month_link($year, $month);
      }

      return get_day_link($year, $month, $day);
   }

   public static function get_login_networks($keys = 'login')
   {
      $all_services = NetworksUtils::get_services($keys);

      $services = [];

      foreach ($all_services as $key => $service) {
         $client_id = get_option("cav-social_login-{$service['login']}");

         if (empty($client_id)) {
            continue;
         }

         $service['login']    = $client_id;
         $service['redirect'] = esc_attr(home_url('?cav=login&from=' . $key));
         $services[$key]      = $service;
      }

      if (get_option('cav-social_login-email')) {
         $services['email'] = $all_services['email'];
      }

      return $services;
   }

   /**
    * Get the current page, skipping 0.
    *
     * @return int the current page.
    *
    * @since 1.0.0
    */
   public static function get_page()
   {
      return max(get_query_var('paged'), 1);
   }

   /**
    * Retrieves all years in which posts of a given post type were published.
    *
    * Queries the database to find distinct years based on the post date.
    * Useful for generating archive navigation or filters.
    *
     * @param string $post_type The post type to check. Default: 'post'.
    *
     * @return int[] Array of years in descending order.
    *
    * @since 1.0.0 Introduced.
    */
   public static function get_years_published(string $post_type = 'post')
   {
      $archives = wp_get_archives([
         'type'      => 'yearly',
         'post_type' => $post_type,
         'format'    => 'none',
         'echo'      => false,
      ]);
      $archives = strip_tags($archives);
      $archives = explode("\t", $archives);
      $archives = array_map('trim', $archives);

      return array_filter($archives);
   }

   /**
    * Creates a directory.
    *
     * @param array|string $path The path for the new directory; or an array of directories to resolve.
    *
     * @return bool|string The path of the new directory on success, or false on failure.
    *
    * @since 1.0.0 Introduced.
    */
   public static function make_dir(array|string $path)
   {
      if (!is_array($path)) {
         $path = [$path];
      }

      $folder = self::path_resolve($path);

      if (!file_exists($folder)) {
         $chmod_dir = 0o755;

         if (defined('FS_CHMOD_DIR')) {
            $chmod_dir = FS_CHMOD_DIR;
         }

         mkdir($folder, $chmod_dir, true);
      }

      if (is_dir($folder) && is_writable($folder)) {
         return $folder;
      }

      return false;
   }

   public static function maybe_image($attachment_ID, $size, $attrs)
   {
      if ('attachment' !== get_post_type($attachment_ID)) {
         return false;
      }

      return wp_get_attachment_image($attachment_ID, $size, false, $attrs);
   }

   public static function number_suffix(int $number)
   {
      return match (substr((string) $number, -1)) {
         '1'     => 'st',
         '2'     => 'nd',
         '3'     => 'rd',
         default => 'th',
      };
   }

   /**
    * A revision of paginate_links() with a standardized `mid_size` value.
    *
     * @param array $args    Arguments for paginate_links(). Default: [].
     * @param array $classes Array of CSS classes to replace in pagination elements. Default: [].
    *
     * @return string[] List of pagination link HTML strings.
    *
    * @see https://developer.wordpress.org/reference/functions/paginate_links/
    * @since 1.0.0 Introduced.
    */
   public static function paginate_links(array $args = [], array $classes = [])
   {
      global $wp_query;

      $defaults = [
         'current'            => max(1, get_query_var('paged')),
         'type'               => 'array',
         'before_page_number' => '',
         'prev_next'          => true,
         'mid_size'           => 7,
         'total'              => $wp_query->max_num_pages,
         'max_total'          => 100,
      ];

      $args = wp_parse_args($args, $defaults);

      $args['total'] = $args['total'] <= $args['max_total'] || $args['current'] >= $args['max_total'] ? $args['total'] : $args['max_total'];

      $args['mid_size'] = self::calc_mid_size($args['mid_size'], $args['current'], $args['total']);

      $paginate_links = paginate_links($args);

      if (empty($paginate_links)) {
         return [];
      }

      foreach ($classes as $search => $replace) {
         foreach ($paginate_links as $key => $paginate_link) {
            $paginate_links[$key] = str_replace($search, $replace, $paginate_link);
         }
      }

      return $paginate_links;
   }

   /**
    * Resolves an array of directories using the correct directory separator for the current operating system.
    *
     * @param array $paths Array of directories names to be resolved.
    *
     * @return string The resolved full path.
    *
    * @since 1.0.0 Introduced.
    */
   public static function path_resolve(array $paths)
   {
      return implode(DIRECTORY_SEPARATOR, $paths);
   }

   /**
    * Clean cache from a URL.
    *
     * @param string $page
    *
     * @return void
    *
    * @since 1.0.0
    */
   public static function purge_page_cache(string $page): void
   {
      global $nginx_purger;

      if (isset($nginx_purger)) {
         $nginx_purger->purge_url(home_url($page));
      }
   }

   /**
    * Regenerates all image sizes for a given attachment.
    *
    * Useful for regenerating specific or all image sizes after changing image dimensions
    * or adding new sizes via `add_image_size()`. Optionally, specific sizes can be skipped.
    *
     * @param int   $attachment_id The ID of the attachment.
     * @param array $skip_sizes    Array of image size names to skip. Default: [].
    *
     * @return bool|void void on failure. true on success.
    *
    * @since 1.0.0 Introduced.
    */
   public static function regenerate_image_sizes(int $attachment_id, array $skip_sizes = [])
   {
      $file = \get_attached_file($attachment_id);

      if (empty($file) || !file_exists($file)) {
         return;
      }

      $image_editor = \wp_get_image_editor($file);

      if (\is_wp_error($image_editor)) {
         return;
      }

      $target_sizes  = \wp_get_registered_image_subsizes();
      $current_sizes = \wp_get_attachment_metadata($attachment_id);
      $compare       = !empty($current_sizes);

      foreach ($target_sizes as $size_name => $size) {
         if (in_array($size_name, $skip_sizes)) {
            continue;
         }

         if ($compare) {
            if (
               $current_sizes['width'] <= $size['width'] && $current_sizes['height'] <= $size['height']) {
               continue;
            }

            if (
               isset($current_sizes['sizes'][$size_name]) && $current_sizes['sizes'][$size_name]['width'] === $size['width'] && $current_sizes['sizes'][$size_name]['height'] === $size['height']) {
               continue;
            }
         }

         $image_editor->resize($size['width'], $size['height'], $size['crop']);

         $resized = $image_editor->save();

         if (!is_wp_error($resized)) {
            $current_sizes['sizes'][$size_name] = [
               'file'      => wp_basename($resized['path']),
               'width'     => $resized['width'],
               'height'    => $resized['height'],
               'mime-type' => $resized['mime-type'],
            ];
         }

         $image_editor->load($file);
      }

      wp_update_attachment_metadata($attachment_id, $current_sizes);

      return true;
   }

   /**
    * Render or return the content of a SVG.
    *
     * @param string $file  Path to the SVG.
     * @param mixed  $class
     * @param bool   $echo
    *
     * @return void
    *
    * @since 1.0.0
    */
   public static function render_svg($file, $class = '', $echo = true)
   {
      if (!file_exists($file)) {
         return;
      }

      $content = file_get_contents($file);

      if (!empty($class)) {
         if (str_contains($content, 'class="')) {
            $content = str_replace('class="', 'class="' . $class . ' ', $content);
         } else {
            $content = str_replace('<svg ', '<svg class="' . $class . '" ', $content);
         }
      }

      if (!$echo) {
         return $content;
      }
      echo $content;
   }

   /**
    * Registers a scheduled event to run only once. The key is based in the hook name and serialized arguments.
    *
     * @param int    $timestamp unix timestamp (UTC) for when to run the event.
     * @param string $hook      Action hook to execute when the event is run.
     * @param array  $args      Arguments to pass to the hook's callback. Each value in passed as an individual parameter. Keys are ignored. Default: [].
     * @param bool   $wp_error  Whether to return a WP_Error on failure. Default: false.
    *
     * @return bool|WP_Error True if event successfully scheduled. False or WP_Error on failure.
    *
    * @since 1.0.0 Introduced.
    */
   public static function schedule_once_event(int $timestamp, string $hook, array $args = [], bool $wp_error = false)
   {
      $events    = get_option('cav-schedule_once_events_ran', []);
      $event_key = $hook . md5(serialize($args));

      if (wp_next_scheduled($hook, $args) || in_array($event_key, $events)) {
         return false;
      }

      $scheduled = wp_schedule_single_event($timestamp, $hook, $args, $wp_error);

      if (false === $scheduled || is_wp_error($scheduled)) {
         return $scheduled;
      }

      $events[] = $event_key;

      update_option('cav-schedule_once_events_ran', $events, 'no');

      return $scheduled;
   }

   /**
    * Identifies if a URL is from a self hosted file and returns his path on the server.
    *
     * @param string $url The full URL of the asset.
    *
     * @return bool|string The full path on success, or false on failure or for external assets.
    *
    * @since 1.0.0 Introduced.
    */
   public static function url_to_path(string $url)
   {
      if (str_starts_with($url, content_url('cache'))) {
         return false;
      }

      if (str_starts_with($url, get_bloginfo('wpurl'))) {
         $parsed   = parse_url(str_replace(get_bloginfo('wpurl'), ABSPATH, $url));
         $filepath = $parsed['path'];

         if (isset($parsed['scheme'])) {
            $filepath = $parsed['scheme'] . ':' . $filepath;
         }
      }

      if (str_starts_with($url, '/')) {
         $filepath = ABSPATH . substr($url, 1);
      }

      if (isset($filepath)) {
         $filepath = str_replace('/', DIRECTORY_SEPARATOR, $filepath);

         if (file_exists($filepath)) {
            return $filepath;
         }
      }

      return false;
   }
}
