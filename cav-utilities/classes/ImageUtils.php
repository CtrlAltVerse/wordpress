<?php

namespace cavWP;

class ImageUtils
{
   public static function circle_crop(&$dest, $src_url, $size, $x, $y)
   {
      $is_jpg_or_tiff = true;

      if (str_starts_with($src_url, 'https://secure.gravatar.com')) {
         $is_jpg_or_tiff = false;
      }

      $src = self::create($src_url, $size, $size);

      if ($is_jpg_or_tiff) {
         $temp_file = tempnam(sys_get_temp_dir(), 'exif_img');
         file_put_contents($temp_file, file_get_contents($src_url));

         $exif = exif_read_data($temp_file);

         if ($exif && isset($exif['Orientation'])) {
            $orientation = $exif['Orientation'];

            switch ($orientation) {
               case 3:
                  $deg = 180;
                  break;

               case 6:
                  $deg = 270;
                  break;

               case 8:
                  $deg = 90;
                  break;
            }

            if ($deg) {
               $src = imagerotate($src, $deg, 0);
            }
         }

         unlink($temp_file);
      }

      $circle = imagecreatetruecolor($size, $size);
      imagecopy($circle, $src, 0, 0, 0, 0, $size, $size);

      $mask            = imagecreatetruecolor($size, $size);
      $maskTransparent = imagecolorallocate($mask, 255, 0, 255);
      imagecolortransparent($mask, $maskTransparent);
      imagefilledellipse($mask, ceil($size / 2), ceil($size / 2), $size, $size, $maskTransparent);
      imagecopymerge($circle, $mask, 0, 0, 0, 0, $size, $size, 100);

      $circleTransparent = imagecolorallocate($circle, 255, 0, 255);
      imagefill($circle, 0, 0, $circleTransparent);
      imagefill($circle, $size - 1, 0, $circleTransparent);
      imagefill($circle, 0, $size - 1, $circleTransparent);
      imagefill($circle, $size - 1, $size - 1, $circleTransparent);
      imagecolortransparent($circle, $circleTransparent);

      imagecopymerge($dest, $circle, $x, $y, 0, 0, $size, $size, 100);

      imagedestroy($src);
      imagedestroy($mask);
      imagedestroy($circle);
   }

   public static function create($source, $width = null, $height = null)
   {
      $info = getimagesize($source);

      switch ($info['mime']) {
         case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;

         case 'image/png':
            $image = imagecreatefrompng($source);
            break;

         case 'image/gif':
            $image = imagecreatefromgif($source);
            break;

         default:
            return;
      }

      if (is_null($width) || is_null($height)) {
         return $image;
      }

      $resized = imagecreatetruecolor($width, $height);
      $image_x = imagesx($image);
      $image_y = imagesy($image);

      imagecopyresized($resized, $image, 0, 0, 0, 0, $width, $height, $image_x, $image_y);

      return $resized;
   }

   public static function rect(&$img, $x1, $y1, $x2, $y2, $color, $radius = 16)
   {
      imagefilledrectangle($img, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
      imagefilledrectangle($img, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);

      imagefilledellipse($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
      imagefilledellipse($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
      imagefilledellipse($img, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
      imagefilledellipse($img, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
   }
}
