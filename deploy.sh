#!/bin/bash

git pull --no-edit

BRANCH_NAME=$(git rev-parse --abbrev-ref HEAD)

if [ "$BRANCH_NAME" == "master" ]; then
    CONTAINER_NAME="buro-laboral-backend"
else
    CONTAINER_NAME="buro-laboral-backend-$BRANCH_NAME"
fi

export COMPOSE_BAKE=true
export CONTAINER_NAME

docker-compose -f docker-compose.yml up -d --build