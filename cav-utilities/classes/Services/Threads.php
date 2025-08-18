<?php

namespace cavWP\Services;

/**
 * @ignore
 */
final class Threads
{
   private $access_token  = '';
   private $app_ID        = '';
   private $base_url      = 'https://graph.threads.net/v1.0';
   private $client_secret = '';
   private $user_ID       = '';

   public function __construct()
   {
      $this->user_ID       = get_option('cav-autoshare-threads_user_ID');
      $this->access_token  = get_option('cav-autoshare-threads_access_token');
      $this->app_ID        = get_option('cav-autoshare-threads_app_ID');
      $this->client_secret = get_option('cav-autoshare-threads_app_client_secret');
   }

   public function get_link_token()
   {
      return 'https://threads.net/oauth/authorize?' . http_build_query([
         'client_id'     => $this->app_ID,
         'redirect_uri'  => home_url('?cav=retrieve_threads_code'),
         'scope'         => 'threads_basic,threads_content_publish',
         'response_type' => 'code',
      ]);
   }

   public function post($text, $link)
   {
      $request = wp_remote_post("{$this->base_url}/{$this->user_ID}/threads?" . http_build_query([
         'text'         => $text . ' ' . $link,
         'media_type'   => 'TEXT',
         'access_token' => $this->access_token,
      ]));

      if (\is_wp_error($request)) {
         return false;
      }

      if (\wp_remote_retrieve_response_code($request) >= 300) {
         return false;
      }

      $response = json_decode(\wp_remote_retrieve_body($request), true);

      sleep(30);

      $request = wp_remote_post("{$this->base_url}/{$this->user_ID}/threads_publish?" . http_build_query([
         'creation_id'  => $response['id'],
         'access_token' => $this->access_token,
      ]));

      if (\is_wp_error($request)) {
         return false;
      }

      if (\wp_remote_retrieve_response_code($request) >= 300) {
         return false;
      }

      $response = json_decode(\wp_remote_retrieve_body($request), true);

      return $response['id'];
   }

   public function refresh_token()
   {
      $access_token = get_option('cav-autoshare-threads_access_token');

      if (!$access_token) {
         return false;
      }

      $request = wp_remote_get($this->base_url . '/refresh_access_token?' . http_build_query([
         'grant_type'   => 'th_refresh_token',
         'access_token' => $access_token,
      ]));

      if (is_wp_error($request)) {
         return false;
      }

      if (\wp_remote_retrieve_response_code($request) >= 300) {
         return false;
      }

      $response = json_decode(\wp_remote_retrieve_body($request), true);

      update_option('cav-autoshare-threads_access_token', $response['access_token'], 'no');

      return $response['access_token'];
   }

   public function save_token($code)
   {
      $request_short_token = wp_remote_post($this->base_url . '/oauth/access_token', [
         'headers' => [
            'Content-Type' => 'application/json',
         ],
         'body' => json_encode([
            'client_id'     => $this->app_ID,
            'client_secret' => $this->client_secret,
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => home_url('?cav=retrieve_threads_code'),
         ]),
      ]);

      if (is_wp_error($request_short_token)) {
         return false;
      }

      if (\wp_remote_retrieve_response_code($request_short_token) >= 300) {
         return false;
      }

      $response_short_token = json_decode(\wp_remote_retrieve_body($request_short_token), true);

      update_option('cav-autoshare-threads_user_ID', $response_short_token['user_id'], 'no');

      $request_long_term = wp_remote_get($this->base_url . '/access_token?' . http_build_query([
         'grant_type'    => 'th_exchange_token',
         'client_secret' => $this->client_secret,
         'access_token'  => $response_short_token['access_token'],
      ]));

      if (is_wp_error($request_long_term)) {
         return false;
      }

      if (\wp_remote_retrieve_response_code($request_long_term) >= 300) {
         return false;
      }

      $response_long_token = json_decode(\wp_remote_retrieve_body($request_long_term), true);

      update_option('cav-autoshare-threads_access_token', $response_long_token['access_token'], 'no');
   }
}
