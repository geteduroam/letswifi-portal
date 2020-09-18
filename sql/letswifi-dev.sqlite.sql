
CREATE TABLE IF NOT EXISTS "realm_signer" (
		"realm" TEXT PRIMARY KEY REFERENCES "realm"("realm"),
		"signer_ca_sub" NOT NULL REFERENCES "ca"("sub")
	);

CREATE TABLE IF NOT EXISTS "ca" (
		"sub" TEXT PRIMARY KEY,
		"pub" BLOB NOT NULL,
		"key" BLOB,
		"issuer" TEXT REFERENCES ""("sub")
	);

CREATE TABLE IF NOT EXISTS "realm" (
		"realm" TEXT PRIMARY KEY
	);

CREATE TABLE IF NOT EXISTS "realm_server" (
		"realm" TEXT REFERENCES "realm"("realm") NOT NULL,
		"server_name" TEXT NOT NULL
	);

CREATE TABLE IF NOT EXISTS "realm_trust" (
		"realm" TEXT REFERENCES "realm"("realm") NOT NULL,
		"trusted_ca_sub" TEXT REFERENCES "ca"("sub") NOT NULL
	);

CREATE TABLE IF NOT EXISTS "oauth_grant" (
		"sid" TEXT PRIMARY KEY,
		"grant_data" TEXT NOT NULL,
		"sub" TEXT NOT NULL,
		"exp" INTEGER NOT NULL
	);

CREATE TABLE IF NOT EXISTS "oauth_token" (
		"token" TEXT PRIMARY KEY,
		"sid" TEXT NOT NULL REFERENCES "grant_data"("sid"),
		"used" INTEGER,
		"exp" INTEGER NOT NULL,
		"type" TEXT NOT NULL
	);

CREATE TABLE IF NOT EXISTS "realm_key" (
		"realm" TEXT PRIMARY KEY REFERENCES "realm"("realm"),
		"key" BLOB NOT NULL,
		"issued" INTEGER NOT NULL,
		"expires" INTEGER
	);

CREATE TABLE IF NOT EXISTS "realm_signing_log" (
		"serial" INTEGER PRIMARY KEY AUTOINCREMENT,
		"realm" TEXT REFERENCES "realm"("realm") NOT NULL,
		"ca_sub" TEXT REFERENCES "ca"("sub") NOT NULL,
		"requester" TEXT NOT NULL,
		"sub" TEXT NOT NULL,
		"issued" TEXT NOT NULL,
		"expires" TEXT NOT NULL,
		"csr" TEXT NOT NULL,
		"x509" TEXT,
		"revoked" TEXT,
		"usage" TEXT NOT NULL
	);

