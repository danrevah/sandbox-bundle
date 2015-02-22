# SandboxBundle &nbsp; [![Build Status](https://scrutinizer-ci.com/g/danrevah/SandboxBundle/badges/build.png?b=master)](https://scrutinizer-ci.com/g/danrevah/SandboxBundle/build-status/master) [![Code Coverage](https://scrutinizer-ci.com/g/danrevah/SandboxBundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/danrevah/SandboxBundle/?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/danrevah/SandboxBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/danrevah/SandboxBundle/?branch=master)

> SandboxBundle is a Symfony2 Bundle which is mostly used in conditions when you don't want to reach your real controller in a Sandbox/Testing environment.

> For example, if you have a controller which handles some action let's say a Purchase, 
> you could use a fake response instead of creating a real purchase request.
> Only by using annotaions and in your Sandbox/Testing environment.


## Table of contents

 * [Installation](#installation)
 * [Create a Sandbox environment](#create-a-sandbox-environment)
 * [Single response annotation](#single-response-annotation)
 * [Multi response annotation](#multi-response-annotation)


## Installation

The following instructions outline installation using Composer. If you don't
have Composer, you can download it from [http://getcomposer.org/](http://getcomposer.org/)

 * Run either of the following commands, depending on your environment:

```
$ composer require "danrevah/sandbox-bundle":"dev-master" 
$ php composer.phar require "danrevah/sandbox-bundle":"dev-master"
```


## Create a Sandbox environment

* Copy the file from your `project-root-directory/web/app_dev.php` into the same directory and call the new file `app_sandbox.php`.
* In the file you've just created `app_sandbox.php` change this line `$kernel = new AppKernel('dev', true); ` to this line `$kernel = new AppKernel('sandbox', true); `
* Go to `project-root-directory/app/AppKernel.php` and change this line  `if (in_array($this->getEnvironment(), array('dev', 'test'))) ``` to this line `if (in_array($this->getEnvironment(), array('dev', 'test','sandbox'))) ```
* In the AppKernel.php file after the `if case` you've just edited, add this `if case` also:
```php
    if (in_array($this->getEnvironment(), array('sandbox'))) {
        $bundles[] = new danrevah\SandboxBundle\SandboxBundle();
    }
```
* Copy the file from `project-root-directory/app/config/config_dev.yml` and call it `config_sandbox.yml`.
* Add this to the end of your `config_sandbox.yml`:
```yml
    sandbox:
      response:
        force: true
        # Force mode means you won't be able to "fall"
        # to the REAL controller if a Sandbox response is not available.
        # It will produce an error instead.
```
* **That's it! you can now access your sandbox environment using `app_sandbox.php`**

## Single Response Annotation

```php
    /**
     * GET /resource
     *
     * @ApiSandboxResponse(
     *      responseCode=200,
     *      type="json",
     *      parameters={
     *          {"name"="some_parameter", "required"=true}
     *      },
     *      resource="@SandboxBundle/Resources/responses/token.json"
     * )
     */
    public function getAction() {
        return array('foo');
    }
```

* `responseCode` (default = 200) - it's the Http response code of the Sandbox response.
* `type` (default = 'json') - you can choose between 'json' and 'xml'.
* `parameters` (default = array()) - this is used to validate required parameters in the Sandbox API in order to produce an Exception if the parameter is missing.
* `resource` (**required**) - the real controller will be overwritten by this, in the above example it will ALWAYS return the contents of the `token.json` instead of the 'foo' from the real getAction(), it won't even go inside.


## Multi Response Annotation

```php
    /**
     * POST /resource
     *
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
    public function postAction() {
        return array('bar');
    }
```

* `responseCode` (default = 200) - it's the Http response code of the Sandbox response.
* `type` (default = 'json') - you can choose between 'json' and 'xml'.
* `parameters` (default = array()) - this is used to validate required parameters in the Sandbox API in order to produce an Exception if the parameter is missing.
* `multiResponse` (**required**) - used to find matching `parameters` from the request and if the values are equal, it returns a response with the `resource` file mentiond. the parameters `type` and `responseCode` insdie the `multiResponse` are not required it will use the parent parameters if none is found inside a matching case.
* `responseFallback` (**required**) - it's using this response when none of the `multiResponse` `caseParams` has been matched.

