<?php

namespace danrevah\SandboxResponseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use danrevah\SandboxResponseBundle\Annotation\ApiSandboxResponse;

class DefaultController extends Controller
{
    /**
     * GET /testing_fake_sandbox_response
     *
     * @param Request $request
     * @return array
     * @Rest\View()
     * @ApiDoc(
     *     section="Sandbox Example",
     *     description="Example",
     *     parameters={
     *          {"name"="some_parameter", "dataType"="string", "required"=true, "description"="some example required parameter"}
     *     },
     *     statusCode={
     *         200="Returned when successful",
     *         500="API Internal Error",
     *         400="Bad arguments"
     *     }
     * )
     * @ApiSandboxResponse("@SandboxResponseBundle/Resources/responses/token.json")
     */
    public function indexAction(Request $request)
    {
        return new JsonResponse(['Test Response']);
    }

}
