<?php

namespace cavWP;

/**
 * A list of sorters to use with `usort()`, `uksort()` or `uasort()`.
 */
final class Sorters
{
   /**
    * Sort by `category` key descending.
    *
     * @param array $a An array with `category` key.
     * @param array $b An array with `category` key.
    *
     * @return int
    *
    * @since 1.0.0 Introduced.
    */
   public static function cat_col(array $a, array $b)
   {
      return strcmp($a['category'], $b['category']);
   }

   /**
    * Sort by `category` key descending.
    *
     * @param array $a An array with `category` key.
     * @param array $b An array with `category` key.
    *
     * @return int
    *
    * @since 1.0.0 Introduced.
    */
   public static function cat_col_desc(array $a, array $b)
   {
      return strcmp($b['category'], $a['category']);
   }

   public static function file_col($a, $b)
   {
      return strcmp($a['file'], $b['file']);
   }

   public static function file_col_desc($a, $b)
   {
      return strcmp($b['file'], $a['file']);
   }

   public static function file_length_desc($a, $b)
   {
      return strlen($b['file']) - strlen($a['file']);
   }

   /**
    * Sort by string length.
    *
     * @param string $a
     * @param string $b
    *
     * @return int
    *
    * @since 1.0.0 Introduced.
    */
   public static function length(string $a, string $b)
   {
      return strlen($a) - strlen($b);
   }

   /**
    * Sort by string length descending.
    *
     * @param string $a
     * @param string $b
    *
     * @return int
    *
    * @since 1.0.0 Introduced.
    */
   public static function length_desc(string $a, string $b)
   {
      return strlen($b) - strlen($a);
   }

   /**
    * Sort by a numeric `order` key.
    *
     * @param array $a An array with `order` key.
     * @param array $b An array with `order` key.
    *
     * @return int
    *
    * @since 1.0.0 Introduced.
    */
   public static function order_col(array $a, array $b)
   {
      return (int) $a['order'] - (int) $b['order'];
   }

   /**
    * Sort by a numeric `order` key descending.
    *
     * @param array $a An array with `order` key.
     * @param array $b An array with `order` key.
    *
     * @return int
    *
    * @since 1.0.0 Introduced.
    */
   public static function order_col_desc(array $a, array $b)
   {
      return (int) $b['order'] - (int) $a['order'];
   }

   public static function order_then_file_cols($a, $b)
   {
      if ($a['order'] === $b['order']) {
         return self::file_length_desc($a, $b);
      }

      return $a['order'] - $b['order'];
   }
}
