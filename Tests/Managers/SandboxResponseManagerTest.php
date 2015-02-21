<?php

use danrevah\SandboxBundle\Managers\SandboxResponseManager;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use ShortifyPunit\ShortifyPunit;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use danrevah\SandboxBundle\Annotation\ApiSandboxResponse;
use danrevah\SandboxBundle\Annotation\ApiSandboxMultiResponse;

// Fake appKernel for testing
class AppKernel {
    public function locateResource($a) {}
}

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

class SandboxResponseManagerTest extends WebTestCase
{
    private static $XML_PATH = 'Resources/responses/token.xml';
    private static $JSON_PATH = 'Resources/responses/token.json';

    public function testSandboxResponseNotForced()
    {
        $sandboxResponseManager = $this->createManager(false);

        $request = new ParameterBag();
        $query = new ParameterBag();
        $rawRequest = new ArrayCollection();

        $object = new testObject();
        $method = 'notAnnotatedFunction';

        $response = $sandboxResponseManager->getResponseController($object, $method, $request, $query, $rawRequest);
        $this->assertFalse($response);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testConfigurationFallbackException()
    {
        $request = new ParameterBag(['some_parameter' => 1, 'some_parameter2'=>2]);
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

        $responseObj->responseFallback = [
            'responseCode' => 404,
        ];

        ShortifyPunit::when($annotationsReader)->
            getMethodAnnotation(anInstanceOf('ReflectionMethod'), 'danrevah\SandboxBundle\Annotation\ApiSandboxResponse')->
            returns(false);

        ShortifyPunit::when($annotationsReader)->
            getMethodAnnotation(anInstanceOf('ReflectionMethod'), 'danrevah\SandboxBundle\Annotation\ApiSandboxMultiResponse')->
            returns($responseObj);

        $sandboxResponseManager = $this->createManager(true, $annotationsReader);
        list($callable, $content, $type, $statusCode) = $sandboxResponseManager->getResponseController($object, $method, $request, $query, $rawRequest);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testUnknownResponseType()
    {
        $request = new ParameterBag(['some_parameter' => 1, 'some_parameter2'=>2]);
        $query = new ParameterBag();
        $rawRequest = new ArrayCollection();

        $object = new testObject();
        $method = 'annotatedResponseFunction';

        $annotationsReader = ShortifyPunit::mock('Doctrine\Common\Annotations\AnnotationReader');

        $responseObj = new \StdClass();
        $responseObj->parameters = [['name'=>'some_parameter', 'required'=>true]];
        $responseObj->type = 'some_unknown_response_type';
        $responseObj->responseCode = 200;

        $responseObj->multiResponse = [
            [
                'resource' => '@SandboxBundle/Resources/responses/token.json',
                'caseParams' => ['some_parameter'=>3, 'some_parameter2'=>4]
            ]
        ];

        $responseObj->responseFallback = [
            'resource' => '@SandboxBundle/Resources/responses/token.json',
            'responseCode' => 404,
        ];

        ShortifyPunit::when($annotationsReader)->
            getMethodAnnotation(anInstanceOf('ReflectionMethod'), 'danrevah\SandboxBundle\Annotation\ApiSandboxResponse')->
            returns(false);

        ShortifyPunit::when($annotationsReader)->
            getMethodAnnotation(anInstanceOf('ReflectionMethod'), 'danrevah\SandboxBundle\Annotation\ApiSandboxMultiResponse')->
            returns($responseObj);

        $sandboxResponseManager = $this->createManager(true, $annotationsReader);
        list($callable, $content, $type, $statusCode) = $sandboxResponseManager->getResponseController($object, $method, $request, $query, $rawRequest);
    }


    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMissingParameters()
    {
        $request = new ParameterBag();
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

        $responseObj->responseFallback = [
            'responseCode' => 404,
        ];

        ShortifyPunit::when($annotationsReader)->
            getMethodAnnotation(anInstanceOf('ReflectionMethod'), 'danrevah\SandboxBundle\Annotation\ApiSandboxResponse')->
            returns(false);

        ShortifyPunit::when($annotationsReader)->
            getMethodAnnotation(anInstanceOf('ReflectionMethod'), 'danrevah\SandboxBundle\Annotation\ApiSandboxMultiResponse')->
            returns($responseObj);

        $sandboxResponseManager = $this->createManager(true, $annotationsReader);
        list($callable, $content, $type, $statusCode) = $sandboxResponseManager->getResponseController($object, $method, $request, $query, $rawRequest);
    }


    /**
     * @expectedException \RuntimeException
     */
    public function testConfigurationResourceFallbackException()
    {
        $request = new ParameterBag(['some_parameter' => 1, 'some_parameter2'=>2]);
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

    }

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
        $jsonFile = json_decode(file_get_contents(self::$JSON_PATH), 1);
        $this->assertEquals($jsonFile, $content);
        $this->assertEquals($type, 'json');
        $this->assertEquals($statusCode, 200);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $callable());
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

        $jsonFile = json_decode(file_get_contents(self::$JSON_PATH), 1);
        $xmlFile = file_get_contents(self::$XML_PATH);

        $this->assertEquals($jsonFile, $content);
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
            'type' => 'json',
            'responseCode' => 404,
            'resource' => '@SandboxBundle/Resources/responses/token.json'
        ];

        ShortifyPunit::when($annotationsReader)->
            getMethodAnnotation(anInstanceOf('ReflectionMethod'), 'danrevah\SandboxBundle\Annotation\ApiSandboxMultiResponse')->
            returns($responseObj);

        $sandboxResponseManager = $this->createManager(true, $annotationsReader);

        $request = new ParameterBag(['some_parameter' => 1, 'some_parameter2'=>2]);
        list($callable, $content, $type, $statusCode) = $sandboxResponseManager->getResponseController($object, $method, $request, $query, $rawRequest);

        $this->assertEquals($type, 'xml');
        $this->assertEquals($statusCode, 200);
        $this->assertEquals($xmlFile, $content);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $callable());

        $request = new ParameterBag(['some_parameter' => 3, 'some_parameter2'=>4]);
        list($callable, $content, $type, $statusCode) = $sandboxResponseManager->getResponseController($object, $method, $request, $query, $rawRequest);

        $this->assertEquals($type, 'json');
        $this->assertEquals($statusCode, 200);
        $this->assertEquals($jsonFile, $content);

        // [4] Testing with fallback
        $request = new ParameterBag(['some_parameter' => 5, 'some_parameter2'=>6]);
        list($callable, $content, $type, $statusCode) = $sandboxResponseManager->getResponseController($object, $method, $request, $query, $rawRequest);

        $this->assertEquals($type, 'json');
        $this->assertEquals($statusCode, 404);
        $this->assertEquals($jsonFile, $content);

    }

    /**
     * @expectedException \RuntimeException
     */
    public function testSandboxMultiResponseException()
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
        $kernel = ShortifyPunit::mock('AppKernel');
        $mockedContainer = ShortifyPunit::mock('Symfony\Component\DependencyInjection\Container');
        ShortifyPunit::when($mockedContainer)->getParameter('sandbox.response.force')->returns($force);

        ShortifyPunit::when($kernel)->locateResource('@SandboxBundle/Resources/responses/token.xml')->returns(self::$XML_PATH);
        ShortifyPunit::when($kernel)->locateResource('@SandboxBundle/Resources/responses/token.json')->returns(self::$JSON_PATH);
        // Create manager
        return new SandboxResponseManager($kernel, $mockedContainer, $annotationsReader);
    }
}