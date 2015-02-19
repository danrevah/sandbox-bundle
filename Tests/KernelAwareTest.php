<?php

require_once dirname(__DIR__).'/../../../app/AppKernel.php';

/**
 * Test case class helpful with Entity tests requiring the database interaction.
 * For regular entity tests it's better to extend standard \PHPUnit_Framework_TestCase instead.
 *
 * Taken from https://gist.github.com/jakzal/1319290
 */
abstract class KernelAwareTest extends \PHPUnit_Framework_TestCase
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
        self::$kernel = new \AppKernel('sandbox', true);
        self::$kernel->boot();

        self::$container = self::$kernel->getContainer();
        self::$entityManager = self::$container->get('doctrine')->getManager();

        self::generateSchema();
    }

    /**
     * @return null
     */
    public function tearDown()
    {
        self::$kernel->shutdown();
    }

    /**
     * @return null
     */
    protected function generateSchema()
    {
        $metadatas = self::getMetadatas();

        if (!empty($metadatas)) {
            $tool = new \Doctrine\ORM\Tools\SchemaTool(self::$entityManager);
            $tool->dropSchema($metadatas);
            $tool->createSchema($metadatas);
        }
    }

    /**
     * @return array
     */
    protected function getMetadatas()
    {
        return self::$entityManager->getMetadataFactory()->getAllMetadata();
    }
}