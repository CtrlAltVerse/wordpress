<?php

namespace cavWP\Networks;

class Admin_User
{
   private $profile_networks;
   private $user_ID;

   public function __construct()
   {
      $this->profile_networks = Utils::get_services('profile');

      add_filter('user_contactmethods', [$this, 'add_networks_profile'], 15);

      foreach ($this->profile_networks as $key => $_network) {
         add_filter("user_{$key}_label", [$this, 'set_profile_label']);
      }

      if (is_multisite()) {
         add_action('show_user_profile', [$this, 'add_hidden_networks']);
         add_action('edit_user_profile', [$this, 'add_hidden_networks']);

         add_action('personal_options_update', [$this, 'save_hidden_networks']);
         add_action('edit_user_profile_update', [$this, 'save_hidden_networks']);
      }
   }

   public function add_hidden_networks($user)
   {
      $title       = esc_html__('Hide social network from profile', 'cav-utilities');
      $description = esc_html__('Select social network to hidden from this profile on this site.', 'cav-utilities');
      $list        = '';

      $to_hidden = get_user_option('networks-hidden', $user->ID);

      if (empty($to_hidden)) {
         $to_hidden = [];
      }

      foreach ($this->profile_networks as $key => $network) {
         $checked = checked($to_hidden[$key] ?? false, 1, false);
         $list .= "<label><input name=\"networks-hidden[{$key}]\" type=\"checkbox\" value=\"1\" {$checked}> {$network['name']}</label> &nbsp; &nbsp; ";
      }

      echo <<<HTML
         <h2>{$title}</h2>
         <table class="form-table" role="presentation">
         <tbody>
            <tr class="user-description-wrap">
            <th><label for="description">Networks to hide</label></th>
            <td>
               <fieldset>
               {$list}
               <p class="description">{$description}</p>
               </fieldset>
            </td>
         </tr>
         </tbody>
         </table>
      HTML;
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

   public function save_hidden_networks($user_ID)
   {
      $to_hidden = $_POST['networks-hidden'] ?? false;

      if (empty($to_hidden)) {
         delete_user_option($user_ID, 'networks-hidden');

         return;
      }

      update_user_option($user_ID, 'networks-hidden', $to_hidden);
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
