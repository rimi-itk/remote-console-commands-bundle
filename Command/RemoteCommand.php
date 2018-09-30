<?php

/*
 * This file is part of Remote console commands bundle.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace ItkDev\RemoteConsoleCommandsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class RemoteCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('remote')
          ->addArgument('remote-command', InputArgument::REQUIRED, 'The command to run')
          ->addArgument('hostname', InputArgument::OPTIONAL, 'The hostname or stage');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->validateHosts();
        $hostname = $input->getArgument('hostname') ?: $this->getDefaultHostname();
        $hostname = $this->validateHostname($hostname);
        $command = $input->getArgument('remote-command');

        $dir = $this->getProjectDir();
        $dep = $dir.'/vendor/bin/dep --file='.__DIR__.'/../Resources/deployer.php';

        $commandLine = implode(' ', [$dep, 'remote-console', $command, $hostname]);
        $process = new Process($commandLine);
        $process->mustRun();
        $result = $process->getOutput();

        if (!empty($result)) {
            $output->write($result);
        }
    }

    /**
     * Get project dir.
     *
     * @return string
     */
    private function getProjectDir()
    {
        return $this->getContainer()->getParameter('kernel.project_dir');
    }

    /**
     * validate that hosts file exists and contins valid YAML.
     *
     * @return string The hosts file path
     */
    private function validateHosts()
    {
        $hostsPath = $this->getProjectDir().'/hosts.yaml';

        if (!file_exists($hostsPath)) {
            throw new RuntimeException('Cannot find file `hosts.yaml` in project root');
        }

        try {
            $hosts = Yaml::parseFile($hostsPath);

            if (\is_array($hosts)) {
                foreach ($hosts as $name => $host) {
                    // Skip hidden hosts (cf. https://deployer.org/docs/hosts#inventory-file)
                    if (preg_match('/^\./', $name)) {
                        continue;
                    }
                    foreach (['stage', 'release_path'] as $required) {
                        if (!isset($host[$required])) {
                            throw new RuntimeException('Missing `'.$required.'` in host '.$name);
                        }
                    }
                }

                return $hosts;
            }
        } catch (ParseException $parseException) {
            throw new RuntimeException($parseException->getMessage());
        }

        throw new RuntimeException('Invalid hosts file');
    }

    /**
     * Get default hostname, i.e. the name of first host in hosts file.
     *
     * @return mixed
     */
    private function getDefaultHostname()
    {
        $hosts = $this->validateHosts();

        return array_keys($hosts)[0];
    }

    /**
     * validate hostname.
     *
     * @param string $hostname The hostname or stage
     * @param mixed  $stage
     *
     * @return string The hostname of first host with given name or stage
     */
    private function validateHostname($stage)
    {
        $hosts = $this->validateHosts();

        foreach ($hosts as $name => $host) {
            if (\in_array($stage, [$name, $host['stage']], true)) {
                return $name;
            }
        }

        throw new RuntimeException('Invalid stage: '.$stage);
    }
}
