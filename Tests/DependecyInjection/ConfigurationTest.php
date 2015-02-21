<?php

use danrevah\SandboxBundle\DependencyInjection\Configuration;
use danrevah\SandboxBundle\EventListener\SandboxListener;
use ShortifyPunit\ShortifyPunit;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConfigurationTest extends WebTestCase
{
    // Test for code coverage, validate if there's no exceptions
    public function testOnKernelController()
    {
        $configuration = new Configuration();
        $tree = $configuration->getConfigTreeBuilder();
    }
}