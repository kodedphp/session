<?php
/*
 * This file is part of the Koded package.
 *
 * (c) Mihail Binev <mihail@kodeart.com>
 *
 * Please view the LICENSE distributed with this source code
 * for the full copyright and license information.
 */
namespace Koded\Session;

use Koded\Stdlib\{Config, Configuration, Immutable};

class SessionConfiguration extends Config
{
    /**
     * @var array
     */
//    private $cookieParams = [];

    public function __construct(Configuration $settings)
    {
//        $this->cookieParams = session_get_cookie_params();

        $this
            ->set('name', 'session')
            ->import($settings->get('session', []))
            ->import([
                'use_strict_mode'  => '1', // enable to prevent session fixation
                'use_only_cookies' => '1', // disable session identifiers in the URLs
                'use_trans_sid'    => '0', // disable to prevent session fixation and hijacking
                'cache_limiter'    => '',  // disable response headers
                'referer_check'    => '',  // disable it, not a safe implementation (with substr() check)
            ]);

//        if ($this->get('expire_at_browser_close')) {
//            ini_set('session.cookie_lifetime', 0);
            $this->set('cookie_lifetime', 0);
//        }

//        if (null === $this->get('cookie_lifetime')) {
//            $this->set('cookie_lifetime', (int)ini_get('session.gc_maxlifetime'));
//        }

        foreach ($this as $name => $value) {
            @ini_set('session.' . $name, $value);
        }
    }

    public function handler(): string
    {
        return $this->get('save_handler', 'files');
    }

    /**
     * Session directives for session_start() function.
     *
     * @return array
     */
    public function sessionParameters(): array
    {
//        $cookie = [];
////        foreach (session_get_cookie_params() as $k => $v) {
////            $cookie['cookie_' . $k] = $v;
////        }
//
//        return $cookie + [
//            //'cache_expire'           => ini_get('session.cache_expire'),
//
//            'name'                   => ini_get('session.name'),
//            'gc_maxlifetime'         => ini_get('session.gc_maxlifetime'),
//            'referer_check'          => ini_get('session.referer_check'),
//            'serialize_handler'      => ini_get('session.serialize_handler'),
//            'sid_bits_per_character' => ini_get('session.sid_bits_per_character'),
//            'sid_length'             => ini_get('session.sid_length'),
//            'use_cookies'            => ini_get('session.use_cookies'),
//            'use_only_cookies'       => ini_get('session.use_only_cookies'),
//            'use_strict_mode'        => ini_get('session.use_strict_mode'),
//            'use_trans_sid'          => ini_get('session.use_trans_sid'),
//            'cache_limiter'          => ini_get('session.cache_limiter'),
//
////            'cookie_lifetime'          => ini_get('session.cookie_lifetime'),
//        ];

        return (new Immutable($this->filter(ini_get_all('session', false), 'session.', false)))
            ->extract([
                'cache_expire',
                'cache_limiter',
                'gc_maxlifetime',
                'name',
                'referer_check',
                'serialize_handler',
                'sid_bits_per_character',
                'sid_length',
                'use_cookies',
                'use_only_cookies',
                'use_strict_mode',
                'use_trans_sid',
            ]);
    }

    public function cookieParameters(): array
    {
        /*
        $lifetime = $this->get('cookie_lifetime');

        if (!$lifetime) {
            $lifetime = session_get_cookie_params()['lifetime'];
        }

        if (!$lifetime) {
            $lifetime = ini_get('session.gc_maxlifetime');
        }

        error_log('@@@@@@@@@@ ' . $lifetime);
*/
        return //$this->cookieParams +
            [
            'lifetime' => (int)$this->get('cookie_lifetime', ini_get('session.gc_maxlifetime')),
//            'lifetime' => $lifetime,
//            'lifetime' => 0,
            'path'     => $this->get('cookie_path', '/'),
            'domain'   => $this->get('cookie_domain', ''),
            'secure'   => $this->get('cookie_secure', false),
            'httponly' => $this->get('cookie_httponly', false),
            'samesite' => $this->get('cookie_samesite', ''),
        ];
    }
}
