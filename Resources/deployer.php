<?php

/*
 * This file is part of Remote console commands bundle.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace Deployer;

use Symfony\Component\Console\Input\InputArgument;

desc('Run a console command on host');
argument('console-command', InputArgument::REQUIRED, 'Console command to run');
task('remote-console', function () {
    $command = input()->getArgument('console-command');
    $console = 'bin/console';
    if (has('paths')) {
        $paths = get('paths');
        if (isset($paths[$console])) {
            $console = $paths[$console];
        }
    }
    $options = ['timeout' => null, 'tty' => true];
    $result = run("{{release_path}}/$console $command", $options);
    write($result);
})->shallow();

inventory('hosts.yaml');
