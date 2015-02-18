<?php

namespace danrevah\SandboxResponseBundle\Annotation;

use danrevah\SandboxResponseBundle\Enum\ApiSandboxResponseTypeEnum;
use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
final class ApiSandboxMultiResponse extends Annotation
{
    // Default response type is JSON
    public $type = ApiSandboxResponseTypeEnum::JSON_RESPONSE;

    /**
     * Multi Response
     * @var array
     *
     * Example:
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