<?php

namespace cavWP\SEO_Links;

class Admin_Page
{
   public function __construct()
   {
      add_action('admin_menu', [$this, 'register_page']);
   }

   public function _admin_list_unique_urls(): void
   {
      latte_plugin('seo_links', [
         'head_id'       => esc_html__('ID', 'cavwp'),
         'head_url'      => esc_html__('URL', 'cavwp'),
         'head_template' => esc_html__('Template', 'cavwp'),
         'head_obj'      => esc_html__('Object', 'cavwp'),
         'head_edit'     => esc_html__('Edit', 'cavwp'),
         'reports'       => Utils::get_reports(),
      ]);
   }

   public function register_page(): void
   {
      $name = esc_html__('CAV SEO Analysis', 'cavwp');

      add_options_page(
         $name,
         $name,
         'manage_options',
         'cavwp-seo_links',
         [$this, '_admin_list_unique_urls'],
         100,
      );
   }
}
