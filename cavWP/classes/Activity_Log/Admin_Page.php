<?php

namespace cavWP\Activity_Log;

class Admin_Page
{
   private $menu;
   private $per_page_key = 'cav_activity_logs_per_page';

   public function __construct()
   {
      add_action('admin_menu', [$this, 'register']);

      add_filter('set-screen-option', [$this, 'set_screen_option'], 10, 3);
   }

   public function page_content(): void
   {
      global $table;

      latte_plugin('page_activity_log', [
         'table' => $table,
      ]);
   }

   public function register(): void
   {
      $name = esc_html__('CAV Activity Logs', 'cavwp');

      $this->menu = add_options_page(
         $name,
         $name,
         'manage_options',
         'cavwp-activity_logs',
         [$this, 'page_content'],
         100,
      );

      add_action("load-{$this->menu}", [$this, 'screen_options']);
   }

   public function screen_options(): void
   {
      global $table;
      $table = new Table();

      add_screen_option('per_page', [
         'label'   => __('Number of items per page:'),
         'default' => 20,
         'option'  => $this->per_page_key,
      ]);
   }

   public function set_screen_option($status, $option, $value)
   {
      if ($this->per_page_key === $option) {
         return $value;
      }

      return $status;
   }
}
