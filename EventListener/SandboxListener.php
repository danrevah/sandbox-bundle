<?php
namespace danrevah\SandboxResponseBundle\EventListener;

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
        $apiMetaAnnotation = $reader->getMethodAnnotation($reflectionMethod, 'danrevah\\SandboxResponseBundle\\Annotation\\ApiSandboxResponse');

        if( ! $apiMetaAnnotation) {
            // Disabled exception, continue to real controller

            //throw new \Exception(sprintf('Entity class %s does not have required annotation ApiSandboxResponse', get_class($controller[0])));
        } else {
            $this->validateRequiredParameters($reader, $reflectionMethod);

            $responsePath = $apiMetaAnnotation->value;
            $path = $this->kernel->locateResource($responsePath);
            $content = json_decode(file_get_contents($path), 1);

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
    private function validateRequiredParameters(AnnotationReader $reader, ReflectionMethod $reflectionClass)
    {
        // get api doc annotation
        $apiDocAnn = $reader->getMethodAnnotation($reflectionClass, 'Nelmio\\ApiDocBundle\\Annotation\\ApiDoc');

        // if has api doc annotation and has parameters validate if recived all required parameters
        if ($apiDocAnn instanceof ApiDoc) {

            $apiDocParams = $apiDocAnn->getParameters();

            // get parameters from stream
            $request = file_get_contents('php://input');
            $requestArray = json_decode($request, true);
            $streamParams = is_null($requestArray) ? new ArrayCollection() : new ArrayCollection($requestArray);

            // search for missing required parameters and throw exception if there's anything missing
            foreach ($apiDocParams as $param => $options) {
                if ( ! array_key_exists('required', $options)) {
                    continue;
                }

                // only validate required true
                if ( ! $options['required']) {
                    continue;
                }

                if ( ! $this->request->request->has($param) &&
                    ! $this->request->query->has($param) &&
                    ! $streamParams->containsKey($param)
                ) {
                    throw new InvalidArgumentException('Missing parameters');
                }
            }
        }
    }
}