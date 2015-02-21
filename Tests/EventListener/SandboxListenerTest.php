<?php
namespace SandboxBundle\Tests\EventListener;

use danrevah\SandboxBundle\EventListener\SandboxListener;
use ShortifyPunit\ShortifyPunit;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SandboxListenerTest extends WebTestCase
{
    public function testOnKernelController()
    {
        $request = ShortifyPunit::mock('Symfony\Component\HttpFoundation\Request');
        $requestStack = ShortifyPunit::mock('Symfony\Component\HttpFoundation\RequestStack');
        $sandboxResponseManager = ShortifyPunit::mock('danrevah\SandboxBundle\Managers\SandboxResponseManager');
        $event = ShortifyPunit::mock('Symfony\Component\HttpKernel\Event\FilterControllerEvent');
        $parameterBag = ShortifyPunit::mock('Symfony\Component\HttpFoundation\ParameterBag');

        ShortifyPunit::when($request)->getContent()->returns('');
        $request->query = $parameterBag;
        $request->request = $parameterBag;
        ShortifyPunit::when($requestStack)->getCurrentRequest()->returns($request);
        ShortifyPunit::when($event)->getController()->returns([0, 1]);

        $sandboxListener = new SandboxListener($requestStack, $sandboxResponseManager);
        $sandboxListener->onKernelController($event);

        ShortifyPunit::verify($event)->setController(anything())->atLeastOnce();

        $response = [false, 0, 0, 0];
        ShortifyPunit::when($sandboxResponseManager)->getResponseController(anything(), anything(), anything(), anything(), anything())->returns($response);

        $event2 = ShortifyPunit::mock('Symfony\Component\HttpKernel\Event\FilterControllerEvent');
        ShortifyPunit::when($event2)->getController(anything())->returns([0, 1]);
        $sandboxListener = new SandboxListener($requestStack, $sandboxResponseManager);
        $sandboxListener->onKernelController($event2);

        ShortifyPunit::verify($event2)->setController(anything())->neverCalled();
    }
}