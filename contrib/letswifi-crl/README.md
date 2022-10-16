# Let's Wifi-CRL

Script for generating a CRL file or OCSP server to be used together with
a RADIUS server, to check whether a client certificate was revoked.

Use together with `/www/admin/ca-index/`


# Testing

Assume we want to test the file `testcert.pem` in the current directory.

## CRL

	cat ca.pem crl.pem >chain.pem
	openssl verify -CAfile chain.pem -crl_check testcert.pem

## OCSP

On one terminal

	make start-ocsp

In another terminal

	openssl ocsp -sha256 -CAfile ca.pem -url http://127.0.0.1:2560 -resp_text -issuer ca.pem -cert testcert.pem
