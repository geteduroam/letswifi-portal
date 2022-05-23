CREATE TABLE `realm` (
		`realm` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		PRIMARY KEY (`realm`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ca` (
		`sub` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		`pub` text COLLATE utf8mb4_unicode_ci NOT NULL,
		`key` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
		`issuer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
		PRIMARY KEY (`sub`),
		FOREIGN KEY(issuer) REFERENCES ca(sub)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `realm_signer` (
		`realm` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		`signer_ca_sub` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		`default_validity_days` int(11) NOT NULL,
		PRIMARY KEY (`realm`),
		FOREIGN KEY(realm) REFERENCES realm(realm),
		FOREIGN KEY(signer_ca_sub) REFERENCES ca(sub)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `realm_server_name` (
		`realm` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		`server_name` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		FOREIGN KEY(realm) REFERENCES realm(realm)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `realm_trust` (
		`realm` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		`trusted_ca_sub` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		FOREIGN KEY(realm) REFERENCES realm(realm),
		FOREIGN KEY(trusted_ca_sub) REFERENCES ca(sub)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `realm_key` (
		`realm` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		`key` blob NOT NULL,
		`issued` int(11) NOT NULL,
		`expires` int(11) DEFAULT NULL,
		PRIMARY KEY (`realm`),
		FOREIGN KEY(realm) REFERENCES realm(realm)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `realm_signing_log` (
		`serial` int(11) unsigned NOT NULL AUTO_INCREMENT,
		`realm` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		`ca_sub` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		`requester` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		`sub` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		`issued` datetime NOT NULL,
		`expires` datetime NOT NULL,
		`csr` blob NOT NULL,
		`x509` blob DEFAULT NULL,
		`revoked` datetime DEFAULT NULL,
		`usage` enum('client','server') COLLATE utf8mb4_unicode_ci NOT NULL,
		`client` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
		`user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
		`ip` varchar(39) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
		PRIMARY KEY (`serial`),
		FOREIGN KEY(realm) REFERENCES realm(realm),
		FOREIGN KEY(ca_sub) REFERENCES ca(sub)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `realm_vhost` (
		`http_host` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		`realm` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		PRIMARY KEY (`http_host`),
		FOREIGN KEY(realm) REFERENCES realm(realm)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `oauth_grant` (
		`sid` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		`grant_data` text COLLATE utf8mb4_unicode_ci NOT NULL,
		`sub` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		`exp` int(11) NOT NULL,
		PRIMARY KEY (`sid`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `oauth_token` (
		`token` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		`sid` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		`used` int(11) DEFAULT NULL,
		`exp` int(11) NOT NULL,
		`type` ENUM('authorization_code', 'access_token', 'refresh_token'),
		PRIMARY KEY (`token`),
		FOREIGN KEY(sid) REFERENCES oauth_grant(sid)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
