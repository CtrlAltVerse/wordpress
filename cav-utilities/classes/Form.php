<?php

namespace cavWP;

/*
Does not considers exclusiveMinimum and exclusiveMaximum.
minItems


/**
 * Creates form fields based in params from REST API endpoint.
 */

final class Form
{
   private $fields;
   private $raw;

   public function __construct($fields)
   {
      foreach ($fields as $key => $args) {
         if ('null' === $args['type']) {
            continue;
         }

         $this->fields[$key] = $this->add_field($key, $args);
      }
   }

   public function field($key, $attrs = [], $tag = null, $echo = true)
   {
      if (!in_array($key, array_keys($this->fields))) {
         $output = '';
      } else {
         $args = $this->fields[$key];

         $args['attrs'] = wp_parse_args($attrs, $args['attrs']);

         if (is_null($tag)) {
            $tag = $args['tag'];
         }

         $field_start  = $this->start($tag, $args['attrs']);
         $field_middle = $this->middle($key, $tag, $args);

         $field_end = 'input' === $tag ? '' : "</{$tag}>";

         if ('password' === $args['attrs']['type']) {
            $field_end .= '</div>';
         }

         $output = $field_start . $field_middle . $field_end;
      }

      if ($echo) {
         echo $output;
      } else {
         return $output;
      }
   }

   public function label($key, $attrs = [], $p_attrs = [], $echo = true)
   {
      if (!in_array($key, array_keys($this->fields))) {
         $output = '';
      } else {
         $args = $this->fields[$key];

         if (empty($args['label'])) {
            $output = '';
         } else {
            $attrs = $this->parse_attrs($attrs);

            $output = "<label for=\"{$key}\" {$attrs}>{$args['label']}</label>" . PHP_EOL;

            if (!empty($args['description']) && false !== $p_attrs) {
               $p_attrs = $this->parse_attrs($p_attrs);

               $output .= "<p {$p_attrs}>{$args['description']}</p>";
            }
         }
      }

      if ($echo) {
         echo $output;
      } else {
         return $output;
      }
   }

   private function add_field($key, $args)
   {
      $defaults = [
         'type'        => 'string',
         'required'    => false,
         'title'       => null,
         'description' => null,
      ];

      $this->raw[$key] = $args;

      $args = wp_parse_args($args, $defaults);

      switch ($args['type']) {
         // radio/checkbox
         case 'boolean':
            $tag            = 'input';
            $attrs['type']  = 'checkbox';
            $attrs['value'] = 'true';
            break;

            // recursivo
         case 'object':
            break;

            // select[multiple]/checkbox/radio,maxItems=1
         case 'array':
            break;

         case 'number':
            $tag           = 'input';
            $attrs['type'] = 'number';
            $attrs['step'] = '0.1';
            break;

         case 'integer':
            $tag           = 'input';
            $attrs['type'] = 'number';
            $attrs['step'] = 1;
            break;

         case 'string':
            $tag = 'input';

            if (isset($args['format'])) {
               switch ($args['format']) {
                  case 'email':
                     $attrs['type'] = 'email';
                     break;

                  case 'url':
                     $attrs['type'] = 'url';
                     break;

                  case 'datetime':
                     $attrs['type'] = 'datetime-local';
                     break;

                  case 'date':
                     $attrs['type'] = 'date';
                     break;

                  case 'time':
                     $attrs['type'] = 'time';
                     break;

                  case 'month':
                     $attrs['type'] = 'month';
                     break;

                  case 'week':
                     $attrs['type'] = 'week';
                     break;

                  case 'password':
                     $attrs['type'] = 'password';
                     break;

                  case 'search':
                     $attrs['type'] = 'search';
                     break;

                  case 'hex-color':
                     $attrs['type'] = 'color';
                     break;

                  case 'tel':
                     $attrs['type'] = 'tel';
                     break;

                  default:
                     $attrs['type'] = 'text';
                     break;
               }
            } else {
               $attrs['type'] = 'text';
            }
            break;

         case 'file':
            $tag           = 'input';
            $attrs['type'] = 'file';

            if (isset($args['format'])) {
               switch ($args['format']) {
                  case 'image':
                     $attrs['accept'] = 'image/jpeg,image/png,image/gif,image/webp';
                     break;

                  case 'audio':
                     $attrs['accept'] = 'audio/*';
                     break;

                  case 'video':
                     $attrs['accept'] = 'video/*';
                     break;

                  case 'word':
                     $attrs['accept'] = '.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                     break;

                  default:
                     $attrs['accept'] = '*';
                     break;
               }
            }
            break;
      }

      if (!empty($args['multipleOf'])) {
         $attrs['step'] = $args['multipleOf'];
      }

      if (isset($args['minimum'])) {
         $attrs['min'] = $args['minimum'];
      }

      if (isset($args['maximum'])) {
         $attrs['max'] = $args['maximum'];
      }

      if (!empty($args['minLength'])) {
         $attrs['minlength'] = (int) $args['minLength'];
      }

      if (!empty($args['maxLength'])) {
         $attrs['maxlength'] = (int) $args['maxLength'];
      }

      if (!empty($args['pattern'])) {
         $attrs['pattern'] = $args['pattern'];
      }

      if (!empty($args['default'])) {
         $attrs['value'] = $args['default'];
      }

      if (!empty($args['enum'])) {
         $list = $args['enum'];

         if ('input' === $tag) {
            $attrs['list'] = "list-{$key}";
         }
      }

      // 'items',

      // 'properties',
      // 'additionalProperties',
      // 'patternProperties',
      // 'minProperties',
      // 'maxProperties',

      // 'uniqueItems',
      // 'anyOf',
      // 'oneOf',

      $attrs['id']   = $key;
      $attrs['name'] = $key;

      if (in_array($attrs['type'], ['text', 'search', 'email', 'password', 'url']) || 'textarea' === $tag) {
         $attrs['placeholder'] = $args['description'] ?? $args['title'] ?? false;
      }

      $attrs['data-test'] = "{$tag}-{$key}";
      $attrs['required']  = $args['required'];

      return [
         'tag'         => $tag,
         'label'       => $args['title'],
         'description' => $args['description'],
         'attrs'       => $attrs,
         'list'        => $list ?? null,
      ];
   }

