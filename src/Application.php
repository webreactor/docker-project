<?php

namespace DockerProject;

use Symfony\Component\Yaml\Yaml;

class Application {
    public $version;
    public $apps_dir;

    public function __construct() {
        $this->version = '0.0.1';
        $this->tpl = new SimpleTemplating();
    }

    public function initAppsDir($path) {
        $this->apps_dir = $path;
        if (!is_dir($path)) {
            mkdir($path, 0766, true);
        }
    }

    public function runUpdate($config_file) {
        $config = $this->loadComposeConfig($config_file);
        foreach ($config['services'] as $service_name => $service) {
            if (isset($service['labels']['project.git'])) {
                $git_link = $service['labels']['project.git'];
                $branch = 'master';
                if (isset($service['labels']['project.git.branch'])) {
                    $branch = $service['labels']['project.git.branch'];
                }
                $this->gitUpdate($service, $git_link, $branch);
            }
        }
    }

    public function runShell($command, $extra, $config_file) {
        $config = $this->loadComposeConfig($config_file);
        if (empty($extra)) {
            throw new \Exception("--extra is a necessary parameter", 1);
        }
        foreach ($config['services'] as $service_name => $service) {
            if (!empty($service['_app_dir'])) {
                $this->execForService($extra, $service);    
            }
        }
    }

    public function runProject($command, $extra, $config_file) {
        $config = $this->loadComposeConfig($config_file);
        $key = strtolower("project.{$command}");
        foreach ($config['services'] as $service_name => $service) {
            if (isset($service['labels'][$key])) {
                $command = trim($service['labels'][$key].' '.$extra);
                $this->execForService($command, $service);
            }
        }
    }

    public function execForService($command, $service) {
        echo "--------------------------------------------\n";
        $path = $service['_app_dir'];
        echo "Service: {$service['service_name']} at $path\n";
        $this->tpl->data = array(
            '__service__' => $service['service_name'],
            '__image__' => $service['image'],
        );
        $command = $this->tpl->process(trim($command));
        Utilities::exec($command, $path);
    }

    public function gitUpdate($service, $link, $branch) {
        if (!is_dir($service['_app_dir'].'.git')) {
            $this->execForService("git clone -b ".$branch." ".$link." .", $service);
        } else {
            $this->execForService("git pull", $service);
        }
    }

    public function loadComposeConfig($file) {
        $config = Utilities::loadYMLFile($file);
        $config['_source'] = $file;
        $pwd = dirname($file).'/';
        $config = $this->resolveExtends($config, $pwd);
        $config = $this->parseAppDirs($config);
        return $config;
    }

    public function parseAppDirs($config) {
        foreach ($config['services'] as $service_name => $service) {
            if (isset($service['labels']['project.git'])) {
                $git_link = $service['labels']['project.git'];
                preg_match('/\b([\w\-]+\/[\w\-]+)\.git/', $git_link, $match);
                $dir = Utilities::normalizeDir($match[1], $this->apps_dir);
            } elseif (isset($service['build'])) {
                $dir = Utilities::normalizeDir($service['build'], dirname($service['_source']));
            } else {
                $dir = null;
            }
            $config['services'][$service_name]['_app_dir'] = $dir;
        }
        return $config;
    }

    public function resolveExtends($config, $pwd) {
        foreach ($config['services'] as $service_name => $service) {
            $config['services'][$service_name]['_source'] = $config['_source'];
            $config['services'][$service_name]['service_name'] = $service_name;
            if (isset($service['extends'])) {
                $extends = $service['extends'];
                $extends_file = Utilities::normalizePath($extends['file'], $pwd);
                if (is_file($extends_file)) {
                    $extention_data = $this->loadComposeConfig($extends_file);
                    if (isset($extention_data['services'][$extends['service']])) {
                        $extention_service = $extention_data['services'][$extends['service']];
                        $extention_service['_source'] = $extention_data['_source'];
                        $config['services'][$service_name] = Utilities::mergeRecursive($config['services'][$service_name], $extention_service);
                    }
                } else {
                    echo "Warning: [$service_name] $extends_file does not exist\n";
                }
            }
        }
        return $config;
    }

}
