<?php

// todo: cdn

namespace cavWP;

final class Parse_HTML
{
   private $_boolean_attrs = [
      'allowfullscreen',
      'async',
      'autofocus',
      'autoplay',
      'checked',
      'compact',
      'controls',
      'declare',
      'default',
      'defaultchecked',
      'defaultmuted',
      'defaultselected',
      'defer',
      'disabled',
      'enabled',
      'formnovalidate',
      'hidden',
      'indeterminate',
      'inert',
      'ismap',
      'itemscope',
      'loop',
      'multiple',
      'muted',
      'nohref',
      'noresize',
      'noshade',
      'novalidate',
      'nowrap',
      'open',
      'pauseonexit',
      'readonly',
      'required',
      'reversed',
      'scoped',
      'seamless',
      'selected',
      'sortable',
      'truespeed',
      'typemustmatch',
      'visible',
   ];
   private $_keep_empty_attrs    = ['alt', 'content', 'src'];
   private $_raw_tags            = ['code', 'pre', 'textarea'];
   private $_self_close_svg_tags = ['circle', 'rect', 'ellipse', 'line', 'path', 'polygon', 'polyline', 'text', 'use'];
   private $_self_close_tags     = [
      'area', 'basefont', 'base', 'br', 'col', 'command', 'embed', 'hr', 'frame', 'img', 'isindex', 'keygen', 'input', 'link', 'meta', 'param', 'source', 'track', 'source', 'wbr'];
   private $cdn_domain;
   private $cdn_extensions;
   private $cdn_on;
   private $compress_html;
   private $compress_inline_script;
   private $compress_inline_style;
   private $html = '';
   private $remove_comments;

   public function __construct($html = null)
   {
      $this->compress_html          = get_option('cav-minify');
      $this->compress_inline_script = get_option('cav-minify-inline_js');
      $this->compress_inline_style  = get_option('cav-minify-inline_css');
      $this->remove_comments        = get_option('cav-minify-remove_comments');

      $this->cdn_on         = get_option('cav-cdn');
      $this->cdn_domain     = get_option('cav-cdn-host');
      $this->cdn_extensions = get_option('cav-cdn-types');

      if (is_null($html)) {
         ob_start([$this, 'close_ob']);
      } else {
         $this->html = $this->close_ob($html);
      }
   }

   public function close_ob($html)
   {
      if (empty($html) || !is_string($html)) {
         return $html;
      }

      if ($this->compress_html) {
         $html = $this->parse_content($html);
      }

      if ($this->cdn_on && (bool) $this->cdn_domain && (bool) $this->cdn_extensions) {
         $html = $this->parse_cdn($html);
      }

      return $html;
   }

   public function get_html()
   {
      return $this->html;
   }

   private function checks_tag($token)
   {
      $tag = strtolower($token['tag'] ?? '');

      $type = null;

      if (!empty($tag)) {
         $type = 'normal';
      }

      if (in_array($tag, $this->_self_close_tags)) {
         $type = 'self';
      } elseif (in_array($tag, $this->_self_close_svg_tags)) {
         $type = 'svg';
      } elseif (str_ends_with($token[0], '/>')) {
         $type = 'self-others';
      }

      return ['name' => $tag, 'type' => $type];
   }

   private function parse_cdn($html)
   {
      $file_extensions = str_replace([' ', ',', ';'], '|', $this->cdn_extensions);

      $urls_regex = '#(?:(?:[\"\'\s=>,]|url\()\K|^)[^\"\'\s(=>,]+(' . quotemeta($file_extensions) . ')(\?[^\/?\\\"\'\s)>,]+)?(?:(?=\/?[?\\\"\'\s)>,])|$)#i';

      return preg_replace_callback($urls_regex, [$this, 'parse_url'], $html);
   }

   private function parse_comment($comment)
   {
      if ($this->remove_comments) {
         return '';
      }

      if ($this->compress_html) {
         return $this->remove_whitespace($comment);
      }

      return $comment;
   }

   private function parse_content($html)
   {
      preg_match_all(
         '/<(?<script>script).*?<\/script\s*>|<(?<style>style).*?<\/style\s*>|<!(?<comment>--).*?-->|<(?<tag>[\/\w.:-]*)(?:".*?"|\'.*?\'|[^\'">]+)*>|(?<text>((<[^!\/\w.:-])?[^<]*)+)|/si',
         $html,
         $tokens,
         PREG_SET_ORDER,
      );

      $tag_tree = [];
      $output   = '';

      foreach ($tokens as $token) {
         if (!empty($token['script'])) {
            $output .= $this->parse_script($token[0]);
            continue;
         }

         if (!empty($token['style'])) {
            $output .= $this->parse_style($token[0]);
            continue;
         }

         if (!empty($token['comment']) && !in_array(end($tag_tree), $this->_raw_tags)) {
            $output .= $this->parse_comment($token[0]);
            continue;
         }

         if (!empty($token['tag']) && str_starts_with($token['tag'], '/')) {
            array_pop($tag_tree);
            $output .= $token[0];
            continue;
         }

         $may_current_tag = $this->checks_tag($token);

         if ('normal' === $may_current_tag['type']) {
            $tag_tree[] = $may_current_tag['name'];
         }

         $output .= $this->parse_tag($token, end($tag_tree));
      }

      return $output;
   }

   private function parse_script($content)
   {
      if (!$this->compress_inline_script) {
         return $content;
      }

      return $this->remove_whitespace($content, 'script');
   }

   private function parse_style($style)
   {
      if (!$this->compress_inline_style) {
         return $style;
      }

      if ($this->remove_comments) {
         $style = preg_replace('/\/\*!.*?\*\//', '', $style);
      }

      return $this->remove_whitespace($style, 'script');
   }

   private function parse_tag($token, $current_tag)
   {
      if ($this->compress_html && !in_array($current_tag, $this->_raw_tags)) {
         $content = str_replace(' />', '/>', $token[0]);

         $keep_empty_attrs = implode('|', array_map(fn($attr) => '\b' . $attr, $this->_keep_empty_attrs));
         $content          = preg_replace('/(\s+)(\w++(?<!' . $keep_empty_attrs . ')="")/', '$1', $content);

         $boolean_attrs = implode('|', array_map(fn($attr) => '\b' . $attr, $this->_boolean_attrs));
         $content       = preg_replace('/(\s+)(\w++' . $boolean_attrs . ')=[\w\'\"]*/', '$1$2', $content);

         return $this->remove_whitespace($content, $token['tag']);
      }

      return $token[0];
   }

   private function parse_url($matches)
   {
      $file_url    = $matches[0];
      $local_host  = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : Utils::clean_domain(home_url());
      $target_host = Utils::clean_domain($this->cdn_domain);

      if (stripos($file_url, '//' . $local_host) !== false || stripos($file_url, '\/\/' . $local_host) !== false) {
         return substr_replace($file_url, $target_host, stripos($file_url, $local_host), strlen($local_host));
      }

      return $file_url;
   }

   private function remove_whitespace($content, $tag = '')
   {
      $content = str_replace("\t", ' ', $content);

      if ('script' !== $tag) {
         $content = str_replace(["\r\n", "\n", '\r\n', '\n'], ' ', $content);
      }

      while (stristr($content, '  ')) {
         $content = str_replace('  ', ' ', $content);
      }

      return $content;
   }
}
