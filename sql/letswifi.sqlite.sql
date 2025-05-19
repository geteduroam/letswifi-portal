
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

CREATE TABLE IF NOT EXISTS "realm_signing_log" (
	"serial" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
	"realm" TEXT REFERENCES "realm"("realm") NOT NULL,
	"ca_sub" TEXT REFERENCES "ca"("sub") NOT NULL,
	"requester" TEXT NOT NULL,
	"sub" TEXT NOT NULL,
	"grant" TEXT DEFAULT NULL,
	"identity" TEXT NOT NULL,
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
