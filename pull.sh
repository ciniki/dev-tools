#!/bin/bash

git pull
git submodule foreach 'git pull'

#./versions.pl -i >site/_versions.ini
