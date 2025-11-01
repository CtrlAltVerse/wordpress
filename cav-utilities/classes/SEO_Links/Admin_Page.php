<?php

namespace cavWP\SEO_Links;

class Admin_Page
{
   public function __construct()
   {
      add_action('admin_menu', [$this, 'register_page']);

      add_action('template_redirect', [$this, 'run_first']);
   }

   public function page_content(): void
   {
      latte_plugin('page_seo_links', [
         'head_id'       => esc_html__('ID', 'cav-utilities'),
         'head_url'      => esc_html__('URL', 'cav-utilities'),
         'head_template' => esc_html__('Template', 'cav-utilities'),
         'head_obj'      => esc_html__('Object', 'cav-utilities'),
         'head_edit'     => esc_html__('Edit', 'cav-utilities'),
         'reports'       => Utils::get_reports(),
      ]);
   }

   public function register_page(): void
   {
      $name = esc_html__('CAV SEO Analysis', 'cav-utilities');

      add_submenu_page(
         'cavwp',
         $name,
         $name,
         'manage_options',
         'cavwp-seo_links',
         [$this, 'page_content'],
         100,
      );
   }

   public function run_first()
   {
      $cav_template = get_query_var('cav', false);

      if ('run_seo' !== $cav_template) {
         return;
      }

      $register = new Register();
      $register->analysis();

      if (wp_safe_redirect(admin_url('?page=cavwp-seo_links'))) {
         exit;
      }
   }
}
