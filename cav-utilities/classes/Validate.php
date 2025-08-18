<?php

namespace cavWP;

use DateTimeImmutable;
use WP_Error;

/**
 * Validates data from REST API requests.
 */
class Validate
{
   private $attribute;
   private $character_checkers = ['special', 'lowercase', 'uppercase', 'number'];
   private $date               = ['datetime', 'date', 'time', 'month', 'week'];
   private $formats            = ['url', 'cnpj', 'cpf', 'cpf_or_cnpj', 'credit_card'];
   private $key;
   private $objects = ['post', 'term', 'user', 'comment'];
   private $value;

   /**
    * Expected to be used as a validate_callback in $args of register_rest_route().
    *
    * @since 1.0.0
    *
     * @param mixed $value
     * @param mixed $request
     * @param mixed $key
    *
    * * @return true|WP_Error
    */
   public function check($value, $request, $key)
   {
      $attributes = $request->get_attributes();

      $attribute = $attributes['args'][$key];

      if (empty($attribute['type']) || 'null' === $attribute['type']) {
         return true;
      }

      $this->key   = $key;
      $this->value = $value;
      // types => string,integer,boolean,array,object,number
      $this->attribute = $attribute;

      $callbacks = [];

      // BY FORMAT
      if (isset($attribute['format'])) {
         foreach ($this->objects as $object) {
            if (str_starts_with($attribute['format'], $object)) {
               $callbacks[] = $object;
               break;
            }
         }

         foreach ($this->formats as $format) {
            if ($attribute['format'] === $format) {
               $callbacks[] = $format;
               break;
            }
         }

         if (in_array($attribute['format'], $this->date)) {
            $callbacks[] = 'date';
         }
      }

      if (!empty($attribute['checks_unique'])) {
         $callbacks[] = 'checks_unique';
      }

      $character_checks = ['has', 'all', 'not'];

      foreach ($character_checks as $check) {
         if (isset($attribute[$check]) && is_array($attribute[$check])) {
            foreach ($attribute[$check] as $method) {
               if (!in_array($method, $this->character_checkers)) {
                  continue;
               }

               $callbacks[] = [$method, $check];
            }
         }
      }

      foreach ($callbacks as $callback) {
         if (is_array($callback)) {
            $result = $this->{$callback[0]}($callback[1]);
         } else {
            $result = $this->{$callback}();
         }

         if (is_wp_error($result)) {
            return $result;
         }
      }

      return true;
   }

   public function is_cnpj($number)
   {
      $cnpj = preg_replace('/[^\d]/', '', (string) $number);

      // Valida tamanho
      if (strlen($cnpj) !== 14) {
         return false;
      }

      // Verifica se todos os digitos são iguais
      if (preg_match('/(\d)\1{13}/', $cnpj)) {
         return false;
      }

      // Valida primeiro dígito verificador
      for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
         $soma += $cnpj[$i] * $j;
         $j = (2 === $j) ? 9 : $j - 1;
      }

      $resto = $soma % 11;

      if ($cnpj[12] !== ($resto < 2 ? 0 : 11 - $resto)) {
         return false;
      }

