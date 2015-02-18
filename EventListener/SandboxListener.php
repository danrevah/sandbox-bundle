<?php
namespace danrevah\SandboxResponseBundle\EventListener;

use danrevah\SandboxResponseBundle\Annotation\ApiSandboxResponse;
use danrevah\SandboxResponseBundle\Annotation\ApiSandboxMultiResponse;
use danrevah\SandboxResponseBundle\Enum\ApiSandboxResponseTypeEnum;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\Exception\RuntimeException;

class SandboxListener
{

    /**
     * @var \Symfony\Component\HttpKernel\KernelInterface
     */
    private $kernel;
    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    private $request;

    /**
     * @param KernelInterface $kernel
     * @param RequestStack $requestStack
     */
    public function __construct(KernelInterface $kernel, RequestStack $requestStack)
    {
        $this->kernel = $kernel;
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * Overriding response in Sandbox mode
     *
     * @param FilterControllerEvent $event
     * @throws \Exception
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        $reader = new AnnotationReader();
        $reflectionMethod = new ReflectionMethod($controller[0], $controller[1]);


        // Step [1] - Single Response Annotation
        /** @var ApiSandboxResponse $apiResponseAnnotation */
        $apiResponseAnnotation = $reader->getMethodAnnotation(
            $reflectionMethod,
            'danrevah\SandboxResponseBundle\Annotation\ApiSandboxResponse'
        );

        /** @var ApiSandboxMultiResponse $apiResponseMultiAnnotation */
        $apiResponseMultiAnnotation = $reader->getMethodAnnotation(
            $reflectionMethod,
            'danrevah\SandboxResponseBundle\Annotation\ApiSandboxMultiResponse'
        );

        if(( ! $apiResponseAnnotation && ! $apiResponseMultiAnnotation) ||
             ! $this->request->query->has('sandboxMode')
        ) {
            // Disabled exception, continue to real controller
            //throw new \Exception(sprintf('Entity class %s does not have required annotation ApiSandboxResponse', get_class($controller[0])));
            return;
        }

        $parameters = $apiResponseAnnotation ? $apiResponseAnnotation->parameters : $apiResponseMultiAnnotation->parameters;

        // Validating with Annotation syntax
        $streamParams = $this->getStreamParams();
        $this->validateParamsArray($parameters, $streamParams);

        // Get response
        list($responsePath, $type) = $this->getResource($apiResponseAnnotation, $apiResponseMultiAnnotation, $streamParams);

        if (is_null($responsePath)) {
            // If didn't find route path, fall to parent
            return;
        }

        $path = $this->kernel->locateResource($responsePath);
        $content = file_get_contents($path);

        $statusCode = $apiResponseAnnotation->responseCode;

        // Override controller with fake response
        switch (strtolower($type))
        {
            // JSON
            case ApiSandboxResponseTypeEnum::JSON_RESPONSE:
                $content = json_decode($content, 1);

                $event->setController(function() use ($content, $statusCode) {
                    return new JsonResponse($content, $statusCode);
                });
                break;

            // XML
            case ApiSandboxResponseTypeEnum::XML_RESPONSE:
                $event->setController(function() use ($content, $statusCode) {
                    $response = new Response($content, $statusCode);
                    $response->headers->set('Content-Type', 'text/xml');
                    return $response;
                });
                break;

            // Unknown
            default:
                throw new RuntimeException('Unknown type of SandboxApiResponse');
        }
    }

    /**
     * @param ApiSandboxResponse $apiResponseAnnotation
     * @param ApiSandboxMultiResponse $apiResponseMultiAnnotation
     * @param $streamParams
     * @throws \Symfony\Component\Serializer\Exception\RuntimeException
     * @return array
     */
    private function getResource(
        ApiSandboxResponse $apiResponseAnnotation,
        ApiSandboxMultiResponse $apiResponseMultiAnnotation,
        $streamParams
    ) {
        if ($apiResponseAnnotation) {
            return [$apiResponseAnnotation->resource, $apiResponseAnnotation->type];
        }

        // parent type
        $type = $apiResponseMultiAnnotation->type;
        $resourcePath = null;
        foreach ($apiResponseMultiAnnotation->multiResponse as $resource) {
            if ( ! isset($resource['caseParams'])) {
                throw new RunTimeException('Each multi response must have caseParams element');
            }
            $validateCaseParams = 0;

            // Validate Params with GET, POST, and RAW
            foreach ($resource->caseParams as $paramName => $paramValue) {
                if ( ($this->request->query->has($paramName) &&
                        $this->request->query->get($paramName) == $paramValue)
                    ||
                    ($this->request->request->has($paramName) &&
                        $this->request->request->get($paramName) == $paramValue)
                    ||
                    ($streamParams->containsKey($paramName) &&
                        $streamParams->get($paramName) == $paramValue)
                ) {
                    $validateCaseParams++;
                }
            }

            // Found a match route params
            if (count($resource->caseParams) == $validateCaseParams) {
                // Override parent type if has child type
                if (isset($resource->type)) {
                    $type = $resource->type;
                }

                $resourcePath = $resource->resource;
            }
        }

        return [$resourcePath, $type];
    }

    /**
     * @param $apiDocParams
     * @param $streamParams
     * @throws \InvalidArgumentException
     */
    private function validateParamsArray($apiDocParams, $streamParams)
    {
        // search for missing required parameters and throw exception if there's anything missing
        foreach ($apiDocParams as $param => $options) {
            // Validating if required parameters are missing
            if (array_key_exists('required', $options) && $options['required'] &&
                ( ! $this->request->request->has($options['name']) && ! $this->request->query->has($options['name']) &&
                    ! $streamParams->containsKey($options['name']) )
            ) {
                throw new InvalidArgumentException('Missing parameters');
            }
        }
    }

    /**
     * @return ArrayCollection
     */
    private function getStreamParams()
    {
        // get parameters from stream
        $request = $this->request->getContent();
        $requestArray = json_decode($request, true);
        $streamParams = is_null($requestArray) ? new ArrayCollection() : new ArrayCollection($requestArray);
        return $streamParams;
    }
}