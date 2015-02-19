<?php

namespace danrevah\SandboxBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use danrevah\SandboxBundle\Annotation\ApiSandboxResponse;
use danrevah\SandboxBundle\Annotation\ApiSandboxMultiResponse;

class DefaultController extends Controller
{
    /**
     * GET /testing_fake_sandbox_response
     *
     * @param Request $request
     * @return array
     * @Rest\View()
     * @ApiSandboxMultiResponse(
     *      responseCode=200,
     *      type="json",
     *      parameters={
     *          {"name"="some_parameter", "required"=true}
     *      },
     *      responseFallback={
     *          "type"="xml",
     *          "responseCode"=500,
     *          "resource"="@SandboxBundle/Resources/responses/error.xml"
     *      },
     *      multiResponse={
     *          {
     *              "type"="xml",
     *              "resource"="@SandboxBundle/Resources/responses/token.xml",
     *              "caseParams": {"some_parameter"="1", "some_parameter2"="2"}
     *          },
     *          {
     *              "resource"="@SandboxBundle/Resources/responses/token.json",
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
