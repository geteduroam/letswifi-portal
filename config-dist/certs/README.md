# Certificate files

This directory contains all certificates used by the application.
Certificates can contain a private key, which allows using them as a singing CA,
or for signing apple-mobileconfig configuration profiles.

Every file must be named after it's subject and end with `.pem`.
This is necessary to form certificate chains.

Every file contains a certificate in PEM format,
starting with `-----BEGIN CERTIFICATE-----`.
If the file also contains the corresponding private key,
it can be placed either before or after the certificate.
It's recommended to put the key after the certificate.
Do not put the chain in the .pem file.

In order to use these files, make sure that the following is set in **tenant.conf.php**:

```php
// Location where to store certificates.
'certificate#pemdir' => 'certs/',
```
