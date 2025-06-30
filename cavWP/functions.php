<?php

use cavWP\AutoLoader;
use cavWP\Utils;
use Latte\Engine;

if (!function_exists('debug')) {
   /**
    * Prints to the error_log file and/or displays on screen all passed variables.
    *
    * ##### Description
    *
    * - If `WP_DEBUG` is false, it does nothing.
    * - If `WP_DEBUG_LOG` is false, it does not log to the error_log.
    * - If `WP_DEBUG_DISPLAY` is false, it does not print on screen.
    *
    * ##### Usage
    *
    * ```php
    * debug($wp_query);
    * add_action('edit_comment', 'debug', 10, 2);
    * add_filter('the_content', 'debug', 10);
    * ```
    *
     * @param mixed $arguments Variable(s) for debugging.
    *
     * @return mixed The first argument passed.
    *
    * @since 1.0.0 Introduced.
    */
   function debug(mixed ...$arguments)
   {
      $args = func_get_args();

      if (!WP_DEBUG) {
         return $args[0];
      }

      $id    = 'DEBUG';
      $files = debug_backtrace();
      $log   = "\n[{$id}] {$files[0]['file']}:{$files[0]['line']}";

      foreach ($args as $key => $arg) {
         $key = str_pad($key, 3, '0', STR_PAD_LEFT);

         $log .= "\n[ {$key} ] ";
         $log .= var_export($arg, 1);
      }

      if (WP_DEBUG_LOG) {
         error_log($log);
      }

      if (
         WP_DEBUG_DISPLAY
         && !defined('XMLRPC_REQUEST')
         && !defined('REST_REQUEST')
         && !defined('MS_FILES_REQUEST')
         && !(defined('WP_INSTALLING') && WP_INSTALLING)
         && !wp_doing_ajax()
         && !wp_is_json_request()
      ) {
         print_r("<pre>{$log}</pre>");
      }

      return $args[0];
   }
}

if (!function_exists('cav_autoloader')) {
   /**
    * Loads and returns the global AutoLoaded class.
    *
     * @return cavWP\AutoLoader
    *
    * @since 1.0.0 Introduced.
    */
   function cav_autoloader()
   {
      global $cav_AutoLoader;

      if ($cav_AutoLoader instanceof AutoLoader) {
         return $cav_AutoLoader;
      }

      $class_path = implode(DIRECTORY_SEPARATOR, [CAV_WP_DIR, 'classes', 'AutoLoader.php']);

      if (!file_exists($class_path)) {
         return new \WP_Error('cav_autoloader_missing', esc_attr__('AutoLoader was not found.', 'cavwp'));
      }

      require_once $class_path;

      $cav_AutoLoader = new AutoLoader();

      return $cav_AutoLoader;
   }
}

if (!function_exists('get_component')) {
   /**
    * Loads a template from the /components directory in the theme.
    *
    * ##### Usage
    *
    * ```php
    * get_component('header');
    * # follows this order:
    * # components/header/_index.php
    * # components/header.php
    *
    * get_component(['header', 'menu']);
    * # follows this order:
    * # components/header/components/menu/_index.php
    * # components/header/components/menu.php
    * ```
    *
     * @param string|string[] $template_name Component name or array of sub-component names.
     * @param mixed[]         $args          Arguments passed to the template. Default: [].
     * @param bool            $once          To use `require_once` or `require`. Default: false.
    *
     * @return bool|void Void if the template is loaded; false if the template was not found.
    *
    * @since 1.0.0 Introduced.
    */
   function get_component(array|string $template_name, array $args = [], bool $once = false)
   {
      if (is_array($template_name)) {
         $template_name = implode('/components/', $template_name);
      }

      $templates[] = "components/{$template_name}/_index.php";
      $templates[] = "components/{$template_name}.php";

      if (!locate_template($templates, true, $once, $args)) {
         return false;
      }
   }
}

if (!function_exists('get_page_component')) {
   /**
    * Loads a component from the /pages directory in the theme.
    *
    * ##### Example
    *
    * ```php
    * get_page_component('home', 'header');
    * # follows this order:
    * # pages/home/components/header/_index.php
    * # pages/home/components/header.php
    * ```
    *
     * @param string          $page Page name.
     * @param string|string[] $slug Component name or array of sub-component names.
     * @param mixed[]         $args Arguments passed to the template. Default: [].
     * @param bool            $once To use `require_once` or `require`. Default: false.
    *
     * @return bool|void Void if the template is loaded; false if the template was not found.
    *
    * @since 1.0.0 Introduced.
    */
   function get_page_component(string $page, array|string $slug, array $args = [], bool $once = false)
   {
      if (is_array($slug)) {
         $slug = implode('/components/', $slug);
      }

      $templates[] = "pages/{$page}/components/{$slug}/_index.php";
      $templates[] = "pages/{$page}/components/{$slug}.php";

      if (!locate_template($templates, true, $once, $args)) {
         return false;
      }
   }
}

