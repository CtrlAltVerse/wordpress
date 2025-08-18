<?php

namespace cavWP\Networks;

use cavWP\Services\Facebook;
use cavWP\Services\Threads;
use cavWP\Utils as MainUtils;

class Plugin_Options
{
   public function __construct()
   {
      add_filter('cav_settings_options', [$this, 'add_options']);
   }

   public function add_options($options)
   {
      $Facebook = new Facebook();
      $Threads  = new Threads();

      $options['autoshare'] = [
         'title'    => 'Auto Share',
         'active'   => true,
         'category' => 'network',
         'fields'   => [
            'post_types' => [
               'label'   => esc_html__('Post types to publish', 'cav-utilities'),
               'type'    => 'select',
               'choices' => MainUtils::get_content_types('post_type'),
               'default' => [],
               'attrs'   => [
                  'multiple' => true,
               ],
            ],
            'twitter_on' => [
               'label' => esc_html__('Auto publish new posts on X', 'cav-utilities'),
               'type'  => 'checkbox',
            ],
            'twitter_api_key' => [
               'label' => 'X API Key',
               'type'  => 'password',
            ],
            'twitter_api_key_secret' => [
               'label' => 'X API Key Secret',
               'type'  => 'password',
            ],
            'twitter_access_token' => [
               'label' => 'X Access Token',
               'type'  => 'password',
            ],
            'twitter_access_token_secret' => [
               'label' => 'X Access Token Secret',
               'type'  => 'password',
            ],
            'fb_on' => [
               'label' => esc_html__('Auto publish new posts on Facebook', 'cav-utilities'),
               'type'  => 'checkbox',
            ],
            'fb_page_id' => [
               'label' => 'Facebook Page ID',
               'type'  => 'number',
            ],
            'fb_api_id' => [
               'label' => 'Facebook App ID',
               'type'  => 'number',
            ],
            'fb_client_secret' => [
               'label' => 'Facebook App Client Secret',
               'type'  => 'password',
            ],
            'fb_renew_token' => [
               'label'   => esc_html__('Get or Renew Page Access Token', 'cav-utilities'),
               'type'    => 'link',
               'default' => $Facebook->get_link_token(),
               'attrs'   => [
                  'target' => '_blank',
               ],
            ],
            'threads_on' => [
               'label' => esc_html__('Auto publish new posts on Threads', 'cav-utilities'),
               'type'  => 'checkbox',
            ],
            'threads_app_ID' => [
               'label' => 'Threads App ID',
               'type'  => 'number',
            ],
            'threads_app_client_secret' => [
               'label' => 'Threads App Client Secret',
               'type'  => 'password',
            ],
            'threads_renew_token' => [
               'label'   => esc_html__('Get Access Token', 'cav-utilities'),
               'type'    => 'link',
               'default' => $Threads->get_link_token(),
               'attrs'   => [
                  'target' => '_blank',
               ],
            ],
         ],
      ];

      $options['social_login'] = [
         'title'    => 'Social Login',
         'category' => 'network',
         'fields'   => [
            'email' => [
               'label' => esc_html__('Sign in with e-mail', 'cav-utilities'),
               'type'  => 'checkbox',
            ],
            'google_client_id' => [
               'label' => esc_html__('Google Client ID', 'cav-utilities'),
            ],
            'facebook_app_id' => [
               'label' => esc_html__('Facebook App ID', 'cav-utilities'),
            ],
            'apple_client_id' => [
               'label' => esc_html__('Apple Client ID', 'cav-utilities'),
            ],
         ],
      ];

      return $options;
   }
}
