<?php

namespace cavWP\Services;

class PageSpeed
{
   private $api_key;
   private $base_url   = 'https://www.googleapis.com/pagespeedonline/v5';
   private $categories = [
      'ACCESSIBILITY',
      'BEST_PRACTICES',
      'PERFORMANCE',
      'SEO',
   ];
   private $strategies = [
      'DESKTOP',
      'MOBILE',
   ];

   public function __construct()
   {
      $this->api_key = get_option('cav-seo_links-pagespeed_apikey');
   }

   public function run_test(string $url, $strategy = 'DESKTOP')
   {
      $request = wp_remote_get("{$this->base_url}/runPagespeed?" . $this->set_params($url, $strategy), [
         'timeout' => 222,
      ], [
         'cache_duration' => '1 week',
      ]);

      if (\is_wp_error($request)) {
         return false;
      }

      if (200 !== wp_remote_retrieve_response_code($request)) {
         return false;
      }

      $body = json_decode(\wp_remote_retrieve_body($request), true);

      if (empty($body)) {
         return false;
      }

      $audits = array_filter($body['lighthouseResult']['audits'], function($audit) {
         if (1 === $audit['score']) {
            return false;
         }

         return !in_array($audit['scoreDisplayMode'], ['notApplicable', 'informative', 'manual']);
      });

      $categories = array_map(fn($category) => [
         'title'  => $category['title'],
         'score'  => $category['score'],
         'audits' => array_map(fn($audit) => $audits[$audit['id']], array_filter($category['auditRefs'], fn($audit) => in_array($audit['id'], array_keys($audits)))),
      ], $body['lighthouseResult']['categories']);

      return [
         'report'     => $categories,
         'datetime'   => $body['analysisUTCTimestamp'],
         'screenshot' => $body['lighthouseResult']['fullPageScreenshot']['screenshot']['data'],
      ];
   }

   public function set_params(string $url, string $strategy): string
   {
      $params = http_build_query([
         'url'      => $url,
         'key'      => $this->api_key,
         'locale'   => str_replace('_', '-', get_locale()),
         'strategy' => $strategy,
      ]);

      foreach ($this->categories as $category) {
         $params .= '&category=' . $category;
      }

      return $params;
   }
}
