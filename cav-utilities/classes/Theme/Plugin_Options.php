<?php

namespace cavWP\Theme;

class Plugin_Options
{
   public function __construct()
   {
      add_filter('cav_settings_options', [$this, 'add_options']);
   }

   public function add_options($options)
   {
      $options['theme'] = [
         'title'       => esc_html__('Theme Options', 'cav-utilities'),
         'description' => '',
         'active'      => false,
         'category'    => 'theme',
         'fields'      => [
            'title_tag' => [
               'label' => esc_html__('Adds Title Tag support', 'cav-utilities'),
               'type'  => 'checkbox',
            ],
            'add_supports' => [
               'label'       => 'Adds theme supports',
               'description' => 'Adds supports for post-thumbnails, responsive-embed and html5.',
               'type'        => 'checkbox',
            ],
            'pages_template' => [
               'label'       => 'Pages Templates',
               'description' => esc_html__('Search /pages theme folder for hierarchies templates.', 'cav-utilities'),
               'type'        => 'checkbox',
            ],
            'remove_tags' => [
               'label' => esc_html__('Remove WP default tags', 'cav-utilities'),
               'type'  => 'checkbox',
            ],
            'remove_assets' => [
               'label' => esc_html__('Remove WP default assets', 'cav-utilities'),
               'type'  => 'checkbox',
            ],
            'remove_title_prepend' => [
               'label' => esc_html__('Remove prepend of titles', 'cav-utilities'),
               'type'  => 'checkbox',
            ],
            'no_title' => [
               'label' => esc_html__('Populate empty post titles', 'cav-utilities'),
               'type'  => 'checkbox',
            ],
            'rest_url_base' => [
               'label' => esc_html__('Changes REST API base URL to /api.', 'cav-utilities'),
               'type'  => 'checkbox',
            ],
            'author_base' => [
               'label' => esc_html__('Changes the Author base URL.', 'cav-utilities'),
               'type'  => 'text',
               'attrs' => [
                  'placeholder' => 'author',
               ],
            ],
            'search_base' => [
               'label' => esc_html__('Changes the Search base URL.', 'cav-utilities'),
               'type'  => 'text',
               'attrs' => [
                  'placeholder' => 'search',
               ],
            ],
            'gtm_code' => [
               'label' => esc_html__('Google Tag Manager code.', 'cav-utilities'),
               'type'  => 'text',
               'attrs' => [
                  'placeholder' => 'GTM-XXXXXXXX',
               ],
            ],
         ],
      ];

      return $options;
   }
}
