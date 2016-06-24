<?php

namespace Reactor\DockerProject;

class SimpleTemplating {

    public $data = array();

    public function __construct($data = array()) {
        $this->data = $data;
    }

    public function process($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->process($value);
            }
        } elseif (is_string($data)) {
            $data = $this->processStr($data);
        }
        return $data;
    }

    public function processStr($str) {
        foreach ($this->data as $key => $value) {
            $str = str_replace($key, $value, $str);
        }
        return $str;
    }

    public function set($key, $value) {
        $this->data[$key] = $value;
        return $this;
    }

    public function remove($key) {
        unset($this->data[$key]);
    }
}

