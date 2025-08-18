<?php

namespace cavWP;

/**
 * @ignore
 */
final class Ads_txt
{
   private $ads_txt = '';

   public function __construct($ads_txt)
   {
      $this->ads_txt = $ads_txt;

      add_action('init', [$this, 'add_rewrite']);
      add_action('template_redirect', [$this, 'prints_content']);

      add_filter('redirect_canonical', [$this, 'remove_trailingslashit']);
   }

   public function add_rewrite(): void
   {
      add_rewrite_rule('^ads\.txt/?$', 'index.php?cav=adstxt', 'top');
   }

   public function prints_content(): void
   {
      $cav_template = get_query_var('cav', false);

      if ('adstxt' !== $cav_template) {
         return;
      }

      header('Content-Type: text/plain; charset=utf-8');

      echo $this->ads_txt . "\n";
      exit;
   }

   public function remove_trailingslashit($redirect_url)
   {
      $cav_template = get_query_var('cav', false);

      if ('adstxt' !== $cav_template) {
         return $redirect_url;
      }

      return rtrim($redirect_url, '/');
   }
}
