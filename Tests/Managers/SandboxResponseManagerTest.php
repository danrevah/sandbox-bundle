<?php

namespace danrevah\SandboxBundle\Tests\Managers;

use danrevah\SandboxBundle\Managers\SandboxResponseManager;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use KernelAwareTest;
use ReflectionMethod;
use ShortifyPunit\ShortifyPunit;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use danrevah\SandboxBundle\Annotation\ApiSandboxResponse;
use danrevah\SandboxBundle\Annotation\ApiSandboxMultiResponse;

class testObject
{

    public function notAnnotatedFunction() {
        return 'not-annotated-function';
    }

    /**
     * @ApiSandboxResponse(
     *      responseCode=200,
     *      type="json",
     *      parameters={
     *          {"name"="some_parameter", "required"=true}
     *      },
     *      resource="@SandboxBundle/Resources/responses/token.json"
     * )
     */
    public function annotatedResponseFunction() {
        return 'annotated-response-function';
    }

    /**
     * @ApiSandboxMultiResponse(
     *      responseCode=200,
     *      type="json",
     *      parameters={
     *          {"name"="some_parameter", "required"=true}
     *      },
     *      responseFallback={
     *          "type"="xml",
     *          "responseCode"=500,
     *          "resource"="@SandboxBundle/Resources/responses/error.xml"
     *      },
     *      multiResponse={
     *          {
     *              "type"="xml",
     *              "resource"="@SandboxBundle/Resources/responses/token.xml",
     *              "caseParams": {"some_parameter"="1", "some_parameter2"="2"}
     *          },
     *          {
     *              "resource"="@SandboxBundle/Resources/responses/token.json",
     *              "caseParams": {"some_parameter"="3", "some_parameter2"="4"}
     *          }
     *      }
     * )
     */
    public function annotatedMultiResponseFunction() {
        return 'annotated-multi-response-function';
    }
}

class SandboxResponseManagerTest extends KernelAwareTest 
{
    /**
     * @expectedException \Exception
     */
    public function testSandboxResponseForceException()
    {
        $sandboxResponseManager = $this->createManager(true);

        $request = new ParameterBag();
        $query = new ParameterBag();
        $rawRequest = new ArrayCollection();

        $object = new testObject();
        $method = 'notAnnotatedFunction';

        $sandboxResponseManager->getResponseController($object, $method, $request, $query, $rawRequest);
    }

    /**
     * Testing single response annotation logic `ApiSandboxResponse`
     */
    public function testSandboxResponse()
    {
        $request = new ParameterBag(['some_parameter' => 1]);
        $query = new ParameterBag();
        $rawRequest = new ArrayCollection();

        $object = new testObject();
        $method = 'annotatedResponseFunction';

        $annotationsReader = ShortifyPunit::mock('Doctrine\Common\Annotations\AnnotationReader');

        $responseObj = new \StdClass();
        $responseObj->parameters = [['name'=>'some_parameter', 'required'=>true]];
        $responseObj->resource = '@SandboxBundle/Resources/responses/token.json';
        $responseObj->type = 'json';
        $responseObj->responseCode = 200;

        ShortifyPunit::when($annotationsReader)->
            getMethodAnnotation(anInstanceOf('ReflectionMethod'), 'danrevah\SandboxBundle\Annotation\ApiSandboxResponse')->
            returns($responseObj);

        ShortifyPunit::when($annotationsReader)->
            getMethodAnnotation(anInstanceOf('ReflectionMethod'), 'danrevah\SandboxBundle\Annotation\ApiSandboxMultiResponse')->
            returns(false);

        $sandboxResponseManager = $this->createManager(true, $annotationsReader);
        list($callable, $content, $type, $statusCode) = $sandboxResponseManager->getResponseController($object, $method, $request, $query, $rawRequest);

        $path = self::$kernel->locateResource('@SandboxBundle/Resources/responses/token.json');
        $contentReal = json_decode(file_get_contents($path), 1);

        $this->assertEquals($contentReal, $content);
        $this->assertEquals($type, 'json');
        $this->assertEquals($statusCode, 200);
    }

