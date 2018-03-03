<?php

namespace SandboxBundle\Tests\DependencyInjection;

use danrevah\SandboxBundle\DependencyInjection\SandboxExtension;
use ShortifyPunit\ShortifyPunit;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SandboxExtensionTest extends WebTestCase
{

    // Test for code coverage only testing that there's not Exceptions with the configuration
    public function testLoad()
    {
        $containerBuilder = ShortifyPunit::mock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $sandboxExtension = new SandboxExtension();
        $sandboxExtension->load(array('sandbox' => array('response' => array('force' => true))), $containerBuilder);
    }
}