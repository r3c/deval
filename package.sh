#!/bin/sh -e

tag="$(git tag --points-at HEAD)"
target="deval-$tag.zip"

if [ -z "$tag" ]; then
	echo >&2 "error: HEAD doesn't point to a tag"
	exit 1
fi

( cd src && npm install --silent )
ln -s src deval
zip -qr "$target" deval -x '*.pegjs'
rm deval

echo "built release active: $target"
