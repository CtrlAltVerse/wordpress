<?php

namespace cavWP;

use cavWP\Services\Facebook;
use cavWP\Services\Threads;
use cavWP\Services\Twitter;

/**
 * @ignore
 */
final class Social_Share
{
   private $active_networks = [];
   private $post_types      = [];

   public function __construct()
   {
      $this->post_types = get_option('cav-autoshare-post_types', []);

      if (get_option('cav-autoshare-twitter_on')) {
         $api_key             = get_option('cav-autoshare-twitter_api_key');
         $api_key_secret      = get_option('cav-autoshare-twitter_api_key_secret');
         $access_token        = get_option('cav-autoshare-twitter_access_token');
         $access_token_secret = get_option('cav-autoshare-twitter_access_token_secret');

         if (!empty($api_key) && !empty($api_key_secret) && !empty($access_token) && !empty($access_token_secret)) {
            $this->active_networks['twitter'] = new Twitter(
               $api_key,
               $api_key_secret,
               $access_token,
               $access_token_secret,
            );
         }
      }

      if (get_option('cav-autoshare-fb_on')) {
         $page_ID       = get_option('cav-autoshare-fb_page_id');
         $app_ID        = get_option('cav-autoshare-fb_api_id');
         $client_secret = get_option('cav-autoshare-fb_client_secret');
         $access_token  = get_option('cav-autoshare-fb_page_access_token');

         if (!empty($page_ID) && !empty($app_ID) && !empty($client_secret) && !empty($access_token)) {
            $this->active_networks['facebook'] = new Facebook();
         }
      }

      if (get_option('cav-autoshare-threads_on')) {
         $user_ID      = get_option('cav-autoshare-threads_user_ID');
         $access_token = get_option('cav-autoshare-threads_access_token');

         if (!empty($user_ID) && !empty($access_token)) {
            $this->active_networks['threads'] = new Threads();
         }
      }

      add_action('wp_insert_post', [$this, 'on_post_save'], 10, 2);
      add_action('template_redirect', [$this, 'retrieve_fb_code']);
      add_action('template_redirect', [$this, 'retrieve_threads_code']);
   }

   public function on_post_save($post_id, $post): void
   {
      if (!in_array($post->post_type, $this->post_types)) {
         return;
      }

      if ('publish' !== $post->post_status) {
         return;
      }

      $social_posts = get_post_meta($post_id, 'cav-social_posts', true);

      if (empty($social_posts)) {
         $social_posts = [];
      }

      if (!empty($post->post_excerpt)) {
         $text = $post->post_excerpt;
      } else {
         $text = $post->post_title;
      }

      $link = get_permalink($post_id);

      foreach ($this->active_networks as $key => $class) {
         if (!empty($social_posts[$key])) {
            continue;
         }

         if ($social_post_ID = $class->post($text, $link)) {
            $social_posts[$key] = $social_post_ID;
         }
      }

      update_post_meta($post_id, 'cav-social_posts', $social_posts);
   }

   public function retrieve_fb_code(): void
   {
      $cav_template = get_query_var('cav');
      $code         = $_GET['code'] ?? false;

      if ('retrieve_fb_code' !== $cav_template || empty($code)) {
         return;
      }

      $Facebook = new Facebook();
      $Facebook->save_token($code);

      $url = admin_url('options-general.php?page=cavwp');

      if (wp_safe_redirect($url)) {
         exit;
      }
   }

   public function retrieve_threads_code(): void
   {
      $cav_template = get_query_var('cav');
      $code         = $_GET['code'] ?? false;

      if ('retrieve_threads_code' !== $cav_template || empty($code)) {
         return;
      }

      $Threads = new Threads();
      $Threads->save_token($code);

      $url = admin_url('options-general.php?page=cavwp');

      if (wp_safe_redirect($url)) {
         exit;
      }
   }
}
