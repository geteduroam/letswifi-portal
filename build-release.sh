#!/bin/sh
set -efu

: "${SOURCE:=.}"
: "${TARGET:=build}"
: "${PRODUCT:=letswifi-portal}"

cd "$(dirname "$0")"
cd "$SOURCE"
: "${TAG:=$(git status --porcelain 2>&1 | grep . >&2 && printf 'Git workspace not clean, aborting\n' >&2 || git describe --tags --exact-match HEAD)}"
WORKDIR="$TARGET/$PRODUCT-$TAG"

printf %s "$WORKDIR" | grep ^/ && exit 2  # Reject absolute workdir
rm -rf "$WORKDIR"
mkdir -p "$WORKDIR"

export COMPOSER_VENDOR_DIR=build/vendor
[ -f composer.phar ] && php composer.phar --quiet --no-dev install || composer --quiet --no-dev install

cp -R bin config-dist defaults htdocs locale src template "$WORKDIR"
cp -R "$COMPOSER_VENDOR_DIR"/fyrkat/{configmap,multilang,oauth-server,openssl}/src/fyrkat "$WORKDIR/src/"
grep -v -e Composer -e /vendor/ <src/_autoload.php >"$WORKDIR/src/_autoload.php"

( cd "$COMPOSER_VENDOR_DIR/twig/twig/src"; find . -path Resources -prune -o -type d; ) | tr '[:upper:]' '[:lower:]' | xargs -I % mkdir -p "$WORKDIR/src/twig/%"
( cd "$COMPOSER_VENDOR_DIR/twig/twig/src"; find . -path Resources -prune -o -type f; ) | while read file
do
	lowerfile="$(printf %s "$file" | tr '[:upper:]' '[:lower:]')"
	ln "$COMPOSER_VENDOR_DIR/twig/twig/src/$file" "$WORKDIR/src/twig/$lowerfile"
done
ln "$COMPOSER_VENDOR_DIR/twig/twig/LICENSE" "$WORKDIR/src/twig/"
sed "/const RELEASE/ s/null/'${TAG}'/" src/letswifi/letswifiapp.php >"$WORKDIR/src/letswifi/letswifiapp.php"
printf '%s\n' "${TAG}" >"$WORKDIR/VERSION"
chmod -R +rX,u+w,g-w,o-w,-st "$WORKDIR"

if [ "$(uname)" = Darwin ]
then
	# MacOS tar needs an arcane enchantment spell to behave itself
	# Otherwise you'd get extended header keyword LIBARCHIVE.xattr.com.apple.provenance
	# or resource forks which are named after any file with ._ prefixed to it
	tar -C "$TARGET" -cz --owner root:0 --group wheel:0 --no-xattrs --no-mac-metadata --exclude ".*" -f "$TARGET/$PRODUCT-$TAG.tar.gz" "$PRODUCT-$TAG"
else
	# For non-MacOS we don't expect their tar binary to support these enchantment spells
	tar -C "$TARGET" -cz --owner root:0 --group wheel:0 --exclude ".*" -f "$TARGET/$PRODUCT-$TAG.tar.gz" "$PRODUCT-$TAG"
fi

printf '%s\n' "$TARGET/$PRODUCT-$TAG.tar.gz"
