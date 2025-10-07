#!/bin/bash

docker stop buro-laboral-backend
docker rm buro-laboral-backend

git pull --no-edit

# BRANCH_NAME=$(git rev-parse --abbrev-ref HEAD)

CONTAINER_NAME="buro-laboral-backend"

export COMPOSE_BAKE=true
export CONTAINER_NAME

docker-compose -f docker-compose.yml up -d --build