    /**
     * Testing single multi response annotation logic `ApiSandboxMultiResponse`
     */
    public function testSandboxMultiResponse()
    {
        // [1] Check basic multiResponse with one response by params
        $request = new ParameterBag(['some_parameter' => 3, 'some_parameter2'=>4]);
        $query = new ParameterBag();
        $rawRequest = new ArrayCollection();

        $object = new testObject();
        $method = 'annotatedResponseFunction';

        $annotationsReader = ShortifyPunit::mock('Doctrine\Common\Annotations\AnnotationReader');

        $responseObj = new \StdClass();
        $responseObj->parameters = [['name'=>'some_parameter', 'required'=>true]];
        $responseObj->type = 'json';
        $responseObj->responseCode = 200;

        $responseObj->multiResponse = [
            [
                'resource' => '@SandboxBundle/Resources/responses/token.json',
                'caseParams' => ['some_parameter'=>3, 'some_parameter2'=>4]
            ]
        ];

        ShortifyPunit::when($annotationsReader)->
            getMethodAnnotation(anInstanceOf('ReflectionMethod'), 'danrevah\SandboxBundle\Annotation\ApiSandboxResponse')->
            returns(false);

        ShortifyPunit::when($annotationsReader)->
            getMethodAnnotation(anInstanceOf('ReflectionMethod'), 'danrevah\SandboxBundle\Annotation\ApiSandboxMultiResponse')->
            returns($responseObj);

        $sandboxResponseManager = $this->createManager(true, $annotationsReader);
        list($callable, $content, $type, $statusCode) = $sandboxResponseManager->getResponseController($object, $method, $request, $query, $rawRequest);

        $path = self::$kernel->locateResource('@SandboxBundle/Resources/responses/token.json');
        $contentReal = json_decode(file_get_contents($path), 1);

        $this->assertEquals($contentReal, $content);
        $this->assertEquals($type, 'json');
        $this->assertEquals($statusCode, 200);


        // [2] Checking if child inside multiResponse override the parent configuration
        $responseObj->multiResponse = [
            [
                'type' => 'xml',
                'responseCode' => 500,
                'resource' => '@SandboxBundle/Resources/responses/token.json',
                'caseParams' => ['some_parameter'=>3, 'some_parameter2'=>4]
            ]
        ];

        ShortifyPunit::when($annotationsReader)->
            getMethodAnnotation(anInstanceOf('ReflectionMethod'), 'danrevah\SandboxBundle\Annotation\ApiSandboxMultiResponse')->
            returns($responseObj);

        $sandboxResponseManager = $this->createManager(true, $annotationsReader);
        list($callable, $content, $type, $statusCode) = $sandboxResponseManager->getResponseController($object, $method, $request, $query, $rawRequest);

        $this->assertEquals($type, 'xml');
        $this->assertEquals($statusCode, 500);

        // [3] Check multiple configuration
        $responseObj->multiResponse = [
            [
                'type' => 'xml',
                'responseCode' => 200,
                'resource' => '@SandboxBundle/Resources/responses/token.xml',
                'caseParams' => ['some_parameter'=>1, 'some_parameter2'=>2]
            ],
            [
                'type' => 'json',
                'responseCode' => 200,
                'resource' => '@SandboxBundle/Resources/responses/token.json',
                'caseParams' => ['some_parameter'=>3, 'some_parameter2'=>4]
            ]
        ];

        $responseObj->responseFallback = [
            'responseCode' => 404,
            'resource' => '@SandboxBundle/Resources/responses/token.json'
        ];

        ShortifyPunit::when($annotationsReader)->
            getMethodAnnotation(anInstanceOf('ReflectionMethod'), 'danrevah\SandboxBundle\Annotation\ApiSandboxMultiResponse')->
            returns($responseObj);

        $sandboxResponseManager = $this->createManager(true, $annotationsReader);

        $path = self::$kernel->locateResource('@SandboxBundle/Resources/responses/token.xml');
        $contentRealXml = file_get_contents($path);

        $request = new ParameterBag(['some_parameter' => 1, 'some_parameter2'=>2]);
        list($callable, $content, $type, $statusCode) = $sandboxResponseManager->getResponseController($object, $method, $request, $query, $rawRequest);

        $this->assertEquals($type, 'xml');
        $this->assertEquals($statusCode, 200);
        $this->assertEquals($contentRealXml, $content);

        $request = new ParameterBag(['some_parameter' => 3, 'some_parameter2'=>4]);
        list($callable, $content, $type, $statusCode) = $sandboxResponseManager->getResponseController($object, $method, $request, $query, $rawRequest);

        $this->assertEquals($type, 'json');
        $this->assertEquals($statusCode, 200);
        $this->assertEquals($contentReal, $content);

        // [4] Testing with fallback
        $request = new ParameterBag(['some_parameter' => 5, 'some_parameter2'=>6]);
        list($callable, $content, $type, $statusCode) = $sandboxResponseManager->getResponseController($object, $method, $request, $query, $rawRequest);

        $this->assertEquals($type, 'json');
        $this->assertEquals($statusCode, 404);
        $this->assertEquals($contentReal, $content);

    }


    /**
     * Creating sandboxResponseManager with dependencies
     *
     * @param $force
     * @param bool $annotationsReader
     * @return SandboxResponseManager
     */
    private function createManager($force, $annotationsReader = false)
    {
        if ($annotationsReader === false) {
            $annotationsReader = new AnnotationReader();
        }

        // Mocking the sandbox response manager dependencies
        $mockedContainer = ShortifyPunit::mock('Symfony\Component\DependencyInjection\Container');
        ShortifyPunit::when($mockedContainer)->getParameter('sandbox.response.force')->returns($force);

        // Create manager
        return new SandboxResponseManager(static::$kernel, $mockedContainer, $annotationsReader);
    }
}