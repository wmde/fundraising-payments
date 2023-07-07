# Fundraising Payments

[![Build Status](https://travis-ci.org/wmde/fundraising-payments.svg?branch=master)](https://travis-ci.org/wmde/fundraising-payments)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/wmde/fundraising-payments/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/wmde/fundraising-payments/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/wmde/fundraising-payments/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/wmde/fundraising-payments/?branch=master)

Bounded Context for the Wikimedia Deutschland fundraising payment (sub-)domain. Used by the
[user facing donation application](https://github.com/wmde/fundraising-application) and the
"Fundraising Operations Center" (which is not public software).

## Installation

To use the Fundraising Payments library in your project, add a dependency on wmde/fundraising-payments
to your project's `composer.json` file. Here is a minimal example of a `composer.json`
file that just defines a dependency on Fundraising Payments 1.x:

```json
{
    "require": {
        "wmde/fundraising-payments": "~1.0"
    }
}
```

## Setting up the PayPal API
The payment context calls the PayPal REST API to create payments.
These API calls need credentials and a one-time setup of subscription plans 
(i.e. definition of recurring payments) on the PayPal server.
There is a command line tool to do the subscription plan setup.
You can call this command (`create-subscription-plans`) from the console in [Fundraising Application](https://github.com/wmde/fundraising-application)
or from the `bin/console` file in this bounded context. 

There is another command, `list-subscription-plans` that lists all the configured plans.

See [Configuring the PayPal API](docs/paypal_api.md) for more details on these commands and their configuration.


## Development

This project has a [Makefile](Makefile) that runs all tasks in Docker containers via
`docker-compose`. You need to have Docker and the `docker-compose` CLI
installed on your machine.

### Installing dependencies

To pull in the project dependencies via Composer, run:

    make install-php

### Running the CI checks

To run all CI checks, which includes PHPUnit tests, PHPCS style checks and coverage tag validation, run:

    make ci
    
### Running the tests

To run the PHPUnit tests run

    make test

To run a subset of PHPUnit tests or otherwise pass flags to PHPUnit, run

    docker-compose run --rm app ./vendor/bin/phpunit --filter SomeClassNameOrFilter

## Architecture

This Bounded context follows the architecture rules outlined in [Clean Architecture + Bounded Contexts](https://www.entropywins.wtf/blog/2018/08/14/clean-architecture-bounded-contexts/).

![Architecture diagram](https://user-images.githubusercontent.com/146040/44942179-6bd68080-adac-11e8-9506-179a9470113b.png)
