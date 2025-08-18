<?php

namespace cavWP;

/*
day ---
N	ISO 8601 numeric representation of the day of the week	1 (for Monday) through 7 (for Sunday)


Full Date/Time	---	---
c	ISO 8601 date	2004-02-12T15:19:21+00:00

*/
class Dekatrian
{
   public const DAYS = [
      1 => 'Acronian',
      2 => 'Sincronian',
   ];
   public const MONTHS = [
      0  => 'O',
      1  => 'Auroran',
      2  => 'Borean',
      3  => 'Coronian',
      4  => 'Driadan',
      5  => 'Electran',
      6  => 'Faian',
      7  => 'Gaian',
      8  => 'Hermetian',
      9  => 'Irisian',
      10 => 'Kaosian',
      11 => 'Lunan',
      12 => 'Maian',
      13 => 'Nixan',
   ];
   private const KEYS = [
      'd', 'D', 'j', 'l', 'N', 'S', 'w', 'z', 'W', 'F', 'm', 'M', 'n', 't', 'L', 'o', 'X', 'x', 'Y', 'y', 'a', 'A', 'B', 'g', 'G', 'h', 'H', 'i', 's', 'u', 'v', 'e', 'I', 'O', 'P', 'p', 'T', 'Z', 'c', 'r', 'U', 'Q'];
   private $dekatrian;
   private $gregorian;

   public function __construct($datetime = 'now', $timezone = null)
   {
      $this->gregorian = new \DateTimeImmutable($datetime, $timezone);
   }

   public function format($format = 'Qj F Y')
   {
      $this->dekatrian = $this->convert();

      $return = '';

      $format = preg_replace('/(?<!\\\)r/', DATE_RFC2822, $format);
      $format = preg_replace('/(?<!\\\)c/', DATE_ISO8601_EXPANDED, $format);

      if (0 === $this->dekatrian['n']) {
         $format = str_replace(['Qj F'], 'Q', $format);
      }

      for ($i = 0; $i < strlen($format); $i++) {
         $key = $format[$i];

         if (!in_array($key, $this::KEYS) && '\\' !== $key) {
            $return .= $key;
            continue;
         }

         if ($format[$i - 1] === '\\') {
            $return .= $key;
            continue;
         }

         if (in_array($key, array_keys($this->dekatrian))) {
            $return .= (string) $this->dekatrian[$key];
            continue;
         }
         $return .= (string) $this->gregorian->format($key);
      }

      return $return;
   }

   public function setDate(int $year, int $month, int $day): void
   {
      $this->gregorian->setDate($year, $month, $day);
   }

   private function convert()
   {
      $day_of_the_year = (int) $this->gregorian->format('z');

      if ($this->gregorian->format('L')) {
         $day_of_the_year--;

         if ($this->gregorian->format('j-n') === '2-1') {
            return $this->set(366, 2, 0, -1);
         }
      }

      if ($this->gregorian->format('j-n') === '1-1') {
         return $this->set(365, 1, 0, -1);
      }

      $month = floor($day_of_the_year / 28);
      $day   = $day_of_the_year - ($month * 28);
      $month++;

      if (empty($day)) {
         $day = 28;
         $month--;
      }

      return $this->set($day_of_the_year, $day, $month);
   }

   private function set($day_of_the_year, $day, $month, $year = 0)
   {
      global $wp_locale;

      $year = (int) $this->gregorian->format('Y') + $year;

      if (0 === $year) {
         $year = -1;
      }
      $year_pad         = str_pad(abs($year), 4, '0', STR_PAD_LEFT);
      $weekday_name     = $wp_locale->get_weekday($this->gregorian->format('w'));
      $week_of_the_year = ceil($day_of_the_year / 7);

      return [
         'D' => $wp_locale->get_weekday_abbrev($weekday_name),
         'd' => str_pad($day, 2, '0', STR_PAD_LEFT),
         'F' => $this::MONTHS[$month],
         'j' => $day,
         'l' => $weekday_name,
         'm' => str_pad($month, 2, '0', STR_PAD_LEFT),
         'M' => substr($this::MONTHS[$month], 0, 3),
         'n' => $month,
         't' => 0 === $month ? 0 : 28,
         'Q' => 0 === $month ? $this::DAYS[$day] : '',
         'q' => 0 === $month ? substr($this::DAYS[$day], 0, 3) : '',
         'W' => $week_of_the_year <= 0 ? 1 : $week_of_the_year,
         'X' => ($year < 0 ? '-' : '+') . $year_pad,
         'x' => ($year < 0 ? '-' : ($year > 9999 ? '+' : '')) . $year_pad,
         'Y' => ($year < 0 ? '-' : '') . $year_pad,
         'y' => substr($year_pad, -2),
         'z' => $day_of_the_year - 1,
         'S' => Utils::number_suffix($day),
         'o' => $year,
      ];
   }
}
