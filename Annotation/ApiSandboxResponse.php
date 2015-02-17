<?php

namespace danrevah\SandboxResponseBundle\Annotation;

use danrevah\SandboxResponseBundle\Enum\ApiSandboxResponseTypeEnum;
use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
final class ApiSandboxResponse extends Annotation
{
    /**
     * Resource path for response
     * @var string
     *
     * Example:
     *      resource="@SandboxResponseBundle/Resources/responses/token.json"
     */
    public $resource;

    // Default response type is JSON
    public $type = ApiSandboxResponseTypeEnum::JSON_RESPONSE;

    // Response code to output, default is 200
    public $responseCode = 200;

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