   private function middle($key, $tag, $args)
   {
      $value = $args['attrs']['value'] ?? null;

      if ('textarea' === $tag) {
         if (empty($value)) {
            return '';
         }

         return $value;
      }

      if ('password' === $args['attrs']['type']) {
         return "<button type=\"button\" x-on:click.prevent=\"peak=!peak\" style=\"cursor:pointer\"><i x-bind:class=\"peak ? 'fa-solid fa-eye-slash ri-eye-off-fill' : 'fa-solid fa-eye ri-eye-fill'\"></i></button>";
      }

      if (empty($args['list']) || 'input' === $tag && 'hidden' === $args['attrs']['type']) {
         return '';
      }

      $list = $args['list'];
      $keys = array_keys($list);

      if (0 !== $keys[0] && is_string($keys[0])) {
         $list = array_flip($list);
      }

      if (
         !empty($this->raw[$key]['format']) && 'integer' === $this->raw[$key]['type']
      ) {
         $list = $this->populate_list($key, $list);
      }

      $middle = '';

      if ('input' === $tag && in_array($args['attrs']['type'], ['text', 'search', 'url', 'tel', 'email', 'number', 'month', 'week', 'date', 'time', 'datetime-local', 'range', 'color'])) {
         $middle .= "<datalist id=\"list-{$args['id']}\">\r\n";

         foreach ($list as $val) {
            $middle .= "<option value=\"{$val}\"></option>\r\n";
         }
         $middle .= '</datalist>' . "\r\n";

         return $middle;
      }

      if (!empty($args['default']) && empty($value)) {
         $value = $args['default'];
      }

      if (empty($this->raw[$key]['required'])) {
         $middle .= '<option value="" ' . selected('', $value, false) . '></option>' . PHP_EOL;
      } else {
         $middle .= '<option value="" ' . selected('', $value, false) . ' disabled>(Selecione uma opção)</option>' . PHP_EOL;
      }

      foreach ($list as $key => $val) {
         $selected = selected($key, $value, false);
         $middle .= "<option value=\"{$key}\" {$selected}>{$val}</option>" . PHP_EOL;
      }

      return $middle;
   }

   private function parse_attrs($attrs)
   {
      if (empty($attrs)) {
         return '';
      }

      foreach ($attrs as $key => $val) {
         if (is_bool($val) || is_null($val)) {
            if (false !== $val) {
               $new_attrs[] = $key;
            }

            continue;
         }

         $new_attrs[] = $key . '="' . esc_attr($val) . '"';
      }

      return implode(' ', $new_attrs);
   }

   private function populate_list($key, $list)
   {
      $objects = explode(':', $this->raw[$key]['format']);
      $type    = $objects[1] ?? null;

      switch ($objects[0]) {
         case 'term':
            $new_list = get_terms([
               'include'    => $list,
               'taxonomy'   => $type,
               'hide_empty' => 0,
               'fields'     => 'id=>name',
            ]);
            break;

         case 'comment':
            $new_list = get_comments([]);
            break;

         case 'post':
            $new_list = get_posts([]);
            break;

         case 'user':
            $new_list = get_users([]);
            break;

         default:
            break;
      }

      return $new_list;
   }

   private function start($tag, $attrs)
   {
      if (in_array($tag, ['textarea', 'select'])) {
         unset($attrs['value']);
      }

      $container_start = '';
      $style           = '';

      $class = $attrs['class'] ?? '';

      if ('password' === $attrs['type']) {
         $attrs['x-bind:type'] = "peak ? 'text' : 'password'";
         $container_start      = "<div class=\"{$class}\" x-data=\"{peak: false}\" style=\"display:flex\">";
         $style                = 'style="outline:none;flex-grow:1"';

         unset($attrs['class'], $attrs['type']);
      }

      $attrs_parsed = $this->parse_attrs($attrs);

      $end = '';

      if ('input' === $tag) {
         $end = '/';
      }

      $input = "<{$tag} {$attrs_parsed} {$end} {$style}>" . PHP_EOL;

      return $container_start . $input;
   }
}
