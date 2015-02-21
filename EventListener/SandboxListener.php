<?php
namespace danrevah\SandboxBundle\EventListener;

use danrevah\SandboxBundle\Annotation\ApiSandboxResponse;
use danrevah\SandboxBundle\Annotation\ApiSandboxMultiResponse;
use danrevah\SandboxBundle\Enum\ApiSandboxResponseTypeEnum;
use danrevah\SandboxBundle\Managers\SandboxResponseManager;
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
     * @var \Symfony\Component\HttpFoundation\Request
     */
    private $request;
    /**
     * @var \danrevah\SandboxBundle\Managers\SandboxResponseManager
     */
    private $sandboxResponseManager;

    /**
     * @param RequestStack $requestStack
     * @param SandboxResponseManager $sandboxResponseManager
     */
    public function __construct(
        RequestStack $requestStack,
        SandboxResponseManager $sandboxResponseManager
    ) {
        $this->request = $requestStack->getCurrentRequest();
        $this->sandboxResponseManager = $sandboxResponseManager;
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

        list($responseController,,,) = $this->sandboxResponseManager->getResponseController(
            $controller[0],
            $controller[1],
            $this->request->query,
            $this->request->request,
            $this->getStreamParams()
        );

        // Fall back to real controller if none has been found and force mode is false
        if ($responseController === false) {
            return;
        }

        $event->setController($responseController);
    }


    /**
     * @return ArrayCollection
     */
    private function getStreamParams()
    {
        // get parameters from stream
        $request = $this->request->getContent();
        $requestArray = json_decode($request, true);
        $streamParams = $requestArray === null ? new ArrayCollection() : new ArrayCollection($requestArray);
        return $streamParams;
    }

}