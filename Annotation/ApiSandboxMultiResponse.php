<?php

namespace danrevah\SandboxBundle\Annotation;

use danrevah\SandboxBundle\Enum\ApiSandboxResponseTypeEnum;
use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
final class ApiSandboxMultiResponse extends Annotation
{
    // Default response type is JSON
    public $type = ApiSandboxResponseTypeEnum::JSON_RESPONSE;

    // Response code to output, default is 200
    public $responseCode = 200;

    /**
     * Response Fallback
     * @var array
     * @desc If could not find any matching route, it will use this route instead
     *
     * Example:
     *     responseFallback={
     *          "type"="xml",
     *          "responseCode"=500,
     *          "resource"="@SandboxBundle/Resources/responses/token.xml"
     *     },
     */
    public $responseFallback = [];

    /**
     * Multi Response
     * @var array
     *
     * Example:
     *      multiResponse={
     *          {
     *              "responseCode":200,
     *              "type"="xml",
     *              "resource"="@SandboxBundle/Resources/responses/token.xml",
     *              "caseParams": {"some_parameter"="1", "some_parameter2"="2"}
     *          },
     *          {
     *              "responseCode":200,
     *              "type"="json",
     *              "resource"="@SandboxBundle/Resources/responses/token.json",
     *              "caseParams": {"some_parameter"="3", "some_parameter2"="4"}
     *          }
     *      }
     */
    public $multiResponse = [];

    /**
     * Request parameters object
     * @var array
     *
     * Example:
     *      parameters = {
     *          {"name"="param1", "required"=true},
     *          {"name"="param2", "required"=false}
     *      }
     */
    public $parameters = [];
}