Docker Project Management
=============

Helps to work with multiple git repositories and set custom build image process using docker-compose.yml


### Why do I need it?

**Case 1**

git clone or pull for each microservice is a pain.

Let's say you have a microservice application which contains 5 microservices.
Each microservice is a small application on it's own, so you have 5 different git repositories for them.
Docker-compose is used tightly to make it all work together.
This means when you start work with the whole application, you will need to do `git clone` 5 times with proper parameters to a proper destination and so on. If you work in a team time to time you will need to do `git pull` 5 times. Git submodules are not convinient thing to use in this case.

**Case 2**

Custom build image process

Building docker appplications sometimes is not just a matter of running `docker build`. There is no need to bring heavy development tools inside of your image in order to build applications. You split the process in two phases - building and packing in the image. You will have bash script or Makefile, so you need to store somewhere a command to build the image and be able to build all microservices in single call.


### How it works

Using [labels](https://docs.docker.com/v1.8/compose/yml/#labels) you can define metadata for any service in docker-compose.yml.

Where git repo link, branch, build and other commands are specified as labels for a service, docker-project will parse out that information and will be able to git clone or pull for each defined repo and run commands over the repositories.

`docker-project` is able to run commands only over repos that have project_git label or build path.


By default `docker-project` creates apps folder next to docker-compose.yml file. That path can be changes using parameter -a.



### Specification

Works with docker-compose.yml version 2 only

```yml
version: "2"
services:
    users:
        image: vendor/users
        expose:
            - 80
        labels:
            project.git: https://github.com/vendor/users.git    # linking git repo
            project.git.branch: cool-feature                    # custom branch, default is master
            project.build: make                                 # defining build command
            project.custom: echo __image__                      # defining custom command
```

Meta tags can be used in commands definitions as well as extra (`-x`) parameter string:
* `__image__`   - image name
* `__service__` - service name

### Usage

```bash
docker-project update
# first time it will create apps/vendor/users folder
# vendor/users is taken from git link
# unless build: parameters is specified
# apps/ is relative to yml fine and can be changed
# then run 'git clone' the repo localy

docker-project build -x dev
# runs 'make dev' at apps/vendor/users
# -x add anything to the end of command

docker-project update
# since repo alredy exists it runs just 'git pull'

docker-project custom
# runs `echo vendor/users`

docker-project shell -x git checkout master
# runs `git checkout master` over all registered repos

```


### `docker-project help` output:

```
docker project management tool 0.0.1

Usage:
  docker-project <command> <arguments>

Commands:
  update - clones or pulls application source
  shell - uses extra parameter to run shell command for each app
  status - prints current recogniser services with repos and their commands
  help - prints help
  your_command - defined as label for the service (example: labels: PROJECT_TEST: make test)

Arguments:
  Full name        | Short | Default          | Note
-----------------------------------------------------
  --file             -f      docker-compose.yml Alternative config file
  --apps             -a      apps               apps folder realtive to the compose file
  --extra            -x                         Extra parameters passed to command
```

### Install binary

```bash
curl -O https://github.com/webreactor/docker-project/releases/download/0.0.1-alfa/docker-project
chmod a+x docker-project
sudo cp docker-project /usr/local/bin/
```

Depenencies:

*php5-cli

### Build and install manually

```
git clone https://github.com/webreactor/docker-project.git
make
sudo make install
```

Depenencies:

* php5-cli
* php composer
* make
* php.ini phar.readonly = off
