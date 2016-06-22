<?php

namespace DockerProject;

use Symfony\Component\Yaml\Yaml;

class CliController {
 
    private $app;
    private $pwd;

    public function __construct($app) {
        $this->app = $app;
        $this->pwd = getcwd().'/';
    }

    public function handle($arguments_container) {
        $arguments = $this->parseArguments($arguments_container);
        $this->app->initAppsDir($arguments['apps-dir']);
        try {
            switch ($arguments['command']) {
                case 'update':
                    $this->app->RunUpdate($arguments['composer-file'], $arguments['apps-dir']);
                break;
                case 'shell':
                    $this->app->RunShell($arguments['command'], $arguments['extra'], $arguments['composer-file']);
                break;
                case 'status':
                    $this->printStatus($arguments['composer-file'], $arguments);
                break;
                case 'help':
                    $this->printHelp($arguments_container);
                break;
                default:
                    $this->app->RunProject($arguments['command'], $arguments['extra'], $arguments['composer-file']);
            };
        } catch (\Exception $e) {
            echo "Error: ".$e->getMessage()."\n";
            exit(1);
        }
    }

    public function parseArguments($arguments_container) {
        $this->defineArguments($arguments_container);
        $arguments = array();
        $arguments['composer-file'] = Utilities::normalizePath($arguments_container->get('file'), $this->pwd);
        $arguments['apps-dir'] = Utilities::normalizeDir($arguments_container->get('apps'), dirname($arguments['composer-file']));

        $_cli_words = $arguments_container->get('_words_');
        if (!isset($_cli_words[1])) {
            $_cli_words[1] = 'help';
        }
        $arguments['command'] = $_cli_words[1];
        $arguments['extra'] = $arguments_container->get('extra');
        return $arguments;
    }

    public function defineArguments($arguments) {
        $arguments->addDefinition('file', 'f', 'docker-compose.yml', false, 'Alternative config file');
        $arguments->addDefinition('apps', 'a', 'apps', false, 'apps folder realtive to the compose file');
        $arguments->addDefinition('extra', 'x', '', false, 'Extra parameters passed to command');
        $arguments->addDefinition('_words_', '', null, true, 'command');
        return $arguments;
    }

    public function printHelp($arguments) {
        echo "docker project management tool {$this->app->version}\n";
        echo "\nUsage:\n";
        echo "  docker-project <command> <arguments>\n";
        echo "\nCommands:\n";
        echo "  update - clones or pulls application source\n";
        echo "  shell - uses extra parameter to run shell command for each app\n";
        echo "  status - prints current recogniser services with repos and their commands\n";
        echo "  help - prints help\n";
        echo "  your_command - defined as label for the service (example: labels: PROJECT_TEST: make test)\n";
        echo "\nArguments:\n";
        echo "  Full name        | Short | Default          | Note\n";
        echo "-----------------------------------------------------\n";

        foreach ($arguments->definitions as $key => $definition) {
            if ($key != '_words_') {
                echo sprintf("  --%-16s -%-6s %-18s %s\n",
                    $definition['name'],
                    $definition['short_name'],
                    $definition['default'],
                    $definition['description']
                );
            }
        }
        echo "\n";
    }

    public function printStatus($config_file, $arguments) {
        $config = $this->app->loadComposeConfig($config_file);
        echo "Compose file: {$arguments['composer-file']}\n";
        echo "Apps folder:  {$arguments['apps-dir']}\n";
        if (!empty($arguments['extra'])) {
            echo "Extra parameters: {$arguments['extra']}\n";
        }
        echo "\nRegistered services\n";
        foreach ($config['services'] as $service_name => $service) {
            if (isset($service['_app_dir'])) {
                echo "$service_name:\n";
                echo "  Application folder: {$service['_app_dir']}\n";
                foreach ($service['labels'] as $key => $value) {
                    if (preg_match('/project.(.+)/', $key, $match)) {
                        $name = ucfirst($match[1]);
                        echo "  {$name}: $value\n";
                    }
                }
                echo "\n";
            }
        }
    }

}
