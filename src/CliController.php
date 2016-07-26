<?php

namespace Reactor\DockerProject;

use Symfony\Component\Yaml\Yaml;
use Reactor\CliArguments\ArgumentDefinition;

class CliController {
 
    private $app;
    private $pwd;

    public function __construct($app) {
        $this->app = $app;
        $this->pwd = getcwd().'/';
    }

    public function handle($arguments_container) {
        try {
            $this->handleLogic($arguments_container);
        } catch (\Exception $e) {
            echo "Error: ".$e->getMessage()."\n\n";
            exit(1);
        }
    }

    public function handleLogic($arguments_container) {
        $arguments = $this->parseArguments($arguments_container);
        $this->app->initAppsDir($arguments['apps-dir']);
        switch ($arguments['command']) {
            case 'help':
                $this->printHelp($arguments_container);
            break;
            case 'update':
                $this->app->loadComposeFile($arguments['composer-file']);
                $this->app->runUpdate($arguments['extra']);
            break;
            case 'shell':
                $this->app->loadComposeFile($arguments['composer-file']);
                $this->app->runShell(
                    $arguments['command'],
                    $arguments['extra']
                );
            break;
            case 'status':
                $this->app->loadComposeFile($arguments['composer-file']);
                $this->printStatus($arguments);
            break;
            default:
                $this->app->loadComposeFile($arguments['composer-file']);
                $this->app->runProjectCommand(
                    $arguments['command'],
                    $arguments['extra']
                );
        };
    }

    public function parseArguments($arguments_container) {
        $this->defineArguments($arguments_container);
        $arguments = array();
        $arguments['composer-file'] = Utilities::normalizePath($arguments_container->get('file'), $this->pwd);
        $arguments['apps-dir'] = Utilities::normalizeDir($arguments_container->get('apps'), $this->pwd);

        $_cli_words = $arguments_container->get('_words_');
        if (!isset($_cli_words[1])) {
            $_cli_words[1] = 'help';
        }
        $arguments['command'] = $_cli_words[1];
        $arguments['extra'] = $arguments_container->get('extra');
        return $arguments;
    }

    public function defineArguments($arguments) {
        $arguments->addDefinition(new ArgumentDefinition('file', 'f', 'docker-compose.yml', false, false, 'Alternative config file'));
        $arguments->addDefinition(new ArgumentDefinition('apps', 'a', 'apps', false, false, 'Applications sources folder'));
        $arguments->addDefinition(new ArgumentDefinition('extra', 'x', '', false, false, 'Extra parameters passed to command'));
        $arguments->addDefinition(new ArgumentDefinition('_words_', '', '', false, true, 'command'));
        $arguments->parse();
        return $arguments;
    }

    public function printHelp($arguments) {
        echo "docker project management tool {$this->app->version}\n";
        echo "\nUsage:\n";
        echo "  docker-project <command> <arguments>\n";
        echo "\nCommands:\n";
        echo "  update - clones or pulls application source\n";
        echo "  shell - uses extra parameter to run shell command for each app\n";
        echo "  status - prints current services with repos and their commands\n";
        echo "  help - prints help\n";
        echo "  your_command - defined as label for the service (example: labels: project.test: make test)\n";
        echo "\nArguments:\n";
        echo "  Full name    | Short | Default            | Note\n";
        echo "-------------------------------------------------------\n";

        foreach ($arguments->definitions as $key => $definition) {
            if ($key != '_words_') {
                echo sprintf("  --%-12s -%-6s %-20s %s\n",
                    $definition->name,
                    $definition->short,
                    $definition->default,
                    $definition->description
                );
            }
        }
        echo "\n";
    }

    public function printStatus($arguments) {
        echo "Compose file: {$arguments['composer-file']}\n";
        echo "Apps folder:  {$arguments['apps-dir']}\n";
        if (!empty($arguments['extra'])) {
            echo "Extra parameters: {$arguments['extra']}\n";
        }
        echo "\nRegistered services\n";
        foreach ($this->app->apps as $service_name => $app_dir) {
            echo "$service_name:\n";
            echo "  Application folder: {$app_dir}\n";
            $service = $this->app->compose->getService($service_name);
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
