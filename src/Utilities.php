<?php

namespace Reactor\DockerProject;

use Symfony\Component\Yaml\Yaml;

class Utilities {
    public static function exec($cmd, $path = '') {
        $rez = 0;
        echo "Running: $cmd\n";
        $path = rtrim($path, '/').'/';
        $_save_path = false;
        if ($path !== '') {
            $_save_path = getcwd();
            @mkdir($path, 0775, true);
            chdir($path);
        }
        passthru($cmd, $rez);
        if ($_save_path !== false) {
            chdir($_save_path);
        }
        if ($rez != 0) {
            throw new \Exception("Error: executing '$cmd'", 1);
        }
    }

    public static function buildCmdArgs($options, $available) {
        $rez = array();
        foreach ($options as $key => $value) {
            if (isset($available[$key]) && $value !== null) {
                $rez[] = $available[$key] . escapeshellarg($value);
            }
        }
        return implode(' ', $rez);
    }

    public static function loadYMLFile($file) {
        if (!is_file($file)) {
            throw new \Exception("File not found '$file'");
        }
        return Utilities::parseYAML(file_get_contents($file), true);
    }

    public static function normalizeDir($path, $pwd) {
        return self::normalizePath($path, $pwd).'/';
    }

    public static function normalizePath($path, $pwd) {
        $path = rtrim($path, '/');
        $pwd = rtrim($pwd, '/').'/';
        if ($path[0] !== '/') {
            $path = $pwd.$path;
        }
        $real = realpath($path);
        if ($real !== false) {
            return $real;
        }
        return $path;
    }

    public static function mergeRecursive($data1, $data2) {
        if (!(is_array($data1) && is_array($data2))) {
            return $data2;
        }
        foreach ($data2 as $key => $value) {
            if (isset($data1[$key])) {
                if (is_integer($key)) {
                    $data1[] = $value;
                } else {
                    $data1[$key] = self::mergeRecursive($data1[$key], $value);
                }
            } else {
                $data1[$key] = $value;
            }
        }
        return $data1;
    }

    public static function strToClassName($str) {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $str)));
    }

    public static function parseYAML($input, $exceptionOnInvalidType = false, $objectSupport = false, $objectForMap = false) {
        return Yaml::parse($input, $exceptionOnInvalidType, $objectSupport, $objectForMap);
    }

    public static function dumpYAML($array, $inline = 2, $indent = 4, $exceptionOnInvalidType = false, $objectSupport = false) {
        return Yaml::dump($array, $inline, $indent, $exceptionOnInvalidType, $objectSupport);
    }

}
