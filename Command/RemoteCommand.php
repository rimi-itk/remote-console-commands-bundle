<?php

/*
 * This file is part of Remote console commands bundle.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace ItkDev\RemoteConsoleCommandsBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Process\Process;

class RemoteCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('remote')
          ->addArgument('remote-command', InputArgument::REQUIRED, 'The command to run');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->validateHosts();
        $hostname = $input->getArgument('hostname') ?: $this->getDefaultHostname();
        $host = $this->validateHostname($hostname);
        $command = $input->getArgument('remote-command');

        $result = $this->runOnHost($host, $command);
    }

    private function runOnHost(array $host, $command)
    {
        $sshArguments = '-o ConnectTimeout=10 -o BatchMode=yes -o StrictHostKeyChecking=no -A -tt';
        $target = '';
        if (isset($host['user'])) {
            $target .= $host['user'].'@';
        }
        $target .= $host['name'];

        $console = version_compare(Kernel::VERSION, '3.0.0') ? 'app/console' : 'bin/console';
        if (isset($hosts['paths'][$console])) {
            $console = $host['paths'][$console];
        }
        $console = $host['release_path'].'/'.$console;

        $env = '';
        if (isset($host['env'])) {
            $env = implode(' ', array_map(
                function ($name, $value) {
                    return $name.'='.$value;
                },
                array_keys($host['env']),
                $host['env']
            ));
        }

        $cmd = implode(' ', array_filter(['ssh', $sshArguments, $target, $env, $console, $command]));

//        echo PHP_EOL, $cmd, PHP_EOL;

        $process = new Process($cmd);
        $process->mustRun();

        return $process->getOutput();
    }
}
