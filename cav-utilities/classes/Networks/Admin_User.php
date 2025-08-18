<?php

namespace cavWP\Networks;

class Admin_User
{
   private $profile_networks;

   public function __construct()
   {
      $this->profile_networks = Utils::get_services('profile');

      add_filter('user_contactmethods', [$this, 'add_networks_profile'], 15);

      foreach ($this->profile_networks as $key => $_network) {
         add_filter("user_{$key}_label", [$this, 'set_profile_label']);
      }
   }

   public function add_networks_profile($networks)
   {
      $new_networks = $networks;

      foreach ($this->profile_networks as $key => $network) {
         if (isset($new_networks[$key])) {
            continue;
         }

         $new_networks[$key] = $network['name'];
      }

      return $new_networks;
   }

   public function set_profile_label($label)
   {
      $key = strtolower($label);

      if ('x' === $key) {
         $key = 'x-twitter';
      }

      if (!isset($this->profile_networks[$key])) {
         return $label;
      }

      $network = $this->profile_networks[$key];

      if (empty($network['profile_type'])) {
         $suffix = esc_html__('Username', 'cav-utilities');
      } elseif ('url' === $network['profile_type']) {
         $suffix = esc_html__('Full URL', 'cav-utilities');
      } elseif ('number' === $network['profile_type']) {
         $suffix = esc_html__('Only numbers', 'cav-utilities');
      }

      return "{$label}<br><em>{$suffix}</em>";
   }
}
