#!/bin/sh -e

( cd src && npm install )
ln -s src deval
zip -qr deval.zip deval -x '*.pegjs'
rm deval