if (!function_exists('render_latte')) {
   /**
    * Displays or returns a latte template.
    *
     * @param string         $template_path Path to the latte template file.
     * @param mixed[]|object $args          Arguments passed to the template. Default: [].
     * @param bool           $echo          Whether to echo the template or return it. Default: true.
    *
     * @return bool|string|void Void if $echo argument is true; template output if $echo is false; false if the template was not found.
    *
     * @throws WP_Error When the latte cache directory can't be created.
    *
    * @since 1.0.0 Introduced.
    */
   function render_latte(string $template_path, array|object $args = [], bool $echo = true)
   {
      if (!file_exists($template_path)) {
         return false;
      }

      require Utils::path_resolve([CAV_WP_DIR, 'vendor', 'autoload.php']);

      $cache_path = Utils::path_resolve([WP_CONTENT_DIR, 'cache', 'latte']);
      $cache_path = Utils::make_dir($cache_path);

      if (false === $cache_path) {
         throw new \WP_Error('cav_latte_cache_path_not_created', esc_attr__('Directory for Latte Cache could not be created.', 'cavwp'));
      }

      $latte = new Engine();
      $latte->setTempDirectory($cache_path);

      if (is_environment('production')) {
         $latte->setAutoRefresh(false);
      }

      if ($echo) {
         $latte->render($template_path, $args);
      } else {
         return $latte->renderToString($template_path, $args);
      }
   }
}

if (!function_exists('latte_component')) {
   /**
    * Loads a latte template from the /components directory in the theme.
    *
     * @param string|string[] $template_name The latte template filename, with sub-components if needed.
     * @param mixed[]|object  $args          Arguments passed to the template. Default: [].
     * @param bool            $echo          Whether to echo the template or return it. Default: true.
    *
     * @return bool|string|void Void if $echo argument is true; template output if $echo is false; false if the template was not found.
    *
     * @throws WP_Error When the latte cache directory can't be created.
    *
    * @since 1.0.0 Introduced.
    */
   function latte_component(array|string $template_name, array|object $args = [], bool $echo = true)
   {
      if (is_array($template_name)) {
         $template_name = implode('/components/', $template_name);
      }

      $templates[] = "components/{$template_name}/_index.latte";
      $templates[] = "components/{$template_name}.latte";

      foreach ($templates as $template) {
         $theme_file = get_theme_file_path($template);

         if (file_exists($theme_file)) {
            return render_latte($theme_file, $args, $echo);
         }
      }

      return false;
   }
}

if (!function_exists('latte_page_component')) {
   /**
    * Loads a latte component from the /pages directory in the theme.
    *
     * @param string          $page          Page name.
     * @param string|string[] $template_name The latte template filename, with sub-components if needed.
     * @param mixed           $args          Arguments passed to the template. Default: [].
     * @param mixed           $echo          Whether to echo the template or return it. Default: true.
    *
     * @return bool|string|void Void if $echo argument is true; template output if $echo is false; false if the template was not found.
    *
     * @throws WP_Error When the latte cache directory can't be created.
    *
    * @since 1.0.0 Introduced.
    */
   function latte_page_component(string $page, array|string $template_name, array|object $args = [], $echo = true)
   {
      if (is_array($template_name)) {
         $template_name = implode('/components/', $template_name);
      }

      $templates[] = "pages/{$page}/components/{$template_name}/_index.latte";
      $templates[] = "pages/{$page}/components/{$template_name}.latte";

      foreach ($templates as $template) {
         $theme_file = get_theme_file_path($template);

         if (file_exists($theme_file)) {
            return render_latte($theme_file, $args, $echo);
         }
      }
   }
}

if (!function_exists('latte_plugin')) {
   /**
    * Loads a latte template from the /components directory in the CAV plugin.
    *
     * @param string|string[] $template_name The latte template filename, with sub-components if needed.
     * @param array|object    $args          Arguments passed to the template. Default: [].
     * @param bool            $echo          Whether to echo the template or return it. Default: true.
    *
     * @return bool|string|void Void if $echo argument is true; template output if $echo is false; false if the template was not found.
    *
    * * @throws WP_Error When the latte cache directory can't be created.
    *
    * @since 1.0.0 Introduced.
    */
   function latte_plugin(array|string $template_name, array|object $args = [], bool $echo = true)
   {
      if (is_array($template_name)) {
         $template_name = implode('/components/', $template_name);
      }

      $plugin_dir = plugin_dir_path(CAV_WP_FILE);

      $templates[] = "components/{$template_name}/_index.latte";
      $templates[] = "components/{$template_name}.latte";

      foreach ($templates as $template) {
         $template_file = $plugin_dir . $template;

         if (file_exists($template_file)) {
            return render_latte($template_file, $args, $echo);
         }
      }

      return false;
   }
}

