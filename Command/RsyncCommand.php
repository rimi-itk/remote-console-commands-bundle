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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class RsyncCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('rsync')
            ->addArgument('path', InputArgument::REQUIRED, 'The path to rsync')
            ->addOption('exclude-paths', null, InputOption::VALUE_REQUIRED, 'List of paths to exclude, seperated by : (Unix-based systems) or ; (Windows).')
            ->addOption('include-paths', null, InputOption::VALUE_REQUIRED, 'List of paths to include, seperated by : (Unix-based systems) or ; (Windows).')
            ->addOption('mode', null, InputOption::VALUE_REQUIRED, 'The unary flags to pass to rsync; --mode=rultz implies rsync -rultz.', 'vakz');
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
        $path = $input->getArgument('path');
        $sourcePath = $host['release_path'].'/'.trim($path, '/');
        $targetPath = $this->getProjectDir().'/'.\dirname($path).'/';

        $arguments = '-'.$input->getOption('mode');
        if ($excludePaths = $input->getOption('exclude-paths')) {
            // @TODO
        }
        if ($includePaths = $input->getOption('include-paths')) {
            // @TODO
        }

        $target = '';
        if (isset($host['user'])) {
            $target .= $host['user'].'@';
        }
        $target .= $host['name'];

        $cmd = implode(' ', array_filter(['rsync', $arguments, $target.':'.$sourcePath, $targetPath]));

        $process = new Process($cmd);
        $process->mustRun(function ($type, $data) use ($output) {
            if ('out' === $type) {
                $output->write($data);
            }
        }, []);
    }
}
