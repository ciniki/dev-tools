#!/bin/bash

git submodule foreach "git commit -am '$1' || :"
git commit -am "$1"
