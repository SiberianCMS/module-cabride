#!/usr/bin/env bash

version=`cat ./package.json |grep "version" |sed -n '1p' |cut -d '"' -f 4`
name=`cat ./package.json |grep "name" |sed -n '1p' |cut -d '"' -f 4`

# Clean-up
rm -f ./$name-*.zip

# Zip
zip -r -9 -x@./tools/exclude.list ./$name-$version.zip ./
