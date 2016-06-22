Docker Project Management
=============

Helps to work with multiple git repositories and set custom build image process using docker-compose.yml


### Why do I need it?

**Case 1**

git clone or pull for each microservice is a pain.

Let's say you have microservice application wich contains 5 microservices.
Each microservice is a small aplication on it's own, so you have 5 different git repositories for them.
Docker-compose is used to tight and make work it all toghether.
That means when you start work with the whole application you need to do `git clone` 5 times with proper parameters to a proper destination and so on. If you work in a team time to time you will need do `git pull` 5 times. Git submodules are not convinient thing to use in this case.

**Case 2**

Custom build image process

Build docker appplication sometimes is not just a matter of running `docker build`. There are no need to bring havy development tools inside of you image on order bus build application. You spit the process in two phases - building and packing in image. You will have bash script or Makefile, so you need to store somewhere a command to build image and be able to build all microservices in single call.


### How it works

Using [labels](https://docs.docker.com/v1.8/compose/yml/#labels) you can define metadata for any service in docker-compose.yml.

When git repo link, branch, build and other commands are specisied as labels for a service, docker-project will parce out that information and will be able do git clone or pull for each defined repo and run command over repositories.

`docker-project` is able to run commands only over repos that have project_git label or build path.


Be default `docker-project` creates apps folder next to docker-compose.yml file. That path can be changes using parameter -a.



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

docker-project build
# since repo alredy exists runs just 'git pull'

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
  help - prints help
  your_command - defined as label for the service (example: labels: PROJECT_TEST: make test)

Arguments:
  Full name        | Short | Default          | Note
-----------------------------------------------------
  --file             -f      docker-compose.yml Alternative config file
  --apps             -a      apps               apps folder realtive to the compose file
  --extra            -x                         Extra parameters passed to command

```
