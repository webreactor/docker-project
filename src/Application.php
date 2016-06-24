<?php

namespace Reactor\DockerProject;

use Symfony\Component\Yaml\Yaml;

class Application {

    public $version = '0.0.1';
    public $apps_dir;
    public $tpl;
    public $compose;
    public $apps;

    public function __construct() {
        $this->tpl = new SimpleTemplating();
        $this->compose = new DockerComposeConfig();
    }

    public function initAppsDir($path) {
        $this->apps_dir = $path;
        if (!is_dir($path)) {
            mkdir($path, 0766, true);
        }
    }

    public function loadComposeFile($file) {
        $this->compose->openFile($file);
        $this->apps = $this->parseAppDirs($this->compose->getServices());
    }

    public function runUpdate($extra) {
        foreach ($this->apps as $service_name => $app_dir) {
            $service = $this->compose->getService($service_name);
            if (isset($service['labels']['project.git'])) {
                $git_link = $service['labels']['project.git'];
                $branch = 'master';
                if (isset($service['labels']['project.git.branch'])) {
                    $branch = $service['labels']['project.git.branch'];
                }
                $this->gitUpdate($service_name, $git_link, $branch, $extra);
            }
        }
    }

    public function runShell($command, $extra) {
        if (empty($extra)) {
            throw new \Exception("--extra is a necessary parameter");
        }
        foreach ($this->apps as $service_name => $app_dir) {
            $service = $this->compose->getService($service_name);
            $this->execForService($extra, $service_name);
        }
    }

    public function runProjectCommand($command_name, $extra) {
        $key = strtolower("project.{$command_name}");
        $command = false;
        foreach ($this->compose->getServices() as $service_name => $service) {
            $service = $this->compose->getService($service_name);
            if (isset($service['labels'][$key])) {
                $command = trim($service['labels'][$key].' '.$extra);
                $this->execForService($command, $service_name);
            }
        }
        if ($command === false) {
            throw new \Exception(
                "Not defined command '$command_name'\n".
                "Possible fix: Create label project.$command_name with shell command as value."
            );
        }
    }

    private function parseAppDirs($services) {
        $apps = array();
        foreach ($services as $service_name => $service) {
            $dir = null;
            if (isset($service['labels']['project.git'])) {
                $git_link = $service['labels']['project.git'];
                preg_match('/\b([\w\-]+\/[\w\-]+)\.git/', $git_link, $match);
                $dir = Utilities::normalizeDir($match[1], $this->apps_dir);
            } elseif (isset($service['build'])) {
                $dir = Utilities::normalizeDir($service['build'], dirname($service['_source']));
            }
            if (!empty($dir)) {
                $apps[$service_name] = $dir;
            }
        }
        return $apps;
    }

    public function execForService($command, $service_name) {
        $service = $this->compose->getService($service_name);
        echo "--------------------------------------------\n";
        $path = $this->apps[$service_name];
        echo "Service: {$service['service_name']} at $path\n";
        $this->tpl->data = array(
            '__service__' => $service['service_name'],
            '__image__' => isset($service['image'])?$service['image']:'',
        );
        $command = $this->tpl->process(trim($command));
        Utilities::exec($command, $path);
    }

    public function gitUpdate($service_name, $link, $branch, $extra) {
        if (!is_dir($this->apps[$service_name].'.git')) {
            $this->execForService("git clone $extra -b ".$branch." ".$link." .", $service_name);
        } else {
            $this->execForService("git pull $extra", $service_name);
        }
    }

    public function testDependencies() {
        $a = exec('git --version', $out, $code);
        if ($code > 0) {
            throw new \Exception(
                "Missing git client tool.\n".
                "This might help: sudo apt-get install git"
            );
        }
    }

}
