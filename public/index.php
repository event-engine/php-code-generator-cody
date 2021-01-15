<?php

/**
 * @see       https://github.com/event-engine/php-code-generator-cody for the canonical source repository
 * @copyright https://github.com/event-engine/php-code-generator-cody/blob/master/COPYRIGHT.md
 * @license   https://github.com/event-engine/php-code-generator-cody/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

use EventEngine\InspectioCody\Http\ServerFactory;

\chdir(\dirname(__DIR__));

require 'vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

$server = ServerFactory::createServer($loop, require 'codyconfig.php');
$socket = new React\Socket\Server('0.0.0.0:8080', $loop);
$server->listen($socket);

$socket->on('error', 'printf');

echo 'Listening on ' . \str_replace('tcp:', 'http:', $socket->getAddress()) . PHP_EOL;

$loop->run();
