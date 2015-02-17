<?php
namespace danrevah\SandboxResponseBundle\EventListener;

use danrevah\SandboxResponseBundle\Annotation\ApiSandboxResponse;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;

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

        if( ! $apiMetaAnnotation) {
            // Disabled exception, continue to real controller
            //throw new \Exception(sprintf('Entity class %s does not have required annotation ApiSandboxResponse', get_class($controller[0])));
        } else {
            // Validating with Annotation syntax
            $streamParams = $this->getStreamParams();
            $this->validateParamsArray($apiMetaAnnotation->parameters, $streamParams);

            // Validating with NelomiApi - Not in use
            //$this->validateRequiredParameters($reader, $reflectionMethod);

            $responsePath = $apiMetaAnnotation->resource;
            $path = $this->kernel->locateResource($responsePath);
            $content = json_decode(file_get_contents($path), 1);

            // Override controller with fake response
            $event->setController(function() use ($content) {
                return new JsonResponse($content);
            });
        }
    }

    /**
     * Using the NelmioApiDocBundle Annotations getting required values from the Parameters field
     *
     * @param $reader
     * @param $reflectionClass
     * @throws \InvalidArgumentException
     */
//    private function validateRequiredParameters(AnnotationReader $reader, ReflectionMethod $reflectionClass)
//    {
//        // get api doc annotation
//        $apiDocAnn = $reader->getMethodAnnotation($reflectionClass, 'Nelmio\ApiDocBundle\Annotation\ApiDoc');
//
//        // if has api doc annotation and has parameters validate if recived all required parameters
//        if ($apiDocAnn instanceof ApiDoc) {
//            $apiDocParams = $apiDocAnn->getParameters();
//            $streamParams = $this->getStreamParams();
//
//            // search for missing required parameters and throw exception if there's anything missing
//            foreach ($apiDocParams as $param => $options) {
//                if (array_key_exists('required', $options) && $options['required'] &&
//                    ( ! $this->request->request->has($param) && ! $this->request->query->has($param) &&
//                        ! $streamParams->containsKey($param) )
//                ) {
//                    throw new InvalidArgumentException('Missing parameters');
//                }
//            }
//        }
//    }

    /**
     * @param $apiDocParams
     * @param $streamParams
     * @throws \InvalidArgumentException
     */
    private function validateParamsArray($apiDocParams, $streamParams)
    {
        // search for missing required parameters and throw exception if there's anything missing
        foreach ($apiDocParams as $param => $options) {
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