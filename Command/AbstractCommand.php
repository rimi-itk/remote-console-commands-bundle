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
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        parent::configure();
        $this->addArgument('hostname', InputArgument::OPTIONAL, 'The hostname or stage');
    }

    /**
     * Get project dir.
     *
     * @return string
     */
    protected function getProjectDir()
    {
        return \dirname($this->getContainer()->getParameter('kernel.root_dir'));
    }

    /**
     * validate that hosts file exists and contins valid YAML.
     *
     * @return string The hosts file path
     */
    protected function validateHosts()
    {
        $hostsPath = $this->getProjectDir().'/hosts.yaml';

        if (!file_exists($hostsPath)) {
            throw new RuntimeException('Cannot find file `hosts.yaml` in project root');
        }

        try {
            $yaml = file_get_contents($hostsPath);
            $hosts = Yaml::parse($yaml);

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
    protected function getDefaultHostname()
    {
        $hosts = $this->validateHosts();

        return array_keys($hosts)[0];
    }

    /**
     * validate hostname.
     *
     * @param string $hostname The hostname or stage
     * @param mixed  $hostname
     *
     * @return string The hostname of first host with given name or stage
     */
    protected function validateHostname($hostname)
    {
        $hosts = $this->validateHosts();

        foreach ($hosts as $name => $host) {
            if (\in_array($hostname, [$name, $host['stage']], true)) {
                return $host + ['name' => $name];
            }
        }

        throw new RuntimeException('Invalid host: '.$hostname);
    }
}
