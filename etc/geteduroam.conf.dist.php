<?php return [
	'realm.certificates' => [
'-----BEGIN CERTIFICATE-----
MIID6DCCAtCgAwIBAgIBADANBgkqhkiG9w0BAQsFADBWMQswCQYDVQQGEwJOTzES
MBAGA1UEBwwJVHJvbmRoZWltMRMwEQYDVQQKDApVbmluZXR0IEFTMR4wHAYDVQQD
DBVnZXRlZHVyb2FtLm5vIGRlbW8gQ0EwHhcNMTkxMDI4MjIzNzU5WhcNMjkxMDI1
MjIzNzU5WjBWMQswCQYDVQQGEwJOTzESMBAGA1UEBwwJVHJvbmRoZWltMRMwEQYD
VQQKDApVbmluZXR0IEFTMR4wHAYDVQQDDBVnZXRlZHVyb2FtLm5vIGRlbW8gQ0Ew
ggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQCnk9ui7c00MjJt5nedAj0w
dIOvTa3HtYdFeziE2a3i32TaxZEuB0ioYw9Od7rkdzo9IW3Q5aQbeSFSws/tPWmW
36XtxTgZ9LEYkb9JzLxCLeeBzDtMHkk0hGpHRPqsHG9yKGNVg3tNv0BY81MzxQQN
ZybyWFCbkbE+9I67e48MonwlNVz6LMUMgi4P2os55JiRR0wvOETQmi2f/o60lxBs
q4M6E9XMIPKQgoZ+La/Aov12dYlLEWdK7X17i6iLBIMGljrWXBDXkkYaxbIeoIt8
EQr+z96WcLbwoCRfesCsJN2kEN4QZMzt79039xCyuZ1ah12muTIZSrMugE/EjrqV
AgMBAAGjgcAwgb0wHQYDVR0OBBYEFOivI6HDBGDcOr7hCUEdQMXQ3lQ7MH4GA1Ud
IwR3MHWAFOivI6HDBGDcOr7hCUEdQMXQ3lQ7oVqkWDBWMQswCQYDVQQGEwJOTzES
MBAGA1UEBwwJVHJvbmRoZWltMRMwEQYDVQQKDApVbmluZXR0IEFTMR4wHAYDVQQD
DBVnZXRlZHVyb2FtLm5vIGRlbW8gQ0GCAQAwDwYDVR0TAQH/BAUwAwEB/zALBgNV
HQ8EBAMCAYYwDQYJKoZIhvcNAQELBQADggEBADQk5KyfrOONYweGWPvCS+zkLsjt
PW84NHu6MQCqZ4shG7JUgpU5Un9MZvLdu2EN9rHQN0koy/fJy7sdT1fJNNq8pN0M
/s/+IC4qNebrDXqCHiP8iI+oM3xRNxUSdm85MO/Lswc7h/4h06X7amUv5LOzauhQ
SXDj6TVNcQfnxWvXGPBPil4bXdXpfs8WhJPvLXd88YV8lA/CzSqc7skJc/ARRTKr
6QcIKEXBNiU+eJII33/ZRuYXMfHvdT/1M2k7UgFTU9igXxkXsnihRii+kT64934D
/vXkvSJYZcCKV4BoysYUVPoQ1nijXJeCJh8XvFdeT0p6HXdba68o2XGL4HE=
-----END CERTIFICATE-----'
		],
	'realm.servernames' => ['demo.eduroam.no'],
	'pdo.dsn' => 'sqlite:' . dirname( __DIR__ ) . '/var/geteduroam-dev.sqlite',
	'oauth.jwt.key' => hex2bin( 'REPLACEMEREPLACEMEREPLACEMEREPLACEMEREPLACEMEREPLACEMEREPLACEMER' ),
	'oauth.clients' => [
			['clientId' => 'f817fbcc-e8f4-459e-af75-0822d86ff47a', 'redirectUris' => ['http://localhost:8080/'], 'scopes' => ['eap-metadata']],
			['clientId' => '07dc14f4-62d1-400a-a25b-7acba9bd7773', 'redirectUris' => ['geteduroam://auth_callback'], 'scopes' => ['eap-metadata']],
			['clientId' => 'no.fyrkat.oauth', 'redirectUris' => ['http://[::1]:1234/callback/'], 'scopes' => ['eap-metadata', 'testscope']],
		],
];
