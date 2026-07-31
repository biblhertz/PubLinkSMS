#!/bin/bash
# Rebuild and restart containers without wiping the database volume.
# Use this to deploy code changes. Use restart.sh if you need a full teardown.
source "$(dirname "$0")/docker-env.sh"

docker compose down --rmi all --remove-orphans
images=$(docker images -q)
[ -n "$images" ] && docker rmi $images
docker compose --verbose up -d
