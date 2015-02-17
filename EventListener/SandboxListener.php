<?php
namespace danrevah\SandboxResponseBundle\EventListener;

use danrevah\SandboxResponseBundle\Annotation\ApiSandboxResponse;
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

        /** @var ApiSandboxResponse $apiMetaAnnotation */
        $apiMetaAnnotation = $reader->getMethodAnnotation($reflectionMethod, 'danrevah\SandboxResponseBundle\Annotation\ApiSandboxResponse');

        if( ! $apiMetaAnnotation || ! $this->request->query->has('sandboxMode')) {
            // Disabled exception, continue to real controller
            //throw new \Exception(sprintf('Entity class %s does not have required annotation ApiSandboxResponse', get_class($controller[0])));
            return;
        }

        // Validating with Annotation syntax
        $streamParams = $this->getStreamParams();
        $this->validateParamsArray($apiMetaAnnotation->parameters, $streamParams);

        $responsePath = $apiMetaAnnotation->resource;
        $path = $this->kernel->locateResource($responsePath);
        $content = file_get_contents($path);

        $statusCode = $apiMetaAnnotation->responseCode;

        // Override controller with fake response
        switch (strtolower($apiMetaAnnotation->type))
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