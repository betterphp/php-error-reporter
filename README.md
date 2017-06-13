# PHP Error Reporting
Common error handler included in most of my projects

[![Build Status](https://ci.jacekk.co.uk/buildStatus/icon?job=PHP%20Error%20Reporter)](https://ci.jacekk.co.uk/view/libraries/job/PHP%20Error%20Reporter/)

Code coverage report is published [here by Jenkins](https://ci.jacekk.co.uk/view/libraries/job/PHP%20Error%20Reporter/Code_Coverage)

## Example composer.json
~~~json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/betterphp/php-error-reporter.git"
        }
    ],
    "require": {
        "betterphp/php-error-reporter": "dev-master"
    }
}
~~~

## Example code
~~~php
<?php

$error_handler = new \betterphp\error_reporting\sentry_reporter();
$error_handler->set_report_url(
    'crash.jacekk.co.uk',
    'something',
    'something_else',
    1337
);

$error_handler->set_environment(config::ENVIRONMENT);

if (config::ENVIRONMENT !== 'development') {
    $error_handler->register_redirect_handler();
    $error_handler->register_reporting_handler();
} else {
    $error_handler->register_output_handler();
}
~~~
