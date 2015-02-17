<?php

namespace danrevah\SandboxResponseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use danrevah\SandboxResponseBundle\Annotation\ApiSandboxResponse;

class DefaultController extends Controller
{
    /**
     * GET /testing_fake_sandbox_response
     *
     * @param Request $request
     * @return array
     * @Rest\View()
     * @ApiSandboxResponse(
     *      resource="@SandboxResponseBundle/Resources/responses/token.xml",
     *      type="XML",
     *      parameters={
     *          {"name"="some_parameter", "required"=true}
     *      },
     * )
     */
    public function indexAction(Request $request)
    {
        return new JsonResponse(['Test Response']);
    }

}
