#!/bin/bash
# Sourced by start.sh / stop.sh / restart.sh.
# Sets DOCKER_HOST to the rootless socket when running as a non-root user.

if [ "$(id -u)" -ne 0 ]; then
    export DOCKER_HOST="unix:///run/user/$(id -u)/docker.sock"
    export XDG_RUNTIME_DIR="/run/user/$(id -u)"
fi
