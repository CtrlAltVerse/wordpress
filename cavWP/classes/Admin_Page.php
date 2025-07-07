<?php

namespace cavWP;

/**
 * @ignore
 */
final class Admin_Page
{
   public function __construct()
   {
      add_action('admin_init', [$this, 'register_sections']);
      add_action('admin_menu', [$this, 'register_page']);
      add_action('admin_enqueue_scripts', [$this, 'enqueues_assets']);
      add_action('admin_footer', [$this, 'on_save_settings']);

      $plugin_base = plugin_basename(CAV_WP_DIR);
      add_filter('plugin_action_links_' . $plugin_base, [$this, 'adds_settings_links']);

      add_action('template_redirect', [$this, 'send_test_mail']);

      if (isset($_GET['cav_notice'])) {
         add_action('admin_notices', [$this, 'adds_notice']);
      }
   }

   public function adds_notice(): void
   {
      $notice = $_GET['cav_notice'];

      $text = match ($notice) {
         'test_mail-yes' => esc_html__('The test mail was sent without errors.', 'cavwp'),
         'test_mail-no'  => esc_html__('The test mail was not sent. An error occurred.', 'cavwp'),
         default         => false,
      };

      if (empty($text)) {
         return;
      }

      $class = str_ends_with($notice, '-yes') ? 'success' : 'error';

      printf(
         '<div class="notice notice-%s is-dismissible"><strong>%s</strong></div>',
         $class,
         $text,
      );
   }

   public function adds_settings_links($actions)
   {
      $settings_url = admin_url('options-general.php?page=cavwp');

      $actions[] = [
         '<a href="' . $settings_url . '">' . esc_html__('Settings', 'cavwp') . '</a>',
      ];

      $actions[] = [
         '<a href="#" target="_blank">' . esc_html__('Contribute', 'cavwp') . '</a>',
      ];

      return $actions;
   }

   public function enqueues_assets(): void
   {
      global $pagenow;

      if ('options-general.php' !== $pagenow || !in_array($_GET['page'] ?? '', ['cavwp', 'cavwp-seo_links'])) {
         return;
      }

      add_thickbox();
      $assets_dir = plugin_dir_url(CAV_WP_FILE) . 'assets/';

      wp_enqueue_style('cavwp', $assets_dir . 'config_page.css');
   }

   public function field_content($field): void
   {
      if (empty($field['type'])) {
         $field['type'] = 'text';
      }

      $file = $field['type'];

      if (!in_array($field['type'], ['link', 'select', 'radio', 'checkbox', 'textarea'])) {
         $file = 'input';
      }

      if ('select' === $field['type'] && isset($field['attrs']['multiple'])) {
         $file = 'select_multiple';
      }

      $attrs = '';

      if (!empty($field['attrs'])) {
         foreach ($field['attrs'] as $key => $value) {
            $attrs .= " {$key}=\"{$value}\"";
         }
      }

      $value = get_option($field['ID'], $field['default'] ?? '');

      latte_plugin("{$file}_content", [
         'name'        => $field['ID'],
         'current'     => $value,
         'type'        => $field['type'],
         'label'       => $field['label'],
         'description' => $field['description'] ?? null,
         'default'     => $field['default']     ?? null,
         'choices'     => $field['choices']     ?? [],
         'attrs'       => $attrs,
      ]);
   }

   public function on_save_settings(): void
   {
      global $pagenow;

      $is_this_plugin = 'cavwp' === ($_GET['page'] ?? false);
      $is_updated     = 'true'  === ($_GET['settings-updated'] ?? false);

      if ('options-general.php' !== $pagenow || !$is_this_plugin || !$is_updated) {
         return;
      }

      flush_rewrite_rules(false);
      Utils::purge_page_cache('/links');

      if ($site_icon_ID = get_option('site_icon')) {
         add_image_size('cav_favicon', 64, 64, true);
         Utils::regenerate_image_sizes($site_icon_ID, ['medium_large']);
         remove_image_size('cav_favicon');
      }
   }

   public function page_content(): void
   {
      global $wp_settings_sections, $wp_settings_fields;
      $page = 'cavwp';

      if (!isset($wp_settings_sections[$page])) {
         return;
      }

      echo '<div class="wrap">';
      echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';
      echo '<form action="options.php" method="post">';
      settings_fields('cavwp');

      foreach ((array) $wp_settings_sections[$page] as $section) {
         $color = Plugin_Options::get_categories($section['category'])['color'];

         echo '<div class="section-content border-l-5 bg-zinc-200" style="border-color: ' . $color . '">';

         if ($section['callback']) {
            call_user_func($section['callback'], $section);
         }

         if (!empty($wp_settings_fields[$page][$section['id']])) {
            foreach ((array) $wp_settings_fields[$page][$section['id']] as $field) {
               call_user_func($field['callback'], $field['args']);
            }
         }

         latte_plugin('button', [
            'label' => esc_html__('Save'),
         ]);

         echo '</div>';
      }

      echo '</form>';
   }

   public function register_page(): void
   {
      $name = get_plugin_data(CAV_WP_FILE)['Name'];

      add_options_page(
         $name,
         $name,
         'manage_options',
         'cavwp',
         [$this, 'page_content'],
         99,
      );
   }

   public function register_sections(): void
   {
      $options = Plugin_Options::get_options();

      foreach ($options as $section_key => $section) {
         if (is_numeric($section_key) || empty($section_key) || empty($section)) {
            continue;
         }

         $section_ID = 'cav-' . $section_key;

         if ($section['active']) {
            register_setting('cavwp', $section_ID);
         }

         add_settings_section(
            $section_ID,
            $section['title'],
            [$this, 'section_content'],
            'cavwp',
            [
               'ID' => $section_ID,
               ...$section,
            ],
         );

         foreach ($section['fields'] as $field_key => $field) {
            if (is_numeric($field_key) || empty($field_key) || empty($field)) {
               continue;
            }

            $field_ID = 'cav-' . $section_key . '-' . $field_key;
            register_setting('cavwp', $field_ID);

            add_settings_field(
               $field_ID,
               $field['label'],
               [$this, 'field_content'],
               'cavwp',
               'cav-' . $section_key,
               [
                  'ID' => $field_ID,
                  ...$field,
               ],
            );
         }
      }
   }

   public function section_content($section): void
   {
      $category = Plugin_Options::get_categories($section['category']);
      $value    = get_option($section['ID'], '');

      latte_plugin('section_content', [
         'name'        => $section['ID'],
         'title'       => $section['title'],
         'category'    => $category['label'],
         'description' => $section['description'],
         'color'       => $category['color'],
         'value'       => $value,
         'active'      => $section['active'],
      ]);
   }

   public function send_test_mail(): void
   {
      $cav_template = get_query_var('cav');

      if ('send_test_mail' !== $cav_template) {
         return;
      }

      $success = wp_mail(get_option('admin_email'), '[CAV WP Plugin] Test mail', 'This is a test mail.');
      $test    = $success ? 'test_mail-yes' : 'test_mail-no';

      if (wp_safe_redirect(admin_url('options-general.php?page=cavwp&cav_notice=' . $test))) {
         exit;
      }
   }
}
