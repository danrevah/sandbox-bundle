<?php

$includeIfExists = function($file)
{
    return file_exists($file) ? include $file : false;
};

if (( ! $loader = includeIfExists(__DIR__.'/../vendor/autoload.php')) && ( ! $loader = includeIfExists(__DIR__.'/../../../../../autoload.php'))) {
    die('You must set up the project dependencies, run the following commands:'.PHP_EOL.
        'curl -s http://getcomposer.org/installer | php'.PHP_EOL.
        'php composer.phar install'.PHP_EOL);
}

if (class_exists('Doctrine\Common\Annotations\AnnotationRegistry')) {
    \Doctrine\Common\Annotations\AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
}

// force loading the ApiDoc annotation since the composer target-dir autoloader does not run through $loader::loadClass
class_exists('danrevah\SandboxBundle\Managers\SandboxResponseManager');
