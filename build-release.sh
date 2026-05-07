#!/bin/sh
set -efu

SOURCE=.
cd "$(dirname "$0")"
cd "$SOURCE"
TARGET=build
PRODUCT=letswifi-portal
TAG="${TAG:-$(git status --porcelain 2>&1 | grep . >&2 && printf 'Git workspace not clean, aborting\n' >&2 || git describe --tags --exact-match HEAD)}"
VERSION="${VERSION:-$(expr "$TAG" : 'v\([0-9]\{1,\}\.[0-9]\{1,\}\.[0-9]\{1,\}\)')}"

[ -f composer.phar ] && php composer.phar --quiet --no-dev install || composer --quiet --no-dev install

WORKDIR="$TARGET/$PRODUCT"
printf %s "$WORKDIR" | grep ^/ && exit 2  # Reject absolute workdir
rm -rf "$WORKDIR"
mkdir -p "$WORKDIR"

cp -a bin htdocs locale src template "$WORKDIR"
cp -a config-dist/. "$WORKDIR/config"
cp -a vendor/fyrkat/{multilang,oauth-server,openssl}/src/fyrkat "$WORKDIR/src/"
cat src/_autoload.php | grep -v Composer | grep -v /vendor/ >"$WORKDIR/src/_autoload.php"
ln -s src/_autoload.php "$WORKDIR/autoload.php"

( cd vendor/twig/twig/src; find . -path Resources -prune -o -type f; ) | while read file
do
	lowerfile="$(printf %s "$file" | tr '[:upper:]' '[:lower:]')"
	mkdir -p "$WORKDIR/src/twig/$(dirname "$lowerfile")"
	cp "vendor/twig/twig/src/$file" "$WORKDIR/src/twig/$lowerfile"
done
cp vendor/twig/twig/LICENSE "$WORKDIR/src/twig/"
sed "/const RELEASE/ s/null/'${TAG:-"v$VERSION"}'/" src/letswifi/letswifiapp.php >"$WORKDIR/src/letswifi/letswifiapp.php"
printf '%s\n' "${TAG:-"v$VERSION"}" >"$WORKDIR/VERSION"

if [ "$(uname)" = Darwin ]
then
	# MacOS tar needs an arcane enchantment spell to behave itself
	# Otherwise you'd get extended header keyword LIBARCHIVE.xattr.com.apple.provenance
	# or resource forks which are named after any file with ._ prefixed to it
	tar -C "$TARGET" -cz --no-xattrs --no-mac-metadata --exclude ".*" -f "$TARGET/$PRODUCT-${TAG:-"v$VERSION"}.tar.gz" "$PRODUCT"
else
	# For non-MacOS we don't expect their tar binary to support these enchantment spells
	tar -C "$TARGET" -cz --exclude ".*" -f "$TARGET/$PRODUCT-${TAG:-"v$VERSION"}.tar.gz" "$PRODUCT"
fi

printf '%s\n' "$TARGET/$PRODUCT-${TAG:-"v$VERSION"}.tar.gz"
