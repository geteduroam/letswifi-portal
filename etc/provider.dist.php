<?php return [
	'providers' => [
		'*' => [
			'display_name' => 'Default provider',
			'description' => 'The default provider',
			'realm' => [
				'staff' => ['staff.example.com'],
				'student' => ['student.example.com'],
				'*' => ['example.com']
			],
			'contact' => 'example.com',
			'auth' => [
				'service' => 'DevAuth',
				'param' => [],
			],
			'oauth' => [
				'clients' => (require __DIR__ . DIRECTORY_SEPARATOR . 'clients.php'),
				'pdo' => [
					'dsn' => 'sqlite:' . dirname( __DIR__ ) . '/var/letswifi-dev.sqlite',
					'username' => null,
					'password' => null,
				],
				'keys' => [
					'my_kid' => [
						'key' => 'N8Je0+zjMwQX8bkKXu7XyKUDRszsuRETDtYrKMtRlPU=',
						'iss' => 1676291040,
						'exp' => null,
					],
				],
			],
		],
	],
	'realm' => [
		'staff.example.com' => [
			'display_name' => 'Staff',
			'description' => 'Network for staff',
			'server_names' => ['radius.example.com'],
			'signer' => 'CN=example.com Let\'s Wi-Fi CA',
			'trust' => ['C=US, O=Let\'s Encrypt, CN=R11'],
			'validity' => 365,
			'contact' => 'example.com',
			'networks' => ['eduroam'],
		],
		'student.example.com' => [
			'display_name' => 'Student',
			'description' => 'Network for students',
			'server_names' => ['radius.example.com'],
			'signer' => 'CN=example.com Let\'s Wi-Fi CA',
			'trust' => ['C=US, O=Let\'s Encrypt, CN=R11'],
			'validity' => 365,
			'contact' => 'example.com',
			'networks' => ['eduroam'],
		],
		'example.com' => [
			'display_name' => 'Example',
			'description' => 'The example realm',
			'server_names' => ['radius.example.com'],
			'signer' => 'CN=example.com Let\'s Wi-Fi CA',
			'trust' => ['C=US, O=Let\'s Encrypt, CN=R11'],
			'validity' => 365,
			'contact' => 'example.com',
			'networks' => ['eduroam'],
		],
	],
	'certificate' => [
		'CN=example.com Let\'s Wi-Fi CA' => [
			'x509' => '-----BEGIN CERTIFICATE-----
MIIB2TCCAX+gAwIBAgIIECklhCRm2HEwCgYIKoZIzj0EAwIwJTEjMCEGA1UEAwwa
ZXhhbXBsZS5jb20gTGV0J3MgV2ktRmkgQ0EwIBcNMjMwMjEzMTIyNDAwWhgPMjA3
MzAxMzExMjI0MDBaMCUxIzAhBgNVBAMMGmV4YW1wbGUuY29tIExldCdzIFdpLUZp
IENBMFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEtlsu05kmTP2NkvwsCfXCdvF2
jOO2PenAQzvzONEIPl2cHbzF1wfCfPrXtFDPQzzGJFEc2MUzv0ABcXKdq96exqOB
ljCBkzAdBgNVHQ4EFgQUSec9SZXUnMRKJGZxg560kLRuvzIwVAYDVR0jBE0wS4AU
Sec9SZXUnMRKJGZxg560kLRuvzKhKaQnMCUxIzAhBgNVBAMMGmV4YW1wbGUuY29t
IExldCdzIFdpLUZpIENBgggQKSWEJGbYcTAPBgNVHRMBAf8EBTADAQH/MAsGA1Ud
DwQEAwIBhjAKBggqhkjOPQQDAgNIADBFAiBqyg1tvjv8FlEeA8n70aMZ42Lfqctl
nLMxCOn18PVWtQIhAIC9Ui6updRWRrS2WbMLo/gMNo574xk6lYuzjebKPMDd
-----END CERTIFICATE-----
',
			'key' => '-----BEGIN EC PRIVATE KEY-----
MHcCAQEEIE5FK2GdNHz7yHAXdH+aL5N6qwa3WJ98y6zEzFHByD7QoAoGCCqGSM49
AwEHoUQDQgAEtlsu05kmTP2NkvwsCfXCdvF2jOO2PenAQzvzONEIPl2cHbzF1wfC
fPrXtFDPQzzGJFEc2MUzv0ABcXKdq96exg==
-----END EC PRIVATE KEY-----
',
		],
		'C=US, O=Let\'s Encrypt, CN=R11' => [
			'x509' => '-----BEGIN CERTIFICATE-----
MIIFBjCCAu6gAwIBAgIRAIp9PhPWLzDvI4a9KQdrNPgwDQYJKoZIhvcNAQELBQAw
TzELMAkGA1UEBhMCVVMxKTAnBgNVBAoTIEludGVybmV0IFNlY3VyaXR5IFJlc2Vh
cmNoIEdyb3VwMRUwEwYDVQQDEwxJU1JHIFJvb3QgWDEwHhcNMjQwMzEzMDAwMDAw
WhcNMjcwMzEyMjM1OTU5WjAzMQswCQYDVQQGEwJVUzEWMBQGA1UEChMNTGV0J3Mg
RW5jcnlwdDEMMAoGA1UEAxMDUjExMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIB
CgKCAQEAuoe8XBsAOcvKCs3UZxD5ATylTqVhyybKUvsVAbe5KPUoHu0nsyQYOWcJ
DAjs4DqwO3cOvfPlOVRBDE6uQdaZdN5R2+97/1i9qLcT9t4x1fJyyXJqC4N0lZxG
AGQUmfOx2SLZzaiSqhwmej/+71gFewiVgdtxD4774zEJuwm+UE1fj5F2PVqdnoPy
6cRms+EGZkNIGIBloDcYmpuEMpexsr3E+BUAnSeI++JjF5ZsmydnS8TbKF5pwnnw
SVzgJFDhxLyhBax7QG0AtMJBP6dYuC/FXJuluwme8f7rsIU5/agK70XEeOtlKsLP
Xzze41xNG/cLJyuqC0J3U095ah2H2QIDAQABo4H4MIH1MA4GA1UdDwEB/wQEAwIB
hjAdBgNVHSUEFjAUBggrBgEFBQcDAgYIKwYBBQUHAwEwEgYDVR0TAQH/BAgwBgEB
/wIBADAdBgNVHQ4EFgQUxc9GpOr0w8B6bJXELbBeki8m47kwHwYDVR0jBBgwFoAU
ebRZ5nu25eQBc4AIiMgaWPbpm24wMgYIKwYBBQUHAQEEJjAkMCIGCCsGAQUFBzAC
hhZodHRwOi8veDEuaS5sZW5jci5vcmcvMBMGA1UdIAQMMAowCAYGZ4EMAQIBMCcG
A1UdHwQgMB4wHKAaoBiGFmh0dHA6Ly94MS5jLmxlbmNyLm9yZy8wDQYJKoZIhvcN
AQELBQADggIBAE7iiV0KAxyQOND1H/lxXPjDj7I3iHpvsCUf7b632IYGjukJhM1y
v4Hz/MrPU0jtvfZpQtSlET41yBOykh0FX+ou1Nj4ScOt9ZmWnO8m2OG0JAtIIE38
01S0qcYhyOE2G/93ZCkXufBL713qzXnQv5C/viOykNpKqUgxdKlEC+Hi9i2DcaR1
e9KUwQUZRhy5j/PEdEglKg3l9dtD4tuTm7kZtB8v32oOjzHTYw+7KdzdZiw/sBtn
UfhBPORNuay4pJxmY/WrhSMdzFO2q3Gu3MUBcdo27goYKjL9CTF8j/Zz55yctUoV
aneCWs/ajUX+HypkBTA+c8LGDLnWO2NKq0YD/pnARkAnYGPfUDoHR9gVSp/qRx+Z
WghiDLZsMwhN1zjtSC0uBWiugF3vTNzYIEFfaPG7Ws3jDrAMMYebQ95JQ+HIBD/R
PBuHRTBpqKlyDnkSHDHYPiNX3adPoPAcgdF3H2/W0rmoswMWgTlLn1Wu0mrks7/q
pdWfS6PJ1jty80r2VKsM/Dj3YIDfbjXKdaFU5C+8bhfJGqU3taKauuz0wHVGT3eo
6FlWkWYtbt4pgdamlwVeZEW+LM7qZEJEsMNPrfC03APKmZsJgpWCDWOKZvkZcvjV
uYkQ4omYCTX5ohy+knMjdOmdH9c7SpqEWBDC86fiNex+O0XOMEZSa8DA
-----END CERTIFICATE-----',
			'issuer' => 'C=US, O=Internet Security Research Group, CN=ISRG Root X1',
		],
		'C=US, O=Internet Security Research Group, CN=ISRG Root X1' => [
			'x509' => '-----BEGIN CERTIFICATE-----
MIIFazCCA1OgAwIBAgIRAIIQz7DSQONZRGPgu2OCiwAwDQYJKoZIhvcNAQELBQAw
TzELMAkGA1UEBhMCVVMxKTAnBgNVBAoTIEludGVybmV0IFNlY3VyaXR5IFJlc2Vh
cmNoIEdyb3VwMRUwEwYDVQQDEwxJU1JHIFJvb3QgWDEwHhcNMTUwNjA0MTEwNDM4
WhcNMzUwNjA0MTEwNDM4WjBPMQswCQYDVQQGEwJVUzEpMCcGA1UEChMgSW50ZXJu
ZXQgU2VjdXJpdHkgUmVzZWFyY2ggR3JvdXAxFTATBgNVBAMTDElTUkcgUm9vdCBY
MTCCAiIwDQYJKoZIhvcNAQEBBQADggIPADCCAgoCggIBAK3oJHP0FDfzm54rVygc
h77ct984kIxuPOZXoHj3dcKi/vVqbvYATyjb3miGbESTtrFj/RQSa78f0uoxmyF+
0TM8ukj13Xnfs7j/EvEhmkvBioZxaUpmZmyPfjxwv60pIgbz5MDmgK7iS4+3mX6U
A5/TR5d8mUgjU+g4rk8Kb4Mu0UlXjIB0ttov0DiNewNwIRt18jA8+o+u3dpjq+sW
T8KOEUt+zwvo/7V3LvSye0rgTBIlDHCNAymg4VMk7BPZ7hm/ELNKjD+Jo2FR3qyH
B5T0Y3HsLuJvW5iB4YlcNHlsdu87kGJ55tukmi8mxdAQ4Q7e2RCOFvu396j3x+UC
B5iPNgiV5+I3lg02dZ77DnKxHZu8A/lJBdiB3QW0KtZB6awBdpUKD9jf1b0SHzUv
KBds0pjBqAlkd25HN7rOrFleaJ1/ctaJxQZBKT5ZPt0m9STJEadao0xAH0ahmbWn
OlFuhjuefXKnEgV4We0+UXgVCwOPjdAvBbI+e0ocS3MFEvzG6uBQE3xDk3SzynTn
jh8BCNAw1FtxNrQHusEwMFxIt4I7mKZ9YIqioymCzLq9gwQbooMDQaHWBfEbwrbw
qHyGO0aoSCqI3Haadr8faqU9GY/rOPNk3sgrDQoo//fb4hVC1CLQJ13hef4Y53CI
rU7m2Ys6xt0nUW7/vGT1M0NPAgMBAAGjQjBAMA4GA1UdDwEB/wQEAwIBBjAPBgNV
HRMBAf8EBTADAQH/MB0GA1UdDgQWBBR5tFnme7bl5AFzgAiIyBpY9umbbjANBgkq
hkiG9w0BAQsFAAOCAgEAVR9YqbyyqFDQDLHYGmkgJykIrGF1XIpu+ILlaS/V9lZL
ubhzEFnTIZd+50xx+7LSYK05qAvqFyFWhfFQDlnrzuBZ6brJFe+GnY+EgPbk6ZGQ
3BebYhtF8GaV0nxvwuo77x/Py9auJ/GpsMiu/X1+mvoiBOv/2X/qkSsisRcOj/KK
NFtY2PwByVS5uCbMiogziUwthDyC3+6WVwW6LLv3xLfHTjuCvjHIInNzktHCgKQ5
ORAzI4JMPJ+GslWYHb4phowim57iaztXOoJwTdwJx4nLCgdNbOhdjsnvzqvHu7Ur
TkXWStAmzOVyyghqpZXjFaH3pO3JLF+l+/+sKAIuvtd7u+Nxe5AW0wdeRlN8NwdC
jNPElpzVmbUq4JUagEiuTDkHzsxHpFKVK7q4+63SM1N95R1NbdWhscdCb+ZAJzVc
oyi3B43njTOQ5yOf+1CceWxG1bQVs5ZufpsMljq4Ui0/1lvh+wjChP4kqKOJ2qxq
4RgqsahDYVvTH9w7jXbyLeiNdd8XM2w9U/t7y0Ff/9yi0GE44Za4rF2LN9d11TPA
mRGunUHBcnWEvgJBQl9nJEiU0Zsnvgc/ubhPgXRR4Xq37Z0j4r7g1SgEEzwxA57d
emyPxgcYxn/eR44/KJ4EBs+lVDR3veyJm+kXQ99b21/+jh5Xos1AnX5iItreGCc=
-----END CERTIFICATE-----',
		],
	],
	'network' => [
		'eduroam' => [
			'ssid' => 'eduroam',
			'display_name' => 'eduroam',
			'oids' => ['5a03ba0800'],
		],
	],
	'contact' => [
		'example.com' => [
			'mail' => 'contact@example.com',
			'web' => 'https://support.example.com',
			'phone' => 'tel:+1555eduroam',
			'location' => ['lat' => 63.4, 'lon' => 10.4],
			'logo' => ['bytes' => '', 'content_type'=>'image/empty'],
		],
	],
];
