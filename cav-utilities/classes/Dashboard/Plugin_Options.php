<?php

namespace cavWP\Dashboard;

class Plugin_Options
{
   public function __construct()
   {
      add_filter('cav_settings_options', [$this, 'add_options']);
   }

   public function add_options($options)
   {
      $options['dashboard'] = [
         'title'       => esc_html__('Dashboard Options', 'cav-utilities'),
         'description' => '',
         'category'    => 'dashboard',
         'active'      => false,
         'fields'      => [
            'show_image_sizes' => [
               'label' => esc_html__('Show all images sizes on post editor (Gutenberg)', 'cav-utilities'),
               'type'  => 'checkbox',
            ],
            'acf_show_key' => [
               'label' => esc_html__('Show ACF keys on UI', 'cav-utilities'),
               'type'  => 'checkbox',
            ],
            'change_schema_colors' => [
               'label' => esc_html__('Change dashboards colors by environment', 'cav-utilities'),
               'type'  => 'checkbox',
            ],
            'hide_bar' => [
               'label' => 'Hide admin bar',
               'type'  => 'checkbox',
            ],
            'new_user_mail' => [
               'label' => 'Adds more information to new user email',
               'type'  => 'checkbox',
            ],
            'rest_api_logged' => [
               'label' => esc_html__('Restrict REST API access to logged-in users only.', 'cav-utilities'),
               'type'  => 'checkbox',
            ],
            'deactivate_threshold' => [
               'label' => esc_html__('Deactivate the threshold for size image uploaded.', 'cav-utilities'),
               'type'  => 'checkbox',
            ],
         ],
      ];
      $options['media'] = [
         'title'    => __('Media options', 'cav-utilities'),
         'category' => 'dashboard',
         'fields'   => [
            'unsplash_key' => [
               'label' => __('Unsplash', 'cav-utilities'),
               'type'  => 'password',
            ],
         ],
      ];
      $options['smtp'] = [
         'title'       => 'SMTP',
         'description' => 'Configures SMTP for sending emails.',
         'active'      => true,
         'category'    => 'dashboard',
         'fields'      => [
            'host' => [
               'label' => 'Host',
               'type'  => 'text',
            ],
            'port' => [
               'label'   => 'Port',
               'type'    => 'number',
               'default' => 465,
            ],
            'user' => [
               'label' => 'User',
               'type'  => 'text',
            ],
            'password' => [
               'label' => 'Password',
               'type'  => 'password',
            ],
            'secure' => [
               'label'   => 'Secure',
               'type'    => 'select',
               'choices' => [
                  'tls' => 'TLS',
                  'ssl' => 'SSL',
               ],
            ],
            'test' => [
               'label'   => esc_html__('Send a test mail', 'cav-utilities'),
               'type'    => 'link',
               'default' => home_url('?cav=send_test_mail'),
            ],
         ],
      ];
      $options['health_check'] = [
         'title'       => 'Health Check',
         'description' => 'Creates a new API endpoint that checks current state of the site. The response may takes 35s.',
         'active'      => true,
         'category'    => 'dashboard',
         'fields'      => [
            'theme' => [
               'label'   => 'Theme to be active',
               'type'    => 'select',
               'choices' => ['' => '(none)', ...array_map(fn($theme) => $theme->name, wp_get_themes())],
            ],
            'custom_url' => [
               'label'       => 'URL',
               'description' => 'Self URL to check if it is accessible and not empty.',
               'type'        => 'url',
               'attrs'       => [
                  'placeholder' => home_url('*'),
               ],
            ],
         ],
      ];
      $options['activity_log'] = [
         'title'       => 'Activity Log',
         'description' => '',
         'active'      => true,
         'category'    => 'dashboard',
         'fields'      => [
            'block_fail_logins' => [
               'label' => 'Blocks multiple fail login attempts',
               'type'  => 'checkbox',
            ],
            'block_fail_logins_interval' => [
               'label'       => 'Failed Login Interval',
               'description' => 'Time frame in minutes to check failed logins attempts for the same IP.',
               'type'        => 'number',
               'default'     => 15,
            ],
            'block_fail_logins_attempts' => [
               'label'       => 'Failed Attempts Limit',
               'description' => 'Number of failed login attempts allowed during the interval.',
               'type'        => 'number',
               'default'     => 5,
            ],
         ],
      ];

      return $options;
   }
}
