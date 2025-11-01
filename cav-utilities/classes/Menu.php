<?php

namespace cavWP;

final class Menu
{
   public function __construct()
   {
      add_action('wp_nav_menu_item_custom_fields', [$this, 'add_menu_custom_field']);
      add_action('save_post_nav_menu_item', [$this, 'save_nav_menu']);

      add_filter('nav_menu_item_title', [$this, 'add_menu_icon'], 10, 2);
      add_filter('nav_menu_css_class', [$this, 'adds_menu_item_classes'], 10, 3);
      add_filter('nav_menu_link_attributes', [$this, 'adds_menu_link_classes'], 10, 3);
   }

   public function add_menu_custom_field($item_id): void
   {
      $icon = get_post_meta($item_id, 'menu_icon', true);
      $icon = $icon ?: '';

      echo <<<HTML
            <p class="description description-wide">
               <label for="edit-menu-item-icon-{$item_id}">
                  Classe do ícone<br>
                  <input type="text" id="edit-menu-item-icon-{$item_id}" class="widefat edit-menu-item-icon" name="menu-item-icon[{$item_id}]" value="{$icon}">
               </label>
            </p>
      HTML;
   }

   public function add_menu_icon($title, $item)
   {
      $icon_class = get_post_meta($item->ID, 'menu_icon', true);

      if ($icon_class) {
         return "<span class='menu-item-text'><i class='{$icon_class} font-normal'></i> <span class='menu-item-title'>{$title}</span></span>";
      }

      return $title;
   }

   public function adds_menu_item_classes($classes, $_menu_item, $args)
   {
      if (empty($args->item_class)) {
         return $classes;
      }

      $classes[] = $args->item_class;

      return $classes;
   }

   public function adds_menu_link_classes($atts, $_menu_item, $args)
   {
      if (empty($args->link_class)) {
         return $atts;
      }

      $atts['class'] = $args->link_class;

      return $atts;
   }

   public function save_nav_menu(): void
   {
      if (!isset($_POST['menu-item-icon'])) {
         return;
      }

      foreach ($_POST['menu-item-icon'] as $post_id => $icon) {
         if (empty($icon)) {
            delete_post_meta($post_id, 'menu_icon');
         } else {
            update_post_meta($post_id, 'menu_icon', $icon);
         }
      }
   }
}
