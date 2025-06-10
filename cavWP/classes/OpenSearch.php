<?php

namespace cavWP;

use SimpleXMLElement;

/**
 * @ignore
 */
final class OpenSearch
{
   public function __construct()
   {
      add_action('init', [$this, 'add_rewrite']);
      add_action('template_redirect', [$this, 'load_content']);
      add_action('cav_head_metas', [$this, 'add_metatag']);

      add_filter('redirect_canonical', [$this, 'remove_trailingslashit']);
   }

   public function add_metatag(): void
   {
      $url  = home_url('opensearch.osdx');
      $name = get_bloginfo('name');

      echo '<link rel="search" type="application/opensearchdescription+xml" title="' . $name . '" href="' . $url . '">';
   }

   public function add_rewrite(): void
   {
      add_rewrite_rule('^opensearch\.osdx/?$', 'index.php?cav=opensearch', 'top');
   }

   public function load_content(): void
   {
      $cav_template = get_query_var('cav', false);

      if ('opensearch' !== $cav_template) {
         return;
      }

      header('Content-Type: application/opensearchdescription+xml; charset=UTF-8');
      header('Content-Disposition: attachment; filename="opensearch.osdx"');

      echo $this->get_content();
      exit;
   }

   public function remove_trailingslashit($redirect_url)
   {
      $cav_template = get_query_var('cav', false);

      if ('opensearch' !== $cav_template) {
         return $redirect_url;
      }

      return rtrim($redirect_url, '/');
   }

   private function get_content()
   {
      $index = new SimpleXMLElement(<<<'EOD'
         <OpenSearchDescription
         xmlns="http://a9.com/-/spec/opensearch/1.1/"
         xmlns:moz="http://www.mozilla.org/2006/browser/search/" />
      EOD);

      $index->addChild('ShortName', get_bloginfo('name'));

      if ($longname = get_option('cav-opensearch-longname')) {
         $index->addChild('LongName', $longname);
      }

      $index->addChild('Description', get_bloginfo('description'));

      if ($tags = get_option('cav-opensearch-tags')) {
         $index->addChild('Tags', $tags);
      }

      if ($contact = get_option('cav-opensearch-contact')) {
         $index->addChild('Contact', $contact);
      }

      if ($site_icon_id = get_option('site_icon')) {
         $metadata = \wp_get_attachment_metadata($site_icon_id);

         $image = $index->addChild('Image', \wp_get_attachment_image_url($site_icon_id, 'cav_favicon'));

         if (!empty($metadata['sizes']['cav_favicon']['height'])) {
            $image->addAttribute('height', $metadata['sizes']['cav_favicon']['height']);
         }

         if (!empty($metadata['sizes']['cav_favicon']['width'])) {
            $image->addAttribute('width', $metadata['sizes']['cav_favicon']['width']);
         }

         if (!empty($metadata['sizes']['cav_favicon']['mime-type'])) {
            $image->addAttribute('type', $metadata['sizes']['cav_favicon']['mime-type']);
         }
      }

      $url = $index->addChild('Url');
      $url->addAttribute('type', 'text/html');
      $url->addAttribute('template', str_replace('__term__', '{searchTerms}', get_search_link('__term__')));

      $query = $index->addChild('Query');
      $query->addAttribute('role', 'example');
      $query->addAttribute('searchTerms', 'cat');

      $index->addChild('Language', strtolower(get_bloginfo('language')));
      $index->addChild('OutputEncoding', 'UTF-8');
      $index->addChild('InputEncoding', 'UTF-8');

      $xml = $index->asXML();

      return str_replace('version="1.0"', 'version="1.0" encoding="UTF-8"', $xml);
   }
}
