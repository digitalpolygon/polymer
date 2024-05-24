#!/bin/bash

# If variable DDEV_PROJECT is not set then exit.
if [ -z "$DDEV_PROJECT" ]; then
  echo "DDEV_PROJECT is not set. Please set DDEV_PROJECT to the name of the project."
  exit 1
fi

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
PROJECT_DIR=$(realpath "${SCRIPT_DIR}/../..")

SPHINX_DIR="${SCRIPT_DIR}"
DOCKERFILE="${SPHINX_DIR}/Dockerfile"
IMAGE_NAME="$DDEV_PROJECT/sphinx:latest"
CONTAINER_LABEL="$DDEV_PROJECT-sphinx"

print_env() {
  env
}

build_image() {
  docker build -t "${IMAGE_NAME}" "${SPHINX_DIR}"
}

build_docs() {
  build_image
  docker run \
    --rm \
    --user=`id -u`:`id -g` \
    -v "${SPHINX_DIR}:/workspace" \
    -w=/workspace \
    "${IMAGE_NAME}" make clean html latexpdf docx
}

watch_docs() {
  build_image
  docker run \
    --rm \
    --user=`id -u`:`id -g` \
    -v "${SPHINX_DIR}:/workspace" \
    -w=/workspace \
    -p 8000 \
    -l "$CONTAINER_LABEL" \
    "${IMAGE_NAME}" /bin/bash -c 'make clean; sphinx-autobuild -b html ./source ./build --host 0.0.0.0'
}

find_port() {
  docker ps --filter "label=$CONTAINER_LABEL" --format "{{.Ports}}"
}

bash_docs() {
  build_image
  docker run --rm --user=`id -u`:`id -g` -it -v "${SPHINX_DIR}:/workspace" -w=/workspace "${IMAGE_NAME}" bash
}

case "$1" in
  build)
    build_docs
    ;;
  watch)
    watch_docs
    ;;
  bash)
    bash_docs
    ;;
  watch-port)
    find_port
    ;;
  env)
    print_env
    ;;
  *)
    echo "Usage: $0 {build|watch|bash|watch-port}"
    exit 1
esac
