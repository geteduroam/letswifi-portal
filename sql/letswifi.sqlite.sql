
CREATE TABLE IF NOT EXISTS "realm_signer" (
		"realm" TEXT NOT NULL PRIMARY KEY REFERENCES "realm"("realm"),
		"signer_ca_sub" NOT NULL REFERENCES "ca"("sub"),
		"default_validity_days" INTEGER NOT NULL
	);

CREATE TABLE IF NOT EXISTS "ca" (
		"sub" TEXT NOT NULL PRIMARY KEY,
		"pub" BLOB NOT NULL,
		"key" BLOB,
		"issuer" TEXT REFERENCES ""("sub")
	);

CREATE TABLE IF NOT EXISTS "realm" (
		"realm" TEXT NOT NULL PRIMARY KEY
	);

CREATE TABLE IF NOT EXISTS "realm_server_name" (
		"realm" TEXT REFERENCES "realm"("realm") NOT NULL,
		"server_name" TEXT NOT NULL
	);

CREATE TABLE IF NOT EXISTS "realm_trust" (
		"realm" TEXT REFERENCES "realm"("realm") NOT NULL,
		"trusted_ca_sub" TEXT REFERENCES "ca"("sub") NOT NULL
	);

CREATE TABLE IF NOT EXISTS "oauth_grant" (
		"sid" TEXT NOT NULL PRIMARY KEY,
		"grant_data" TEXT NOT NULL,
		"sub" TEXT NOT NULL,
		"exp" INTEGER NOT NULL
	);

CREATE TABLE IF NOT EXISTS "oauth_token" (
		"token" TEXT NOT NULL PRIMARY KEY,
		"sid" TEXT NOT NULL REFERENCES "grant_data"("sid"),
		"used" INTEGER,
		"exp" INTEGER NOT NULL,
		"type" TEXT NOT NULL
	);

CREATE TABLE IF NOT EXISTS "realm_key" (
		"realm" TEXT NOT NULL PRIMARY KEY REFERENCES "realm"("realm"),
		"key" BLOB NOT NULL,
		"issued" INTEGER NOT NULL,
		"expires" INTEGER
	);

CREATE TABLE "realm_vhost" (
		"http_host" TEXT NOT NULL PRIMARY KEY,
		"realm" TEXT NOT NULL REFERENCES "realm"("realm")
	);

CREATE TABLE IF NOT EXISTS "realm_signing_log" (
		"serial" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
		"realm" TEXT REFERENCES "realm"("realm") NOT NULL,
		"ca_sub" TEXT REFERENCES "ca"("sub") NOT NULL,
		"requester" TEXT NOT NULL,
		"sub" TEXT NOT NULL,
		"issued" TEXT NOT NULL,
		"expires" TEXT NOT NULL,
		"csr" TEXT NOT NULL,
		"x509" TEXT,
		"revoked" TEXT,
		"usage" TEXT NOT NULL,
		"client" TEXT,
		"user_agent" TEXT,
		"ip" TEXT
	);
