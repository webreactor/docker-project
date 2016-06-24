<?php
namespace Reactor\DockerProject;

use Reactor\CliArguments\ArgumentsParser;

include __dir__.'/../vendor/autoload.php';

$app = new Application();
$cli = new CliController($app);
$cli->handle(new ArgumentsParser($GLOBALS['argv']));
