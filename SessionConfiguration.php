<?php

/*
 * This file is part of the Koded package.
 *
 * (c) Mihail Binev <mihail@kodeart.com>
 *
 * Please view the LICENSE distributed with this source code
 * for the full copyright and license information.
 *
 */

namespace Koded\Session;

use Koded\Stdlib\{Config, Immutable};
use Koded\Stdlib\Interfaces\ConfigurationFactory;


class SessionConfiguration extends Config
{

    public function __construct(ConfigurationFactory $settings)
    {
        $this
            ->import($settings->get('session', []))
            ->import([
                'use_strict_mode' => '1', // enable to prevent session fixation
                'use_trans_sid'   => '0', // disable to prevent session fixation and hijacking
                'cache_limiter'   => '',  // disable response headers
                'referer_check'   => '',  // disable for it has a dangerous implementation with substr() check
            ]);

        if ($this->get('expire_at_browser_close')) {
            ini_set('session.cookie_lifetime', 0);
        }

        foreach ($this as $name => $value) {
            @ini_set('session.' . $name, $value);
        }
    }

    public function handler(): string
    {
        return $this->get('save_handler', 'files');
    }

    public function cookieParameters(): array
    {
//        return [
//            (int)$this->get('cookie_lifetime', 1209600), // default 2 weeks
//            (string)$this->get('cookie_path', '/'),
//            (string)$this->get('cookie_domain'),
//            (bool)$this->get('cookie_secure', false),
//            (bool)$this->get('cookie_httponly', true),
//        ];

        return [
            (int)ini_get('session.cookie_lifetime'),
            (string)ini_get('session.cookie_path'),
            (string)ini_get('session.cookie_domain'),
            (bool)ini_get('session.cookie_secure'),
            (bool)ini_get('session.cookie_httponly'),
        ];
    }

    public function sessionParameters(): array
    {
//        return $this->extract([
//            'cache_expire',
//            'cache_limiter',
//            'gc_maxlifetime',
//            'name',
//            'referer_check',
//            'serialize_handler',
//            'sid_bits_per_character',
//            'sid_length',
//            'use_cookies',
//            'use_only_cookies',
//            'use_strict_mode',
//            'use_trans_sid',
//        ]);

        $ini = new Immutable($this->filter(ini_get_all('session', false), 'session.', false));

        return $ini
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
}
