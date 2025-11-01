<?php

namespace cavWP\Networks;

class Utils
{
   public static function decode_google_jwt($token)
   {
      $base64Url = explode('.', $token)[1];
      $base64    = str_replace(['-', '_'], ['+', '/'], $base64Url);

      return json_decode(base64_decode($base64, true), true);
   }

   public static function get_services($type_or_keys = 'share')
   {
      $socials = [
         'google' => [
            'name'  => 'Google',
            'icon'  => 'fa-brands fa-google ri-google-fill',
            'color' => '4285F4',
            'login' => 'google_client_id',
         ],
         'instagram' => [
            'name'    => 'Instagram',
            'icon'    => 'fa-brands fa-instagram ri-instagram-line',
            'color'   => 'E4405F',
            'profile' => 'https://www.instagram.com/%user%',
         ],
         'facebook' => [
            'name'    => 'Facebook',
            'icon'    => 'fa-brands fa-facebook ri-facebook-circle-fill',
            'color'   => '0865fe',
            'share'   => 'https://www.facebook.com/sharer/sharer.php?u=%link%&display=popup',
            'profile' => 'https://www.facebook.com/%user%',
            'login'   => 'facebook_app_id',
         ],
         'x_twitter' => [
            'name'    => 'X',
            'icon'    => 'fa-brands fa-x-twitter ri-twitter-x-line',
            'color'   => '000',
            'share'   => 'https://x.com/intent/tweet?text=%text%&url=%link%',
            'profile' => 'https://x.com/%user%',
         ],
         'tiktok' => [
            'name'    => 'TikTok',
            'icon'    => 'fa-brands fa-tiktok ri-tiktok-fill',
            'color'   => '000',
            'profile' => 'https://www.tiktok.com/@%user%',
            'login'   => true,
         ],
         'youtube' => [
            'name'         => 'YouTube',
            'icon'         => 'fa-brands fa-youtube ri-youtube-fill',
            'color'        => 'F03',
            'profile'      => '%user%',
            'profile_type' => 'url',
         ],
         'threads' => [
            'name'    => 'Threads',
            'icon'    => 'fa-brands fa-threads ri-threads-fill',
            'color'   => '000',
            'share'   => 'https://www.threads.com/intent/post?text=%text%&url=%link%',
            'profile' => 'https://www.threads.net/@%user%',
         ],
         'lastfm' => [
            'name'    => 'Last.fm',
            'icon'    => 'fa-brands fa-lastfm ri-infinity-line',
            'color'   => 'd1170e',
            'profile' => 'https://www.last.fm/user/%user%',
         ],
         'deezer' => [
            'name'    => 'Deezer',
            'icon'    => 'fa-brands fa-deezer ri-heart-pulse-fill',
            'color'   => '1ed860',
            'profile' => 'https://www.deezer.com/profile/@%user%',
         ],
         'pinterest' => [
            'name'    => 'Pinterest',
            'icon'    => 'fa-brands fa-pinterest ri-pinterest-fill',
            'color'   => 'e70025',
            'share'   => 'http://pinterest.com/pin/create/button/?url=%link%&description=%text%',
            'profile' => 'https://www.pinterest.com/%user%',
         ],
         'snapchat' => [
            'name'    => 'Snapchat',
            'icon'    => 'fa-brands fa-snapchat ri-snapchat-line',
            'color'   => 'fffd01',
            'profile' => 'https://www.snapchat.com/add/%user%',
         ],
         'apple' => [
            'name'  => 'Apple',
            'icon'  => 'fa-brands fa-apple ri-apple-fill',
            'color' => '000',
            'login' => 'apple_client_id',
         ],
         'microsoft' => [
            'name'  => 'Microsoft',
            'icon'  => 'fa-brands fa-microsoft ri-microsoft-fill',
            'color' => '00A4EF',
            'login' => true,
         ],
         'bluesky' => [
            'name'    => 'Bluesky',
            'icon'    => 'fa-brands fa-bluesky ri-bluesky-fill',
            'color'   => '1085fd',
            'share'   => 'https://bsky.app/intent/compose?text=%text%%20%link%',
            'profile' => 'https://bsky.app/profile/%user%',
         ],
         'whatsapp' => [
            'name'         => 'WhatsApp',
            'icon'         => 'fa-brands fa-whatsapp ri-whatsapp-line',
            'color'        => '25d366',
            'share'        => 'https://wa.me/?text=%text%%20%link%',
            'profile'      => 'https://wa.me/%user%',
            'profile_type' => 'number',
         ],
         'linkedin' => [
            'name'    => 'LinkedIn',
            'icon'    => 'fa-brands fa-linkedin ri-linkedin-box-fill',
            'color'   => '0a66c2',
            'share'   => 'https://www.linkedin.com/sharing/share-offsite/?url=%link%',
            'profile' => 'https://www.linkedin.com/in/%user%',
            'login'   => true,
            // https://www.linkedin.com/company/${id}
         ],
         'flickr' => [
            'name'    => 'Flickr',
            'icon'    => 'fa-brands fa-flickr ri-flickr-fill',
            'color'   => 'ff0084',
            'profile' => 'https://www.flickr.com/photos/%user%',
         ],
         'tumblr' => [
            'name'    => 'Tumblr',
            'icon'    => 'fa-brands fa-tumblr ri-tumblr-fill',
            'color'   => '001935',
            'share'   => 'https://tumblr.com/widgets/share/tool?canonicalUrl=%link%',
            'profile' => 'https://%user%.tumblr.com',
         ],
         'mastodon' => [
            'name'         => 'Mastodon',
            'icon'         => 'fa-brands fa-mastodon ri-mastodon-fill',
            'color'        => '6364ff',
            'share'        => 'https://mastodon.social/share?text=%text%%20%link%',
            'profile'      => '%user%',
            'profile_type' => 'url',
         ],
         'github' => [
            'name'    => 'GitHub',
            'icon'    => 'fa-brands fa-github ri-github-fill',
            'color'   => '000',
            'profile' => 'https://github.com/%user%',
            'login'   => true,
         ],
         'twitch' => [
            'name'    => 'Twitch',
            'icon'    => 'fa-brands fa-twitch ri-twitch-fill',
            'color'   => '9146ff',
            'profile' => 'https://www.twitch.tv/%user%',
            'login'   => true,
         ],
         'vimeo' => [
            'name'    => 'Vimeo',
            'icon'    => 'fa-brands fa-vimeo-v ri-vimeo-fill',
            'color'   => '44d5ff',
            'profile' => 'https://vimeo.com/%user%',
         ],
         'skoob' => [
            'name'    => 'Skoob',
            'icon'    => 'fa-solid fa-book-open ri-book-open-fill',
            'color'   => '3282bc',
            'profile' => 'https://www.skoob.com.br/usuario/%user%',
         ],
         'goodreads' => [
            'name'    => 'Goodreads',
            'icon'    => 'fa-solid fa-g ri-book-2-fill',
            'color'   => '1e1915',
            'profile' => 'https://www.goodreads.com/user/show/%user%',
         ],
         'patreon' => [
            'name'    => 'Patreon',
            'icon'    => 'fa-brands fa-patreon ri-patreon-fill',
            'color'   => '000',
            'profile' => 'https://www.patreon.com/%user%',
         ],
         'reddit' => [
            'name'    => 'Reddit',
            'icon'    => 'fa-brands fa-reddit ri-reddit-fill',
            'color'   => 'ff4500',
            'share'   => 'https://www.reddit.com/submit?url=%link%&title=%text%&type=LINK',
            'profile' => 'https://www.reddit.com/user/%user%',
         ],
         'dribbble' => [
            'name'    => 'Dribbble',
            'icon'    => 'fa-brands fa-dribbble ri-dribbble-line',
            'color'   => 'ec5e95',
            'profile' => 'https://dribbble.com/%user%',
         ],
         'telegram' => [
            'name'  => 'Telegram',
            'icon'  => 'fa-brands fa-telegram ri-telegram-fill',
            'color' => '0088cc',
            'share' => 'https://telegram.me/share/url?url=%link%',
         ],
         'email' => [
            'name'  => 'Email',
            'icon'  => 'fa-solid fa-envelope ri-mail-fill',
            'color' => '999',
            'share' => 'mailto:?subject=%text%&body=%link%',
            'login' => true,
         ],
         'behance' => [
            'name'    => 'Behance',
            'icon'    => 'fa-brands fa-behance ri-behance-fill',
            'color'   => '007EFF',
            'profile' => 'https://www.behance.net/%user%',
         ],
         'medium' => [
            'name'    => 'Medium',
            'icon'    => 'fa-brands fa-medium ri-medium-fill',
            'color'   => '000',
            'profile' => 'https://medium.com/@%user%',
         ],
         'gitlab' => [
            'name'    => 'GitLab',
            'icon'    => 'fa-brands fa-gitlab ri-gitlab-fill',
            'color'   => 'fc6d25',
            'profile' => 'https://gitlab.com/%user%',
         ],
      ];

      if (is_array($type_or_keys)) {
         return array_filter($socials, fn($_social, $key) => in_array($key, $type_or_keys), ARRAY_FILTER_USE_BOTH);
      }

      return array_filter($socials, fn($social) => !empty($social[$type_or_keys]));
   }
}
