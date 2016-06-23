<?php

namespace DockerProject;

class DockerComposeConfig {

    private $config = array();

    public function __construct($file = null) {
        if (!empty($file)) {
            $this->loadFile($file);    
        }
    }

    public function getService($service_name) {
        return $this->config['services'][$service_name];
    }
    public function getServices() {
        return $this->config['services'];
    }

    public function openFile($file) {
        $this->config = $this->loadFile($file);
    }

    public function loadFile($file) {
        $config = Utilities::loadYMLFile($file);
        if (!(isset($config['version']) && $config['version'] === '2')) {
            throw new \Exception("Supports only docker-compose.yml version 2", 1);
        }
        $config['_source'] = $file;
        if (!isset($config['services'])) {
            throw new \Exception("No services are defined in $file", 1);
        }
        $pwd = dirname($file).'/';
        $config = $this->normalize($config);
        $config = $this->resolveExtends($config, $pwd);
        return $config;
    }

    private function normalize($config) {
        foreach ($config['services'] as $service_name => $service) {
            $config['services'][$service_name]['service_name'] = $service_name;
        }
        return $config;
    }


    private function resolveExtends($config, $pwd) {
        foreach ($config['services'] as $service_name => $service) {
            $config['services'][$service_name]['_source'] = $config['_source'];
            if (isset($service['extends'])) {
                $extends = $service['extends'];
                $extends_file = Utilities::normalizePath($extends['file'], $pwd);
                if (is_file($extends_file)) {
                    $extention_data = $this->loadFile($extends_file);
                    if (isset($extention_data['services'][$extends['service']])) {
                        $service_extention = $extention_data['services'][$extends['service']];
                        $service_extention['_source'] = $extention_data['_source'];
                        $config['services'][$service_name] = Utilities::mergeRecursive(
                            $config['services'][$service_name],
                            $service_extention
                        );
                    }
                } else {
                    echo "Warning: [$service_name] $extends_file does not exist\n";
                }
            }
        }
        return $config;
    }
}