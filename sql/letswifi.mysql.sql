
CREATE TABLE `realm_signing_log` (
		`serial` int(11) unsigned NOT NULL AUTO_INCREMENT,
		`realm` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		`ca_sub` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		`requester` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		`sub` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		`grant` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
		`ident` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
		`issued` datetime NOT NULL,
		`expires` datetime NOT NULL,
		`csr` blob NOT NULL,
		`x509` blob DEFAULT NULL,
		`revoked` datetime DEFAULT NULL,
		`usage` enum('client','server') COLLATE utf8mb4_unicode_ci NOT NULL,
		`client` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
		`user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
		`ip` varchar(39) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
		PRIMARY KEY (`serial`)
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
