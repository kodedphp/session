<?php declare(strict_types=1);
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
use function ini_get;
use function ini_get_all;
use function ini_set;

class SessionConfiguration extends Config
{
    private array $cookieParameters = [];
    private array $sessionParameters = [];

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(Configuration $settings)
    {
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

        if ($this->get('expire_at_browser_close')) {
            @ini_set('session.cookie_lifetime', 0);
            $this->set('cookie_lifetime', 0);
        }
        foreach ($this as $name => $value) {
            @ini_set('session.' . $name, $value);
        }
        // session parameters
        $this->sessionParameters = (new Immutable($this->filter(
            ini_get_all('session', false), 
            'session.', 
            false
        )))->extract([
            'cache_expire',
            'cache_limiter',
            'gc_maxlifetime',
            'name',
            'referer_check',
            'save_path',
            'serialize_handler',
            'sid_bits_per_character',
            'sid_length',
            'use_cookies',
            'use_only_cookies',
            'use_strict_mode',
            'use_trans_sid'
        ]);
        // cookie parameters
        $this->cookieParameters = [
            'lifetime' => (int)$this->get('cookie_lifetime', ini_get('session.gc_maxlifetime')),
            'path'     => $this->get('cookie_path', '/'),
            'domain'   => $this->get('cookie_domain', ''),
            'secure'   => $this->get('cookie_secure', false),
            'httponly' => $this->get('cookie_httponly', false),
            'samesite' => $this->get('cookie_samesite', ''),
        ];
    }

    /**
     * Returns the name of the session handler.
     * @return string
     * @link https://php.net/manual/en/function.session-set-save-handler.php
     */
    public function handler(): string
    {
        return $this->get('save_handler', 'files');
    }

    /**
     * Session directives for session_start() function.
     * @return array
     * @link https://php.net/manual/en/function.session-start.php
     */
    public function sessionParameters(): array
    {
        return $this->sessionParameters;
    }

    /**
     * Parameters for session_set_cookie_params() function.
     * @return array
     * @link https://php.net/manual/en/function.session-set-cookie-params.php
     */
    public function cookieParameters(): array
    {
        return $this->cookieParameters;
    }
}
