#!/bin/sh -e

ln -s src deval
zip -qr deval.zip deval -x '*.pegjs'
rm deval
