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

    /**
     * Request parameters object
     * @var array
     *
     * Example:
     *      parameters = {
     *          {"name"="param1", "type"="string", "required"=true, value="*"},
     *          {"name"="param2", "type"="integer", "required"=false, value="1"}
     *      }
     */
    public $parameters = [];
}