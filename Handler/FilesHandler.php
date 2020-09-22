<?php
/*
 * This file is part of the Koded package.
 *
 * (c) Mihail Binev <mihail@kodeart.com>
 *
 * Please view the LICENSE distributed with this source code
 * for the full copyright and license information.
 */
namespace Koded\Session\Handler;

use Koded\Session\SessionConfiguration;
use SessionHandler;

final class FilesHandler extends SessionHandler
{
    public function __construct(SessionConfiguration $settings)
    {
        ini_set('session.save_handler', 'files');
        ini_set('session.save_path', $settings->get('save_path', session_save_path()));
        ini_set('session.serialize_handler', $settings->get('serialize_handler', 'php'));
    }
}
