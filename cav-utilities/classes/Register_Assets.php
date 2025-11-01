<?php

namespace cavWP;

final class Register_Assets
{
   public function __construct()
   {
      add_action('wp_enqueue_scripts', [$this, 'register_scripts']);
   }

   public function register_scripts(): void
   {
      wp_register_style('fontawesome', plugins_url('assets/fontawesome.min.css', CAV_WP_FILE), [], '7.0.1');
      wp_register_style('links_page', plugins_url('assets/links_page.min.css', CAV_WP_FILE), [], '1');
   }
}
