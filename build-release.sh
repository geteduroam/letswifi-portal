#!/bin/sh
set -efu

: "${SOURCE:=.}"
: "${TARGET:=build}"
: "${PRODUCT:=letswifi-portal}"
dirs='bin config-dist defaults htdocs locale sql src template'
git status --short --ignored $dirs & >&2

cd "$(dirname "$0")"
cd "$SOURCE"
test -n "${TAG:=$(git describe --tags)}"
WORKDIR="$TARGET/$PRODUCT-$TAG"

printf %s "$WORKDIR" | grep ^/ && exit 2  # Reject absolute workdir
rm -rf "$WORKDIR"
mkdir -p "$WORKDIR"

export COMPOSER_VENDOR_DIR=build/vendor
[ -f composer.phar ] \
	&& php composer.phar --quiet --no-plugins --no-scripts --no-dev install \
	|| composer --quiet --no-plugins --no-scripts --no-dev install

cp -R $dirs LICENSE CREDITS.md "$WORKDIR"
rm -f "$WORKDIR/htdocs/simplesaml"
cp -R "$COMPOSER_VENDOR_DIR"/fyrkat/{configmap,multilang,oauth-server,openssl}/src/fyrkat "$WORKDIR/src/"
awk '$0 ~ /Composer/ { exit } { print $0 }' src/_autoload.php >"$WORKDIR/src/_autoload.php"

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
chmod o= "$WORKDIR/config-dist/certs"

if [ "$(uname)" = Darwin ]
then
	# MacOS tar needs an arcane enchantment spell to behave itself
	# Otherwise you'd get extended header keyword LIBARCHIVE.xattr.com.apple.provenance
	# or resource forks which are named after any file with ._ prefixed to it
	tar -C "$TARGET" -cz --owner root:0 --group wheel:0 --no-xattrs --no-acls --no-fflags --no-mac-metadata --exclude ".*" -f "$TARGET/$PRODUCT-$TAG.tar.gz" "$PRODUCT-$TAG"
else
	# For non-MacOS we don't expect their tar binary to support these enchantment spells
	tar -C "$TARGET" -cz --owner root:0 --group wheel:0 --exclude ".*" -f "$TARGET/$PRODUCT-$TAG.tar.gz" "$PRODUCT-$TAG"
fi

printf '%s\n' "$TARGET/$PRODUCT-$TAG.tar.gz"
