# API Usage

>[!CAUTION]
> The API code is not finished yet and breaking changes can be expected

Most operations that can be done through the web interface, can also be done through the API.
In order to use the API, you need an API token.

## Setting up an API account

Edit the `clients.conf.php` file and add the following:

```php
	'my.api.client.name' => [
		'clientId' => 'my.api.client.name',
		'scopes' => ['admin'],
		'clientSecret' => 'abc',
	],
```

Replace `my.api.client.name` with a name that you pick yourself.
Pick a secure and unguessable **clientSecret**.
Please be very sure that the name cannot collide with any username or affiliation.

In your provider configuration or realm configuration, make sure that this client ID is listed as adminstrator.

```php
	'admins' => [
		'my.api.client.name',
		// ..multiple admins..
	],
```

Now you can obtain your API key.

```sh
% curl -sS \
	-d grant_type=client_credentials \
	-d client_id=my.api.client.name \
	-d client_secret=abc \
	-d scope=admin \
	'https://HOSTNAME/oauth/token/'
```

If succesful, the result is a JSON document with a key called `access_token`.

You can use this API key in any request to the configuration panel.

## List and revoke current requesters

A requester is the actual identity of pseudo-credentials in the system.

```sh
% curl -HAuthorization:Bearer\ $TOKEN https://HOSTNAME/admin/requesters/
```

Returns JSON in the following format:

```json
{
	"requesters": {
		"samluser@realm.example.com": {
			"earliest_valid": "YYYY-MM-DD",
			"last_valid": "YYYY-MM-DD",
			"requester": {
				"name": "samluser",
				"realm": "realm.example.com"
			},
			"valid_on": "YYYY-MM-DDThh:mm:ss+00:00",
			"total_accounts": 5,
			"valid_accounts": 5
		}
	}
}
```

The `valid_on` is the current date and time; as other fields are influenced by the current time.

You can revoke someone like this, copy the `valid_on` field from your result, or build the value yourself using `date +%Y-%m-%dT%H:%M:%S+00:00`

```sh
% curl -sS --fail-with-body -HAuthorization:Bearer\ $TOKEN \
	-d revoke_requester=samluser \
	-d realms=realm.example.com \
	-d valid_on=$(date +%Y-%m-%dT%H:%M:%S+00:00) \
	https://HOSTNAME/admin/requesters/ \
```

Please note that at this time, the API will answer with 302 Found on success.
This will be fixed in a later release.

## Generate CRL

```sh
% curl -sS --fail-with-body -HAuthorization:Bearer\ $TOKEN \
	--get -o "example.com Let's Wi-Fi CA.crl" \
	-d realm_id=example.com \
	-d "ca=CN=example.com+Let's+Wi-Fi+CA" \
	-d file=crl-pem \
	'https://HOSTNAME/admin/realms/certificate.php'
```

or (identical effect)

```sh
% curl -sS --fail-with-body -HAuthorization:Bearer\ $TOKEN \
	-o "example.com Let's Wi-Fi CA.crl" \
	'https://HOSTNAME/admin/realms/certificate.php?realm_id=example.com&ca=CN%3Dexample.com+Let%27s+Wi-Fi+CA&file=crl-pem'
```
