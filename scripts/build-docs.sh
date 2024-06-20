#!/usr/bin/env bash

# This script builds documentation.

set -e

# Get the directory of this script.
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
# Get the project root directory.
PROJECT_ROOT="$(dirname "$DIR")"

docker run \
    --rm \
    -p 8000 \
    --user=`id -u`:`id -g` \
    -v ${PROJECT_ROOT}:/docs squidfunk/mkdocs-material:9.5.26 \
    "$@"
