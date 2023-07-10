<?php

/*
 * This file is part of the Behat MinkExtension.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\MinkExtension\ServiceContainer\Driver;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;

class Selenium4Factory implements DriverFactory
{
    /**
     * {@inheritdoc}
     */
    public function getDriverName()
    {
        return 'selenium4';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsJavascript()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
                ->scalarNode('name')->defaultValue('Behat Test')->end()
                ->scalarNode('browser')->defaultValue('%mink.browser_name%')->end()
                ->append($this->getCapabilitiesNode())
                ->scalarNode('wd_host')->defaultValue('http://localhost:4444/wd/hub')->end()
            ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildDriver(array $config)
    {
        if (!class_exists('Behat\Mink\Driver\Selenium4Driver')) {
            throw new \RuntimeException(sprintf(
                'Install MinkSelenium4Driver in order to use %s driver.',
                $this->getDriverName()
            ));
        }

        dd($config);

        $desiredCapabilities = array(
            'capabilities'  => $config['capabilities'],
            'tags' => array(php_uname('n'), 'PHP '.phpversion())
        );

        if (getenv('TRAVIS_JOB_NUMBER')) {
            $desiredCapabilities['tunnel-identifier'] = getenv('TRAVIS_JOB_NUMBER');
            $desiredCapabilities['build'] = getenv('TRAVIS_BUILD_NUMBER');
            $desiredCapabilities['tags'] = array('Travis-CI', 'PHP '.phpversion());
        }

        if (getenv('JENKINS_HOME')) {
            $desiredCapabilities['tunnel-identifier'] = getenv('JOB_NAME');
            $desiredCapabilities['build'] = getenv('BUILD_NUMBER');
            $desiredCapabilities['tags'] = array('Jenkins', 'PHP '.phpversion(), getenv('BUILD_TAG'));
        }
        dd($config['capabilities']);
        return new Definition('Behat\Mink\Driver\Selenium4Driver', array(
            $config['browser'],
            $desiredCapabilities,
            $config['wd_host'],
        ));
    }

    protected function getCapabilitiesNode()
    {
        $node = new ArrayNodeDefinition('capabilities');

        $node
            ->addDefaultsIfNotSet()
            ->normalizeKeys(false)
            ->children()
                ->arrayNode('firstMatch')
                ->end()
                ->arrayNode('alwaysMatch')
                    ->children()
                        ->scalarNode('browserName')->end()
                        ->scalarNode('pageLoadStrategy')->end()
                        ->arrayNode('goog:chromeOptions')
                            ->children()
                                ->arrayNode('extensions')
                                    ->scalarPrototype()->end()
                                ->end()
                                ->arrayNode('args')
                                    ->scalarPrototype()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }
}
