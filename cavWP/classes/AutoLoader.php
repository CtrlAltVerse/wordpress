<?php

namespace cavWP;

/**
 * Automatically loads classes based on file names and namespaces.
 *
 * This class can handle multiple namespaces in multiple folders, utilizing an associative array, where the key is a namespace prefix and the value is an array of base directories for classes in that namespace.
 *
 * @since 1.0.0 Introduced.
 */
final class AutoLoader
{
   /** @ignore */
   private $namespaces = [];

   /**
    * @ignore
    */
   public function __construct()
   {
      spl_autoload_register([$this, 'load_class']);
   }

   /**
    * Adds a base directory for a namespace prefix.
    *
     * @param string $namespace The namespace.
     * @param string $base_path Directory to search for classes in this namespace.
    */
   public function add_namespace(string $namespace, string $base_path): void
   {
      $namespace = str_replace(['/', '\\'], '', $namespace);
      $namespace .= DIRECTORY_SEPARATOR;
      $base_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $base_path);
      $base_path .= DIRECTORY_SEPARATOR;

      $this->namespaces[$namespace][] = $base_path;
   }

   /**
    * @ignore
    *
     * @param string $class
    */
   public function load_class(string $class)
   {
      $prefix = $class;

      while (false !== $pos = strrpos($prefix, '\\')) {
         $prefix         = substr($class, 0, $pos + 1);
         $relative_class = substr($class, $pos + 1);
         $mapped_file    = $this->load_mapped_file($prefix, $relative_class);

         if ($mapped_file) {
            return $mapped_file;
         }

         $prefix = rtrim($prefix, '\\');
      }

      return false;
   }

   /**
    * @ignore
    *
     * @param string $prefix
     * @param string $relative_class
    */
   private function load_mapped_file(string $prefix, string $relative_class)
   {
      $prefix = rtrim($prefix, '\\') . DIRECTORY_SEPARATOR;

      if (!isset($this->namespaces[$prefix])) {
         return false;
      }

      foreach ($this->namespaces[$prefix] as $base_dir) {
         $file = $base_dir;
         $file .= str_replace(['\\'], DIRECTORY_SEPARATOR, $relative_class);
         $file .= '.php';

         if ($this->require_file($file)) {
            return $file;
         }
      }

      return false;
   }

   /**
    * @ignore
    *
     * @param string $file
    */
   private function require_file(string $file)
   {
      if (file_exists($file)) {
         require_once $file;

         return true;
      }

      return false;
   }
}
