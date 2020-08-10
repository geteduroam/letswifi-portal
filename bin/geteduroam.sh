#!/bin/sh
if test -z $1
then
	printf '\033[0musage: %s base-url [realm]\n\nexample: \033[1m%s "http://[::1]:1080" demo.eduroam.no >eduroam.eap-config\033[0m\n\nPlease note: base-url must be available from both your webbrowser and this script,\nuse -R1080:localhost:1080 if you want to test a local server from remote\n\n' "$0" "$0" >&2
	exit 2
fi

URL="$1"
SCOPE="eap-metadata"
PORT=8080
CLIENT_ID="app.geteduroam.sh"
REDIRECT_URI=http://127.0.0.1:8080/

if test -n $2
then
	REALM_PARAM="?realm=$2"
fi
AUTHORIZE_URL="$URL/oauth/authorize/?realm=demo.eduroam.no"
TOKEN_URL="$URL/oauth/token/?realm=demo.eduroam.no"
GENERATE_URL="$URL/api/eap-config/?realm=demo.eduroam.no"

REFRESH_TOKEN_FILENAME=".geteduroam-refresh-$(echo "$TOKEN_URL" | openssl sha256 | tail -c16)"

CODE_VERIFIER="$(LC_ALL=C tr -cd '[:alnum:]-_.~' </dev/urandom | head -c128)"
#CODE_VERIFIER="$(LC_ALL=C tr -cd '[:alnum:]-_' </dev/urandom | head -c43)"
STATE="$(LC_ALL=C tr -cd '[:alnum:]_-' </dev/urandom | head -c43)"


urlb64() {
	openssl base64 | sed -es@/@_@g -es@+@-@g -es@=@@
}
sha256bin() {
	#shasum -a256 | head -c64 | xxd -r -p
	openssl sha256 -binary
}
urlToQuery() {
	cut -d\? -f2 | cut -d\  -f1
}
parseQuery() {
	tr \& \\n
}
getQuery() {
	parseQuery | fgrep "${1}=" | cut -d= -f2-
}
getJson() {
	fgrep "    \"${1}\": " | cut -d\" -f4- | sed -e's/",\{0,1\}$//'
}

serve() {
	cat fifo | ( nc -l -p $PORT 2>/dev/null|| nc -l $PORT ) | while read line
	do
		error="$(echo $line | urlToQuery | getQuery error)"
		if [ -n "$error" ]
		then
			printf 'HTTP/1.0 500 Internal Server Error\r\nContent-Type: text/plain\r\n\r\n%s\r\n' "$error" >fifo
			printf '\033[1;41m%s\033[0m' "$error" >&2
		fi
		echo $line | urlToQuery
		printf 'HTTP/1.0 200 OK\r\nContent-Type: text/plain\r\n\r\nAll done! Close browser tab.' >fifo
		break
	done
}

redirect() { # $1 = url
	cat fifo | ( nc -l -p $PORT 2>/dev/null|| nc -l $PORT ) | while read line
	do
		printf 'HTTP/1.0 302 Found\r\nLocation: %s\r\n\r\n%s\r\n' "$1" "$1" >fifo
		break
	done
}

window() { # $1 = title, $2 = text
	printf '\033[A\033[A\033[A\033[A\033[F\033\033[1;44;37m\n • %s\033[K\033[0;47;30m\n\033[K\n   %s\033[K\n\033[K\033[0m\n' "$1" "$2" >&2
}

set -e

test -f "$REFRESH_TOKEN_FILENAME" && refresh_token="$(cat "$REFRESH_TOKEN_FILENAME")"

if test -n "$refresh_token"
then
	token_data="$(curl \
		--fail \
		--silent \
		--show-error \
		-HAccept:application/json \
		--data-urlencode "client_id=$CLIENT_ID" \
		--data-urlencode "redirect_uri=$REDIRECT_URI" \
		--data-urlencode grant_type=refresh_token \
		--data-urlencode "refresh_token=$refresh_token" \
		"$TOKEN_URL")" || true
	access_token=$(echo "$token_data" | getJson access_token)
	refresh_token=$(echo "$token_data" | getJson refresh_token)
fi

printf '\n\n\n\n\n' >&2
if test -z "$access_token"
then
	code_challenge="$(printf "$CODE_VERIFIER" | sha256bin | urlb64)"
	separator=$(echo "$AUTHORIZE_URL" | fgrep -q '?' && printf '&' || printf '?')
	authorize_url="${AUTHORIZE_URL}${separator}response_type=code&code_challenge_method=S256&scope=$SCOPE&code_challenge=$code_challenge&redirect_uri=$REDIRECT_URI&client_id=$CLIENT_ID&state=$STATE"
	window 'Please visit the following URL in your webbrowser' "$REDIRECT_URI"
	[ -p fifo ] || mkfifo fifo
	redirect "$authorize_url"
	window 'Please log in and approve this application in your webbrowser' 'Waiting for response...'
	code=
	while [ -z "$code" ]
	do
		response="$(serve)"
		code="$(echo $response | getQuery code)"
		state="$(echo $response | getQuery state)"
	done
	rm -f "$REFRESH_TOKEN_FILENAME"

	
	window 'Please wait' "Fetching token from $TOKEN_URL"
	token_data="$(curl \
		--fail \
		--silent \
		--show-error \
		-HAccept:application/json \
		--data-urlencode "client_id=$CLIENT_ID" \
		--data-urlencode "redirect_uri=$REDIRECT_URI" \
		--data-urlencode grant_type=authorization_code \
		--data-urlencode "code=$code" \
		--data-urlencode "code_verifier=$CODE_VERIFIER" \
		"$TOKEN_URL")"
	access_token=$(echo "$token_data" | getJson access_token)
	refresh_token=$(echo "$token_data" | getJson refresh_token)
fi

window 'Please wait' "Fetching eap-config from $GENERATE_URL"
curl --fail --silent --show-error -HAuthorization:"Bearer $access_token" "$GENERATE_URL"


window 'Success' 'Successfully downloaded eap-config file'

if test -n "$refresh_token"
then
	echo "$refresh_token" > "$REFRESH_TOKEN_FILENAME"
fi
