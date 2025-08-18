<?php

namespace cavWP\Services;

/**
 * @ignore
 */
final class Facebook
{
   private $app_ID        = '';
   private $base_url      = 'https://graph.facebook.com/v22.0';
   private $client_secret = '';
   private $page_ID       = '';

   public function __construct()
   {
      $this->page_ID       = get_option('cav-autoshare-fb_page_id');
      $this->app_ID        = get_option('cav-autoshare-fb_api_id');
      $this->client_secret = get_option('cav-autoshare-fb_client_secret');
   }

   public function get_link_token()
   {
      if (empty($this->page_ID) || empty($this->app_ID) || empty($this->client_secret)) {
         return false;
      }

      return 'https://www.facebook.com/v22.0/dialog/oauth?' . http_build_query([
         'client_id'     => $this->app_ID,
         'redirect_uri'  => home_url('?cav=retrieve_fb_code'),
         'scope'         => 'pages_show_list,pages_manage_posts',
         'response_type' => 'code',
      ]);
   }

   public function post($text, $link)
   {
      $access_token = get_option('cav-autoshare-fb_page_access_token');

      if (empty($access_token)) {
         return false;
      }

      $request = \wp_remote_post("{$this->base_url}/{$this->page_ID}/feed", [
         'headers' => [
            'Content-Type' => 'application/json',
         ],
         'body' => json_encode([
            'message'      => $text,
            'link'         => $link,
            'access_token' => $access_token,
         ]),
      ]);

      if (\is_wp_error($request)) {
         return false;
      }

      if (\wp_remote_retrieve_response_code($request) >= 300) {
         return false;
      }

      $response = json_decode(\wp_remote_retrieve_body($request), true);

      return $response['id'];
   }

   public function save_token($code)
   {
      $request_short_token = wp_remote_get($this->base_url . '/oauth/access_token?' . http_build_query([
         'redirect_uri'  => home_url('?cav=retrieve_fb_code'),
         'client_id'     => $this->app_ID,
         'client_secret' => $this->client_secret,
         'code'          => $code,
      ]));

      if (is_wp_error($request_short_token)) {
         return false;
      }

      if (\wp_remote_retrieve_response_code($request_short_token) >= 300) {
         return false;
      }

      $response_short_token = json_decode(\wp_remote_retrieve_body($request_short_token), true);

      $request = wp_remote_get($this->base_url . '/me/accounts?' . http_build_query([
         'access_token' => $response_short_token['access_token'],
      ]));

      if (is_wp_error($request)) {
         return false;
      }

      if (\wp_remote_retrieve_response_code($request) >= 300) {
         return false;
      }

      $response = json_decode(\wp_remote_retrieve_body($request), true);

      foreach ($response['data'] as $page) {
         if ($this->page_ID === $page['id']) {
            update_option('cav-autoshare-fb_page_access_token', $page['access_token'], 'no');
            break;
         }
      }
   }
}
