<?php

$defaults = array(
    'cli' => 'src/cli.php',
    'web' => 'src/web.php',
    'bin' => 'application.phar',
);
$temp_file = 'application.phar';

$options = getopt('', array('cli::', 'web::', 'bin::'));
$options = array_merge($defaults, $options);

@unlink($options['bin']);
@unlink($temp_file);

$phar = new Phar($temp_file);
$phar->buildFromDirectory(__DIR__, '/^((?!\.git).)*$/');
$defaultStub = $phar->createDefaultStub($options['cli'], $options['web']);
$defaultStub = "#!/usr/bin/env php\n".$defaultStub;
$phar->setStub($defaultStub);
unset($phar);
chmod($temp_file, 0755);
rename($temp_file, $options['bin']);
