# Fundraising Payments

[![Build Status](https://travis-ci.org/wmde/fundraising-payments.svg?branch=master)](https://travis-ci.org/wmde/fundraising-payments)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/wmde/fundraising-payments/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/wmde/fundraising-payments/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/wmde/fundraising-payments/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/wmde/fundraising-payments/?branch=master)

Bounded Context for the Wikimedia Deutschland fundraising payment (sub-)domain. Used by the
[user facing donation application](https://github.com/wmde/FundraisingFrontend) and the
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

You can also look at [the dependency graph of the different namespaces](docs/dependency-graph.svg).

To regenerate the dependency graph, you can either use `deptrac` directly (on a system or container that has both PHP 
and [GraphViz](https://graphviz.org/) available):

    docker-compose run --rm --no-deps app php ./vendor/bin/deptrac --formatter=graphviz-image --output=docs/dependency-graph.svg

Or you can use `deptrac` to generate the input file inside the Docker container and use `dot` to create the SVG:
    
    docker-compose run --rm --no-deps app php ./vendor/bin/deptrac --formatter=graphviz-dot --output=docs/dependency-graph.dot 
    dot -odocs/dependency-graph.svg -Tsvg docs/dependency-graph.dot
