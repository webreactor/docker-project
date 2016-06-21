<?php
namespace DockerProject;

use \Webreactor\CliArguments\ArgumentsParser;

include __dir__.'/../vendor/autoload.php';

$app = new Application();
$cli = new CliController($app);
$cli->handle(new ArgumentsParser($GLOBALS['argv']));
