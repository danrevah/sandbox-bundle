# SandboxBundle &nbsp; (in-development)
[![Build Status](https://travis-ci.org/danrevah/SandboxBundle.svg?branch=master)](https://travis-ci.org/danrevah/SandboxBundle)

> Symfony2 SandboxBundle
> used for creating a Sandbox enviorment and response for API

 * [Installation](#installation)
 * [Create a Sandbox environment](#create-a-sandbox-environment)

## Installation

The following instructions outline installation using Composer. If you don't
have Composer, you can download it from [http://getcomposer.org/](http://getcomposer.org/)

 * Run either of the following commands, depending on your environment:

```
$ composer require "danrevah/sandboxbundle":"1.0.*" 
$ php composer.phar require "danrevah/sandboxbundle":"1.0.*"
```


## Create a Sandbox environment

1. Copy the file from your `project-root-directory/web/app_dev.php` into the same directory and call the new file `app_sandbox.php`.
2. In the file you've just created `app_sandbox.php` change this line `$kernel = new AppKernel('dev', true); ` to this line `$kernel = new AppKernel('sandbox', true); `
3. Go to `project-root-directory/app/AppKernel.php` and change this line  `if (in_array($this->getEnvironment(), array('dev', 'test'))) { ``` to this line `if (in_array($this->getEnvironment(), array('dev', 'test','sandbox'))) { ```
4. In the AppKernel.php file after the `if case` you've just edited, add this `if case` also:
```php
    if (in_array($this->getEnvironment(), array('sandbox'))) {
        $bundles[] = new danrevah\SandboxBundle\SandboxBundle();
    }
```
5. Copy the file from `project-root-directory/app/config/config_dev.yml` and call it `config_sandbox.yml`.
6. Add this to the end of your `config_sandbox.yml`
```yml
    sandbox:
      response:
        force: true
        # Force mode means you won't be able to "fall"
        # to the REAL controller if a Sandbox response is not available.
        # It will produce an error instead.
```
7. That's it! you can now access your sandbox environment using `app_sandbox.php`
