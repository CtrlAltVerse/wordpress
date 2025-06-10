<?php

namespace cavWP;

/**
 * A list of sorters to use with usort().
 */
final class Sorters
{
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
}
