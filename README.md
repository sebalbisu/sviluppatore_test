# Sviluppatore test backend

## Requirements

* docker
* docker-composer

## Install

```
cp ./backend/.env.example ./backend/.env
docker-compose run --rm backend composer app-setup
```

## Run

```
docker-compose up -d
docker-compose logs -f job_manager
```

## Close 

`docker-compose down`

# Dashboard

`http://localhost:8000/`

# Tests

There are some units test in the tests/Unit/BackgroundService folder