# Event Engine - Code Generator Cody

PHP Code Generator for Event Engine powered by Cody.

## Installation

Please make sure you have installed [Docker](https://docs.docker.com/install/ "Install Docker")
and [Docker Compose](https://docs.docker.com/compose/install/ "Install Docker Compose").

* Please copy the file `.env.dist` to `.env` and configure for your needs e.g. UID!
* Please copy the file `docker-compose.yml.dist` to `docker-compose.yml` and configure it for your needs e.g. mount 
business application folder!
* Please copy the file `codyconfig.php.dist` to `codyconfig.php` and configure for your needs!

Install Composer dependencies:

```
$ docker run --rm -it -v $(pwd):/app prooph/composer:7.4 install
```

Start the application with:

```
$ docker-compose up -d --no-recreate
```

## Code Generation

Open your board on [InspectIO](https://inspect.event-engine.io/inspectio), open Cody console and connect to Cody with 
`/connect http://localhost:3311`. 
