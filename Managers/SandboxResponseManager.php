<?php
namespace danrevah\SandboxBundle\Managers;

use danrevah\SandboxBundle\Annotation\ApiSandboxMultiResponse;
use danrevah\SandboxBundle\Enum\ApiSandboxResponseTypeEnum;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class SandboxResponseManager {

    /**
     * @var \Symfony\Component\HttpKernel\KernelInterface
     */
    private $kernel;
    /**
     * @var \Doctrine\Common\Annotations\AnnotationReader
     */
    private $annotationsReader;
    private $forceMode;

    /**
     * @param KernelInterface $kernel
     * @param $forceMode
     * @param \Doctrine\Common\Annotations\AnnotationReader $annotationsReader
     */
    public function __construct($kernel, $forceMode, AnnotationReader $annotationsReader)
    {
        $this->kernel = $kernel;
        $this->annotationsReader = $annotationsReader;
        $this->forceMode = $forceMode;
    }

    /**
     * Getting a response controller by Annotations
     *
     * @param $object
     * @param String $method
     * @param ParameterBag $request
     * @param ParameterBag $query
     * @param ArrayCollection $rawRequest
     * @return callable
     * @throws \RuntimeException
     * @throws \Exception
     */
    public function getResponseController(
        $object,
        $method,
        ParameterBag $request,
        ParameterBag $query,
        ArrayCollection $rawRequest
    ) {
        $reader = $this->annotationsReader;
        $reflectionMethod = new ReflectionMethod($object, $method);

        // Step [1] - Single Response Annotation
        /** @var \StdClass $apiResponseAnnotation */
        $apiResponseAnnotation = $reader->getMethodAnnotation(
            $reflectionMethod,
            'danrevah\SandboxBundle\Annotation\ApiSandboxResponse'
        );

        /** @var \StdClass $apiResponseMultiAnnotation */
        $apiResponseMultiAnnotation = $reader->getMethodAnnotation(
            $reflectionMethod,
            'danrevah\SandboxBundle\Annotation\ApiSandboxMultiResponse'
        );

        if ( ! $apiResponseAnnotation && ! $apiResponseMultiAnnotation) {
            // Disabled exception, continue to real controller
            $forceMode = $this->forceMode;

            if ($forceMode) {
                throw new \Exception(sprintf(
                    'Entity class %s does not have required annotation ApiSandboxResponse or ApiResponseMultiAnnotation',
                    get_class($object)
                ));
            } else {
                // Fall back to the REAL Controller
                return false;
            }
        }

        $parameters = $apiResponseAnnotation ? $apiResponseAnnotation->parameters : $apiResponseMultiAnnotation->parameters;

        // Validating with Annotation syntax
        $this->validateParamsArray($parameters, $rawRequest, $request, $query);

        // Single response annotation is checked first
        if ($apiResponseAnnotation) {
            $responsePath = $apiResponseAnnotation->resource;
            $type = $apiResponseAnnotation->type;
            $statusCode = $apiResponseAnnotation->responseCode;
        } else {
            // Get response
            list($responsePath, $type, $statusCode) = $this->getResource(
                $apiResponseMultiAnnotation,
                $rawRequest,
                $request,
                $query
            );
        }

        if ($responsePath === null && $apiResponseMultiAnnotation) {
            list($type, $statusCode, $responsePath) = $this->extractRealParams($responsePath, $apiResponseMultiAnnotation, $type, $statusCode);
        }

        list($controller, $content) = $this->getControllerResponseByResource($responsePath, $type, $statusCode);

        return [$controller, $content, $type, $statusCode];
    }

    /**
     * @param \StdClass $apiResponseMultiAnnotation
     * @param $streamParams
     * @param $request
     * @param $query
     * @throws \RuntimeException
     * @return array
     */
    private function getResource(
        \StdClass $apiResponseMultiAnnotation,
        ArrayCollection $streamParams,
        ParameterBag $request,
        ParameterBag $query
    ) {
        // parent type, and responseCode
        $type = $apiResponseMultiAnnotation->type;
        $responseCode = $apiResponseMultiAnnotation->responseCode;
        $resourcePath = null;

        foreach ($apiResponseMultiAnnotation->multiResponse as $resource) {

            if ( ! isset($resource['caseParams']) || ! isset($resource['resource'])) {
                throw new RunTimeException('Each multi response must have caseParams and resource property');
            }

            $validateCaseParams = $this->countCaseParamsFromQuery($streamParams, $request, $query, $resource);

            // Found a match route params
            if (count($resource['caseParams']) == $validateCaseParams) {
                list($type, $responseCode, $resourcePath) = $this->extractResource($resource, $type, $responseCode);
                // If found route break loop
                break;
            }
        }

        return [$resourcePath, $type, $responseCode];
    }

    /**
     * @param $apiDocParams
     * @param $rawRequest
     * @param $request
     * @param $query
     * @throws \InvalidArgumentException
     */
    private function validateParamsArray(
        $apiDocParams,
        ArrayCollection $rawRequest,
        ParameterBag $request,
        ParameterBag $query
    ) {
        // search for missing required parameters and throw exception if there's anything missing
        foreach ($apiDocParams as $options) {
            // Validating if required parameters are missing
            if (array_key_exists('required', $options) && $options['required'] &&
                ( ! $request->has($options['name']) && ! $query->has($options['name']) &&
                    ! $rawRequest->containsKey($options['name']))
            ) {
                throw new \InvalidArgumentException('Missing parameters');
            }
        }
    }

    /**
     * @param $responsePath
     * @param $type
     * @param $statusCode
     * @return callable
     * @throws \RuntimeException
     */
    private function getControllerResponseByResource($responsePath, $type, $statusCode)
    {
        $path = $this->kernel->locateResource($responsePath);
        $content = file_get_contents($path);

        // Override controller with fake response
        switch (strtolower($type)) {
            // JSON
            case ApiSandboxResponseTypeEnum::JSON_RESPONSE:
                $content = json_decode($content, 1);

                $controller = function() use ($content, $statusCode) {
                    return new JsonResponse($content, $statusCode);
                };
                break;

            // XML
            case ApiSandboxResponseTypeEnum::XML_RESPONSE:
                $controller = function() use ($content, $statusCode) {
                    $response = new Response($content, $statusCode);
                    $response->headers->set('Content-Type', 'text/xml');
                    return $response;
                };
                break;

            // Unknown
            default:
               throw new RuntimeException('Unknown type of SandboxApiResponse');
        }
        return [$controller, $content];
    }

    /**
     * @param $responsePath
     * @param \StdClass $apiResponseMultiAnnotation
     * @param $type
     * @param $statusCode
     * @throws \RuntimeException
     * @return array
     */
    private function extractRealParams($responsePath, \StdClass $apiResponseMultiAnnotation, $type, $statusCode)
    {
        // If didn't find route path, fall to responseFallback
        if (empty($apiResponseMultiAnnotation->responseFallback) || ! isset($apiResponseMultiAnnotation->responseFallback['resource'])) {
            throw new RuntimeException('Missing `responseFallback` is not set properly in the Sandbox annotation');
        }

        return array(
            isset($apiResponseMultiAnnotation->responseFallback['type']) ? $apiResponseMultiAnnotation->responseFallback['type'] : $type,
            isset($apiResponseMultiAnnotation->responseFallback['responseCode']) ? $apiResponseMultiAnnotation->responseFallback['responseCode'] : $statusCode,
            $apiResponseMultiAnnotation->responseFallback['resource']
        );
    }

    /**
     * @param ArrayCollection $streamParams
     * @param ParameterBag $request
     * @param ParameterBag $query
     * @param $resource
     * @return int
     */
    private function countCaseParamsFromQuery(ArrayCollection $streamParams, ParameterBag $request, ParameterBag $query, $resource)
    {
        $validateCaseParams = 0;

        // Validate Params with GET, POST, and RAW
        foreach ($resource['caseParams'] as $paramName => $paramValue) {
            if ($this->existsInQuery($paramName, $paramValue, $streamParams, $request, $query)) {
                $validateCaseParams++;
            }
        }
        return $validateCaseParams;
    }

    /**
     * @param $paramName
     * @param $paramValue
     * @param $streamParams
     * @param $request
     * @param $query
     * @return bool
     */
    private function existsInQuery($paramName, $paramValue, ArrayCollection $streamParams, ParameterBag $request, ParameterBag $query)
    {
        return ($query->get($paramName) == $paramValue) || $request->get($paramName) == $paramValue || $streamParams->get($paramName) == $paramValue;
    }

    /**
     * @param $resource
     * @param $type
     * @param $responseCode
     * @return array
     */
    private function extractResource($resource, $type, $responseCode)
    {
        // Override parent type if has child type
        if (isset($resource['type'])) {
            $type = $resource['type'];
        }
        if (isset($resource['responseCode'])) {
            $responseCode = $resource['responseCode'];
        }
        $resourcePath = $resource['resource'];
        return array($type, $responseCode, $resourcePath);
    }
}