if (!function_exists('is_bot')) {
   /**
    * Determines whether is an user agent is a known bot.
    *
     * @param string $user_agent An user agent to check. If empty, uses `$_SERVER['HTTP_USER_AGENT']`. Default: ''.
    *
     * @return bool Whether is a bot.
    *
    * @since 1.0.0 Introduced.
    */
   function is_bot(string $user_agent = '')
   {
      if (empty($user_agent)) {
         $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
      }

      if (empty($user_agent)) {
         return false;
      }

      $ua = stripslashes($user_agent);

      // spellchecker: disable
      $bot_agents = [
         'ahrefsbot',
         'alexa',
         'altavista',
         'applebot',
         'ask jeeves',
         'attentio',
         'baiduspider',
         'bingbot',
         'BLEXBot',
         'chtml generic',
         'cloudflare-alwaysonline',
         'crawler',
         'Discordbot',
         'domaintunocrawler',
         'DotBot',
         'DuckDuckBot',
         'Exabot',
         'facebot',
         'fastmobilecrawl',
         'feedfetcher-google',
         'firefly',
         'froogle',
         'germcrawler',
         'gigabot',
         'googlebot',
         'grapeshotcrawler',
         'grokkit-crawler',
         'heritrix',
         'httrack',
         'ia_archiver',
         'iescholar',
         'infoseek',
         'insomnia',
         'irlbot',
         'jumpbot',
         'kraken',
         'linkcheck',
         'linkdexbot',
         'livelapbot',
         'lycos',
         'mediapartners',
         'mediobot',
         'MJ12bot',
         'motionbot',
         'mshots',
         'msnbot',
         'openbot',
         'openhosebot',
         'pingdom.com_bot',
         'pss-webkit-request',
         'python-requests',
         'pythumbnail',
         'queryseekerspider',
         'rogerbot',
         'scooter',
         'Screaming Frog',
         'SeekportBot',
         'SemrushBot',
         'SEOkicks',
         'SeznamBot',
         'SiteCheckerBot',
         'slurp',
         'snapbot',
         'Sogou web spider',
         'spider',
         'taptubot',
         'technoratisnoop',
         'teoma',
         'tweetmemebot',
         'twiceler',
         'twitterbot',
         'UptimeRobot',
         'WhatsApp',
         'Wotbox',
         'yahooseeker',
         'yahooysmcm',
         'yammybot',
         'yandexbot',
      ];
      // spellchecker: enable

      foreach ($bot_agents as $bot_agent) {
         if (false !== stripos($ua, $bot_agent)) {
            return true;
         }
      }

      return false;
   }
}

if (!function_exists('is_environment')) {
   /**
    * Determines whether is in a specified environment.
    *
     * @param string|string[] $environments Environment(s) to check. Accepts 'local', 'development', 'staging' or ‘production’.
    *
     * @return bool Whether is in that environment(s).
    *
    * @since 1.0.0 Introduced.
    */
   function is_environment(array|string $environments)
   {
      if (!is_array($environments)) {
         $environments = [$environments];
      }

      return in_array(wp_get_environment_type(), $environments);
   }
}

if (!function_exists('is_cli')) {
   /**
    * Determines whether is running from a WP CLI command.
    *
     * @return bool Whether is from WP CLI.
    *
    * @since 1.0.0 Introduced.
    */
   function is_cli()
   {
      return defined('WP_CLI') && \WP_CLI;
   }
}

if (!function_exists('_cav_prints_meta_charset')) {
   /**
    * @ignore
    */
   function _cav_prints_meta_charset(): void
   {
      echo '<meta charset="' . get_bloginfo('charset') . '" />';
      echo '<meta http-equiv="X-UA-Compatible" content="IE=edge" />';
      echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />';
   }
}

if (
   !function_exists('edit_user_link')) {
   function edit_user_link(
      ?string $text = null,
      string $before = '',
      string $after = '',
      null|int|\WP_User $user = null,
      string $css_class = 'user-edit-link',
   ): void {
      if (is_null($user)) {
         $user = wp_get_current_user();
      }

      if (!is_a($user, 'WP_User')) {
         $user = new \WP_User($user);
      }

      if (empty(!$user)) {
         return;
      }

      $url = get_edit_user_link($user->ID);

      if (!$url) {
         return;
      }

      if (null === $text) {
         $text = __('Edit This');
      }

      $link = '<a class="' . esc_attr($css_class) . '" href="' . esc_url($url) . '">' . $text . '</a>';

      /**
       * Filters the user edit link anchor tag.
       *
       * @since 1.0.0
       *
       * @param string $link    Anchor tag for the edit link.
       * @param int    $user_id User ID.
       * @param string $text    Anchor text.
       */
      echo $before . apply_filters('edit_user_link', $link, $user->ID, $text) . $after;
   }
}
