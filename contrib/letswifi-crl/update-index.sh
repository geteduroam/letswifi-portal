#!/bin/sh
set -e
. crlrc

case $method in
	remote)
		curl -sS -o indexnew.txt "$index_url" --data-urlencode ca="$ca_sub"
		;;
	local)
		php $letswifi_basepath/www/admin/ca/index/index.php "$realm" "$ca_sub" >indexnew.txt
		;;
esac

test -s indexnew.txt
mv indexnew.txt index.txt
