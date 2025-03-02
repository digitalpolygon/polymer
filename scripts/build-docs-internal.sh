#!/usr/bin/env bash

set -ev

git config --local user.email "actions@github.com"
git config --local user.name "Drush Documentation Bot"
git rev-parse HEAD
mike alias 0.x latest --update-aliases
mike set-default latest
#mike deploy
