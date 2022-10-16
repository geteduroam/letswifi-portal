crl.pem: index.txt
	openssl ca -config openssl.cnf -gencrl -out crl.pem

index.txt: crlrc update-index.sh
	sh -x update-index.sh

serial: index.txt
	printf %s "$$(expr "$$(tail -n1 index.txt | cut -f4)" + 1)" > serial.tmp
	test $$(wc -c <serial.tmp) = 1 && printf 0%s "$$(cat serial.tmp)" > serial || mv serial.tmp serial
	@rm -f serial.tmp

crlrc:
	cp crlrc.dist crlrc

clean:
	rm crl.pem index.txt
.PHONY: clean

ocsp-key.pem:
	echo 'Copy ocsp-key.pem in from another location'
	-false
ocsp-cert.pem:
	echo 'Copy ocsp-cert.pem in from another location'
	-false
run-ocsp: ocsp-key.pem ocsp-cert.pem index.txt
	openssl ocsp -port 127.0.0.1:2560 -text -sha256 \
		-index index.txt \
		-CA ca.pem \
		-rkey ocsp-key.pem \
		-rsigner ocsp-cert.pem
.PHONY: run-ocsp
