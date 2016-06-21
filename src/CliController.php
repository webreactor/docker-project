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
        $config = $this->app->loadComposeConfig($arguments['composer-file']);
        try {
            switch ($arguments['command']) {
                case 'update':
                    $this->app->RunUpdateCommand($config, $arguments['apps-dir']);
                break;
                case 'shell':
                    $this->app->RunShellCommand($arguments['command'], $arguments['extra'], $config);
                break;
                case 'help':
                    $this->help($arguments_container);
                break;
                default:
                    $this->app->RunProjectCommand($arguments['command'], $arguments['extra'], $config);
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
        $arguments->addDefinition('apps', 'a', 'apps', false, 'apps folder realtive to te compose file');
        $arguments->addDefinition('extra', 'x', '', false, 'Extra parameters passed to command');
        $arguments->addDefinition('_words_', '', null, true, 'command');
        return $arguments;
    }

    public function help($arguments) {
        echo "docker project management tool {$this->app->version}\n";
        echo "\nUsage:\n";
        echo "  docker-project <command> <arguments>\n";
        echo "\nCommands:\n";
        echo "  update - clones or pulls application source\n";
        echo "  shell - uses extra parameter to run shell command for each app\n";
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

}
