<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Test case class helpful with Entity tests requiring the database interaction.
 * For regular entity tests it's better to extend standard \PHPUnit_Framework_TestCase instead.
 */
abstract class KernelAwareTest extends WebTestCase
{
    protected static $kernel;

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected static $entityManager;

    /**
     * @var Symfony\Component\DependencyInjection\Container
     */
    protected static $container;

    /**
     * @return null
     */
    public static function setUpBeforeClass()
    {
        self::$kernel = static::createKernel(['environment' => 'sandbox']);
        self::$kernel->boot();
        self::$entityManager = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    /**
     * @return null
     */
    public static function tearDownAfterClass()
    {
        self::$entityManager->close();
    }

}