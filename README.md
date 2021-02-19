# Event Engine - Code Generator Cody

PHP Code Generator for Event Engine powered by Cody.

> If you not familiar with PHP code generation by Cody please take a look at the [PHP Cody tutorial](https://github.com/event-engine/inspectio/wiki/PHP-Cody-Tutorial).

## Preparation

Please make sure you have installed [Docker](https://docs.docker.com/install/ "Install Docker")
and [Docker Compose](https://docs.docker.com/compose/install/ "Install Docker Compose").

## Usage with Event Engine Skeleton

The following tutorial shows how to connect and use the *Cody* bot with the [Event Engine Skeleton](https://github.com/event-engine/php-engine-skeleton "Event Engine Skeleton on GitHub")
to generate PHP code from [InspectIO](https://event-engine.io/free-inspectio/ "Free version of InspectIO") event map.

> It's important to follow each setup step. It requires a specific folder structure to function correctly!

1. Create a new folder (f.e. in your home directory) that will contain the [Event Engine Skeleton](https://github.com/event-engine/php-engine-skeleton "Event Engine Skeleton on GitHub") as well as the coding bot.
```
mkdir cody-tutorial
cd cody-tutorial
```

2. Install Event Engine Skeleton via Composer

```
docker run --rm -it \
    -v $(pwd):/app \
    -u $(id -u ${USER}):$(id -g ${USER}) \
    prooph/composer:7.4 create-project -v \
        --stability dev \
        --remove-vcs \
        event-engine/php-engine-skeleton \
        php-engine-tutorial-demo
```

3. Setup Event Engine Skeleton
```
cd php-engine-tutorial-demo
sudo chown $(id -u -n):$(id -g -n) . -R
docker-compose up -d
docker-compose run php php scripts/create_event_stream.php
```

Head over to [https://localhost](https://localhost) to check if the containers are up and running. 
Accept the self-signed certificate and you should see a "It works" message.

4. Setup coding bot
```
# Change back to root directory cody-tutorial
cd ..

# Install Cody Server using composer
docker run --rm -it \
    -v $(pwd):/app \
    -u $(id -u ${USER}):$(id -g ${USER}) \
    prooph/composer:7.4 create-project -v \
        --stability dev \
        --remove-vcs \
        event-engine/php-code-generator-cody \
        /app/cody-bot

# Change into bot directory and prepare first start
cd cody-bot
cp .env.dist .env # Adjust UID in .env if needed
cp docker-compose.yml.dist docker-compose.yml
cp codyconfig.php.dist codyconfig.php
```

5. Mount Event Engine Skeleton to Cody Bot

The Cody Server (coding bot) runs in a docker container. To be able to generate source code it needs access to a 
code repository. In our case this is the `php-engine-tutorial-demo` directory. We can mount the directory to the server 
by modifying `docker-compose.yml` located in the server directory `cody-bot`. The file should look like this.

```yaml
version: '2'

services:

  # The actual client application
  iio-cody:
    image: prooph/php:7.4-cli
    volumes:
      - .:/app
      # mount your business application folder to /service
      - ../php-engine-tutorial-demo:/service
    user: ${UID}
    ports:
      - 3311:8080
    command: vendor/bin/php-watcher public/index.php
```

6. Prepare codyconfig 

*Cody* is a proxy to your own code generation logic. A central `codyconfig.php` file tells Cody what to do when it 
receives tasks from [InspectIO](https://event-engine.io/free-inspectio/ "Free version of InspectIO"). As a first step 
we change to `Context` config to fit the Event Engine Skeleton configuration. Open `cody-bot/codyconfig.php` and replace
`$context` line with this:

```php
$context = new Context(
    'MyService',
    'CodyTutorial',
    '/service/src'
);
```

7. Start Cody

Finally let's start Cody:

```
./dev.sh
```

*Please Note: When adding or changing something in the Cody Bot source code a file watcher takes care of restarting the server.*

### InspectIO

**InspectIO is a modeling tool specifically designed for remote Event Storming. It ships with realtime collaboration
features for teams (only available in paid version). The free version is a standalone variant without any backend
connection. Your work is stored in local storage and can be exported. It is hosted on Github Pages and has the same
code generation capabilities as the SaaS version.**

You can use [InspectIO free version](https://event-engine.io/free-inspectio/ "Free version of InspectIO") and model 
the [building tutorial](https://event-engine.io/tutorial/intro.html#2-1 "Event Engine Building Tutorial") on the event 
map (no login required). 

Create a new board called "Cody Tutorial". You'll be redirected to the fresh board. Choose "Cody" from top menu to
open the **Cody Console**. Just hit ENTER in the console to connect to the default Cody server that we've setup and started
in the previous step.

Finally type "**/help**" in the console to let Cody explain the basic functionality.

Cody will generate the following boilerplate for you:
- Event Engine API description for commands, aggregates and domain events
- Command, aggregate and domain event classes with corresponding value objects based on [metadata](https://github.com/event-engine/inspectio/wiki/Card-Metadata "InspectIO card metadata") (JSON schema)
- Glue code between command, corresponding aggregate and corresponding domain events

### Cockpit

[Cockpit](https://github.com/event-engine/cockpit) is an admin UI for Event Engine. You can access it on port `4444`: [https://localhost:4444](https://localhost:4444).
The Event Engine skeleton is preconfigured with the [cockpit-php-backend handler](https://github.com/event-engine/cockpit-php-backend).

*Note: To avoid CORS issues the Nginx configuration of the Cockpit server is modified to also act as a reverse proxy for requests from Cockpit to the backend.*

You can execute the built-in `HealthCheck` query to very that Cockpit can access the Event Engine backend.

![HealthCheck](https://github.com/event-engine/php-engine-skeleton/blob/master/docs/assets/cockpit_health_check.png?raw=true)