      // Valida segundo dígito verificador
      for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
         $soma += $cnpj[$i] * $j;
         $j = (2 === $j) ? 9 : $j - 1;
      }

      $resto = $soma % 11;

      return $cnpj[13] === ($resto < 2 ? 0 : 11 - $resto);
   }

   public function is_cpf($number)
   {
      // Extrai somente os números
      $cpf = preg_replace('/[^\d]/is', '', (string) $number);

      // Verifica se foi informado todos os digitos corretamente
      if (strlen($cpf) !== 11) {
         return false;
      }

      // Verifica se foi informada uma sequência de digitos repetidos. Ex: 111.111.111-11
      if (preg_match('/(\d)\1{10}/', $cpf)) {
         return false;
      }

      // Faz o calculo para validar o CPF
      for ($t = 9; $t < 11; $t++) {
         for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
         }
         $d = ((10 * $d) % 11) % 10;

         if ($cpf[$c] !== $d) {
            return false;
         }
      }

      return true;
   }

   /**
    * Checks if it matches the mask.
    * Use "0" for numbers and "X" for letters. ("x" for lowercase).
    * Is it possible to use the "?" to make the previous element optional.
    *
    * Type needs to be "string".
    *
    * @since 1.0.0
    *
     * @param string       $format
     * @param array|string $optionals if string, a custom error message or an array with ['message']
    *
     * @return true|WP_Error
    */
   public function mask(string $format)
   {
      $defaults = [
         'cep'      => '99999-999',
         'cnpj'     => '99.999.999/9999-99',
         'cpf'      => '999.999.999-99',
         'phone_br' => '(99) ?9?9999-9999',
      ];

      $format = $defaults[$format] ?? $format;

      $pattern = preg_quote($format, '/');
      $pattern = str_replace('9', '\d', $pattern);
      $pattern = str_replace('a', '[a-zA-Z]', $pattern);
      $pattern = str_replace('\?', '?', $pattern);

      if (!preg_match('/^' . $pattern . '$/', $this->value)) {
         return $this->_error(
            esc_attr__('%s its not in the expect format.', 'cav-utilities'),
         );
      }

      return true;
   }

   /**
    * Checks that the value does not exceed the maximum.
    *
    * @since 1.0.0
    * Type needs to be "string", "number", "file", "date" or "array".
    *
     * @param float|string $length    if field type is "file", must be informed in MB
     * @param string       $message
     * @param array|string $optionals if string, a custom error message or an array with ['message']
    *
     * @return true|WP_Error
    */
   public function max(float|string $length, string $message = '')
   {
      if (!empty($attribute['required']) && empty($this->value)) {
         return true;
      }

      switch ('') {
         case 'date':
            $length_date = DateTimeImmutable::createFromFormat('', $length);
            $input_date  = DateTimeImmutable::createFromFormat('', $this->value);

            if (false === $length_date) {
               trigger_error(
                  esc_attr__('O formato esperado é %s.', 'cav-utilities'),
                  E_USER_ERROR,
               );
            } else {
               if ($input_date < $length_date) {
                  return $this->_error(
                     esc_attr__('Precisa ser uma data posterior a %s.', 'cav-utilities'),
                     $message,
                  );
               }
            }

            break;

         default:
            break;
      }

      return true;
   }

   /**
    * Checks that the value is not less than the minimum.
    *
    * @since 1.0.0
    * Type needs to be "string", "number", "file", "date" or "array".
    *
     * @param float|string $length    if field type is "file", must be informed in MB
     * @param array|string $optionals if string, a custom error message or an array with ['message']
    *
     * @return true|WP_Error
    */
   public function min(float|string $length, array|string $optionals = [])
   {
      if (!empty($attribute['required']) && empty($this->value)) {
         return $this;
      }

      switch ('') {
         case 'date':
            $length_date = DateTimeImmutable::createFromFormat('', $length);
            $input_date  = DateTimeImmutable::createFromFormat('', $this->value);

            if (false === $length_date) {
               trigger_error(
                  esc_attr__('O formato esperado é %s.', 'cav-utilities'),
                  E_USER_ERROR,
               );
            } else {
               if ($input_date > $length_date) {
                  return $this->_error(
                     esc_attr__('Precisa ser anterior a data %s.', 'cav-utilities'),
                  );
               }
            }

            break;

         default:
            break;
      }

      return $this;
   }

   /**
    * Parse and returns a error.
    *
    * @since 1.0.0
    *
     * @param string $message
    *
     * @return WP_Error
    */
   private function _error(string $message)
   {
      $message = sprintf($message, $this->attribute['title'] ?? $this->key);

      return new WP_Error('cav_rest_invalid_param', $message);
   }

   private function _get_type_from_format()
   {
      $check_type = explode(':', $this->attribute['format']);

      if (isset($check_type[1])) {
         return $check_type[1];
      }

      return null;
   }

   /**
    * Checks if value is unique object.
    *
    ** @return true|WP_Error
    *
    * @since 1.0.0
    */
   private function checks_unique()
   {
      switch ($this->attribute['checks_unique']) {
         case 'user_login':
            if (!get_user_by('login', $this->value)) {
               return $this->_error(__('Username already in use.', 'cav-utilities'));
            }
            break;

         case 'user_email':
            if (!email_exists($this->value)) {
               return $this->_error(__('E-mail already in use.', 'cav-utilities'));
            }
            break;

         default:
            break;
      }

      return true;
   }

   /**
    * Checks if value is a CNPJ.
    *
     * @return true|WP_Error
    *
    * @since 1.0.0
    */
   private function cnpj()
   {
      if (empty($this->value) && empty($this->attribute['required'])) {
         return true;
      }

      if ($this->is_cnpj($this->value)) {
         return true;
      }

      return $this->_error(
         esc_attr__('%s its not a valid CNPJ.', 'cav-utilities'),
      );
   }

   /**
    * Checks for a existing comment.
    *
     * @return true|WP_Error
    *
    * @since 1.0.0
    */
   private function comment()
   {
      if (
         0 === (int) $this->value && (isset($this->attribute['minimum']) && (int) $this->attribute['minimum'] === 0 || empty($this->attribute['required']))
      ) {
         return true;
      }

      $type = $this->_get_type_from_format();

      $comment_found = \get_comment($this->value);

      if (!empty($comment_found)) {
         if (empty($type)) {
            return true;
         }

         if ($type === $comment_found->comment_type) {
            return true;
         }
      }

      return $this->_error(esc_attr__('%s is not a valid comment ID', 'cav-utilities'));
   }

   /**
    * Checks if value is a CPF.
    *
     * @return true|WP_Error
    *
    * @since 1.0.0
    */
   private function cpf()
   {
      if (empty($this->value) && empty($this->attribute['required'])) {
         return true;
      }

      if ($this->is_cpf($this->value)) {
         return true;
      }

      return $this->_error(
         esc_attr__('%s its not a valid CPF.', 'cav-utilities'),
      );
   }

   /**
    * Checks if value is a CPF or CNPJ.
    *
     * @return true|WP_Error
    *
    * @since 1.0.0
    */
   private function cpf_or_cnpj()
   {
      if (empty($this->value) && empty($this->attribute['required'])) {
         return true;
      }

      if (!$this->is_cpf($this->value) && !$this->is_cnpj($this->value)) {
         return $this->_error(
            esc_attr__('%s its not a valid CPF or CNPJ.', 'cav-utilities'),
         );
      }

      return true;
   }

   /**
    * Checks if value is a credit card number.
    *
     * @return true|WP_Error
    *
    * @since 1.0.0
    */
   private function credit_card()
   {
      if (empty($this->value) && empty($this->attribute['required'])) {
         return true;
      }

      $only_numbers = preg_replace('/[^\d]/is', '', $this->value);

      $length = strlen($only_numbers);

      if (13 <= $length && $length <= 19) {
         $sum     = 0;
         $is_even = false;

         for ($i = $length - 1; $i >= 0; $i--) {
            $digit = (int) $only_numbers[$i];

            if ($is_even) {
               $digit *= 2;

               if ($digit > 9) {
                  $digit = ($digit % 10) + 1;
               }
            }

            $sum += $digit;
            $is_even = !$is_even;
         }

         if ($sum % 10 === 0) {
            return true;
         }
      }

      return $this->_error(
         esc_attr__('%s its not a valid credit card.', 'cav-utilities'),
      );
   }

   /**
    * Checks if value has the Y-m-d\TH:i datetime format.
    *
     * @return true|WP_Error
    *
    * @since 1.0.0
    */
   private function date()
   {
      if (empty($this->value) && empty($this->attribute['required'])) {
         return true;
      }

      if ('week' === $this->attribute['format']) {
         if (!preg_match('/\d{4}\-W[0-5][0-9]/', $this->value)) {
            return $this->_error(
               esc_attr__('%s its not a valid date.', 'cav-utilities'),
            );
         }

         return true;
      }

      $formats = [
         'datetime' => 'Y-m-d\TH:i',
         'date'     => 'Y-m-d',
         'time'     => 'H:i',
         'month'    => 'Y-m',
      ];
      $format = $formats[$this->attribute['format']];

      $date = DateTimeImmutable::createFromFormat($format, $this->value);

      if (false === $date || $date->format($format) !== $this->value) {
         return $this->_error(
            esc_attr__('%s its not a valid date.', 'cav-utilities'),
         );
      }

      return true;
   }

   /**
    * Check if value contains lowercase characters.
    *
    * @since 1.0.0
    *
     * @param mixed $method
    *
     * @return true|WP_Error
    */
   private function lowercase($method)
   {
      if (empty($this->value) && empty($this->attribute['required'])) {
         return true;
      }

      if ('all' === $method && !\ctype_lower($this->value)) {
         return $this->_error(
            esc_attr__('%s: All characters must lowercase.', 'cav-utilities'),
         );
      }

      preg_match_all('/([a-zà-öø-ÿ])/', $this->value, $matches);

      $count = 0;

      if (!empty($matches[0])) {
         $count = count($matches[0]);
      }

      if ('has' === $method && 0 === $count) {
         return $this->_error(
            esc_attr__('%s: Must contain at least one lowercase character.', 'cav-utilities'),
         );
      }

      if ('not' === $method && $count >= 1) {
         return $this->_error(
            esc_attr__('%s: Must not contain any lowercase characters.', 'cav-utilities'),
         );
      }

      return true;
   }

   /**
    * Check if value contains numbers.
    *
    * @since 1.0.0
    *
     * @param mixed $method
    *
     * @return true|WP_Error
    */
   private function number($method)
   {
      if (empty($this->value) && empty($this->attribute['required'])) {
         return true;
      }

      if ('all' === $method && !\ctype_digit($this->value)) {
         return $this->_error(
            esc_attr__('%s: All characters must be number.', 'cav-utilities'),
         );
      }

      preg_match_all('/(\d)/', $this->value, $matches);

      $count = 0;

      if (!empty($matches[0])) {
         $count = count($matches[0]);
      }

      if ('has' === $method && 0 === $count) {
         return $this->_error(
            esc_attr__('%s: Must contain at least one number.', 'cav-utilities'),
         );
      }

      if ('not' === $method && $count >= 1) {
         return $this->_error(
            esc_attr__('%s: Must not contain any numbers.', 'cav-utilities'),
         );
      }

      return true;
   }

   /**
    * Checks if it is a phone from Brasil.
    *
     * @return true|WP_Error
    *
    * @since 1.0.0
    */
   private function phone_br()
   {
      if (empty($this->value) && empty($this->attribute['required'])) {
         return true;
      }

      $ddds = array_merge(
         range(11, 19),
         [21, 22, 24, 27, 28],
         range(31, 35),
         [37, 38],
         range(41, 49),
         [51],
         range(53, 55),
         range(61, 69),
         [71],
         range(73, 75),
         [77, 79],
         range(81, 89),
         range(91, 99),
      );

      $only_numbers = preg_replace('/[^\d]/is', '', $this->value);

      $phone_ddd = substr($only_numbers, 0, 2);

      if (in_array($phone_ddd, $ddds)) {
         $phone_number = substr($only_numbers, 2);
         $phone_length = strlen($phone_number);

         if (
            9 === $phone_length && '9' === $phone_number[0] || 8 === $phone_length && !in_array($phone_number[0], ['0', '1'])
         ) {
            return true;
         }
      }

      return $this->_error(
         esc_attr__('%s its not a valid phone number.', 'cav-utilities'),
      );
   }

   /**
    * Checks for a existing post .
    *
     * @return true|WP_Error
    *
    * @since 1.0.0
    */
   private function post()
   {
      if (
         0 === (int) $this->value && (isset($this->attribute['minimum']) && (int) $this->attribute['minimum'] === 0 || empty($this->attribute['required']))
      ) {
         return true;
      }

      $type = $this->_get_type_from_format();

      $post_found = \get_post($this->value);

      if (!empty($post_found)) {
         if (empty($type)) {
            return true;
         }

         if ($type === $post_found->post_type) {
            return true;
         }
      }

      return $this->_error(esc_attr__('%s is not a valid post ID', 'cav-utilities'));
   }

   /**
    * Check if value contains special characters.
    *
    * @since 1.0.0
    *
     * @param mixed $method
    *
     * @return true|WP_Error
    */
   private function special($method)
   {
      if (empty($this->value) && empty($this->attribute['required'])) {
         return true;
      }

      $chars = str_split('!"#$%&()*+,-/:;>=<?@\[]^_`´{|}~');
      $value = str_split($this->value);

      $count = 0;

      foreach ($value as $letter) {
         if (in_array($letter, $chars)) {
            $count++;
         }
      }

      if ('has' === $method && 0 === $count) {
         return $this->_error(
            esc_attr__('%s: Must contain at least one special character.', 'cav-utilities'),
         );
      }

      if ('not' === $method && $count >= 1) {
         return $this->_error(
            esc_attr__('%s: Must not contain any special characters.', 'cav-utilities'),
         );
      }

      if ('all' === $method && strlen($this->value) !== $count) {
         return $this->_error(
            esc_attr__('%s: All characters must be special.', 'cav-utilities'),
         );
      }

      return true;
   }

   /**
    * Checks for a existing term .
    *
     * @return true|WP_Error
    *
    * @since 1.0.0
    */
   private function term()
   {
      if (
         0 === (int) $this->value && (isset($this->attribute['minimum']) && (int) $this->attribute['minimum'] === 0 || empty($this->attribute['required']))
      ) {
         return true;
      }

      $type = $this->_get_type_from_format();

      $term_found = \get_term($this->value);

      if (!empty($term_found) && !is_wp_error($term_found)) {
         if (empty($type)) {
            return true;
         }

         if ($type === $term_found->taxonomy) {
            return true;
         }
      }

      return $this->_error(esc_attr__('%s is not a valid term ID', 'cav-utilities'));
   }

   /**
    * Check if value contains uppercase characters.
    *
    * @since 1.0.0
    *
     * @param mixed $method
    *
     * @return true|WP_Error
    */
   private function uppercase($method)
   {
      if (empty($this->value) && empty($this->attribute['required'])) {
         return true;
      }

      if ('all' === $method && !\ctype_upper($this->value)) {
         return $this->_error(
            esc_attr__('%s: All characters must uppercase.', 'cav-utilities'),
         );
      }

      preg_match_all('/([A-ZÀ-ÖØ-Þ])/', $this->value, $matches);

      $count = 0;

      if (!empty($matches[0])) {
         $count = count($matches[0]);
      }

      if ('has' === $method && 0 === $count) {
         return $this->_error(
            esc_attr__('%s: Must contain at least one uppercase character.', 'cav-utilities'),
         );
      }

      if ('not' === $method && $count >= 1) {
         return $this->_error(
            esc_attr__('%s: Must not contain any uppercase characters.', 'cav-utilities'),
         );
      }

      return true;
   }

   /**
    * Checks if valid is a valid URL.
    *
    * @since 1.0.0
    */
   private function url()
   {
      if (empty($this->value) && empty($this->attribute['required'])) {
         return true;
      }

      if (!preg_match('/^(http|https):\/\/[a-z0-9_]+([\-\.]{1}[a-z_0-9]+)*\.[_a-z]{2,5}((:[0-9]{1,5})?\/.*)?$/i', $this->value)) {
         return $this->_error(
            esc_attr__('%s must be a valid URL.', 'cav-utilities'),
         );
      }

      return true;
   }

   /**
    * Checks for a existing user .
    *
     * @return true|WP_Error
    *
    * @since 1.0.0
    */
   private function user()
   {
      if (
         0 === (int) $this->value && (isset($this->attribute['minimum']) && (int) $this->attribute['minimum'] === 0 || empty($this->attribute['required']))
      ) {
         return true;
      }

      $type = $this->_get_type_from_format();

      if (!empty($type) && 'current' === $type && get_current_user_id() === (int) $this->value) {
         return true;
      }

      $user_found = \get_user_by('id', $this->value);

      if (!empty($user_found)) {
         if (empty($type)) {
            return true;
         }

         if ($user_found->has_cap($type)) {
            return true;
         }
      }

      return $this->_error(esc_attr__('%s is not a valid user ID', 'cav-utilities'));
   }
}
