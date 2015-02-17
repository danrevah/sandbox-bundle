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
     *
     * @ApiSandboxMultiResponse(
     *      parameters={
     *          {"name"="some_parameter", "required"=true}
     *      },
     *      multiResponse={
     *          {
     *              "type"="xml",
     *              "resource"="@SandboxResponseBundle/Resources/responses/token.xml",
     *              "caseParams": {"some_parameter"="1", "some_parameter2"="2"}
     *          },
     *          {
     *              "type"="json",
     *              "resource"="@SandboxResponseBundle/Resources/responses/token.json",
     *              "caseParams": {"some_parameter"="3", "some_parameter2"="4"}
     *          }
     *      }
     * )
     */
    public function indexAction(Request $request)
    {
        return new JsonResponse(['Test Response']);
    }

}
