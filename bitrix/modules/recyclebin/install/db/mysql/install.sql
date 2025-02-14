CREATE TABLE `b_recyclebin`
(
	`ID`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`NAME`        VARCHAR(255) NULL,
	`SITE_ID`     CHAR(2)      NULL,
	`ENTITY_ID`   VARCHAR(64)  NOT NULL,
	`MODULE_ID`   VARCHAR(64)  NOT NULL,
	`ENTITY_TYPE` VARCHAR(64)  NOT NULL,
	`TIMESTAMP`   DATETIME     NOT NULL,
	`USER_ID`     INT UNSIGNED NULL,
	PRIMARY KEY (`ID`),
	KEY `IX_RECYCLEBIN_USER_ID` (`USER_ID`),
	KEY `IX_RECYCLEBIN_MODULE_ID` (`MODULE_ID`),
	KEY `IX_RECYCLEBIN_ENTITY_TYPE` (`ENTITY_TYPE`),
	KEY `IX_RECYCLEBIN_TIMESTAMP` (`TIMESTAMP`)
);

CREATE TABLE `b_recyclebin_data`
(
	`ID`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`RECYCLEBIN_ID` INT UNSIGNED NOT NULL,
	`ACTION`        VARCHAR(64)  NOT NULL,
	`DATA`          MEDIUMTEXT   NOT NULL,
	PRIMARY KEY (`ID`),
	KEY `IX_RECYCLEBIN_RECYCLEBIN_ID` (`RECYCLEBIN_ID`)
);

CREATE TABLE `b_recyclebin_files`
(
	`ID`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`RECYCLEBIN_ID` INT UNSIGNED NOT NULL,
	`FILE_ID`       INT UNSIGNED NOT NULL,
	`STORAGE_TYPE`  VARCHAR(64)  NULL,
	PRIMARY KEY (`ID`),
	KEY `IX_RECYCLEBIN_FILES_RECYCLEBIN_ID` (`RECYCLEBIN_ID`),
	KEY `IX_RECYCLEBIN_FILES_FILE_ID` (`FILE_ID`)
);