#!/bin/bash

git submodule foreach 'git push push master'
git push
