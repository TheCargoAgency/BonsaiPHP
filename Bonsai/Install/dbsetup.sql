# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- content
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `content`;

CREATE TABLE `content`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `contentRegistryID` INTEGER NOT NULL,
    `content` LONGTEXT,
    `localeID` INTEGER NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `UNIQUE_CONTENTREFLOCALE` (`contentRegistryID`, `localeID`),
    INDEX `INDEX_CONTENTREF` (`contentRegistryID`),
    INDEX `INDEX_LOCALE` (`localeID`),
    CONSTRAINT `content_fk_7a9c06`
        FOREIGN KEY (`contentRegistryID`)
        REFERENCES `contentRegistry` (`id`),
    CONSTRAINT `content_fk_5579a0`
        FOREIGN KEY (`localeID`)
        REFERENCES `locale` (`id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- contentRegistry
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `contentRegistry`;

CREATE TABLE `contentRegistry`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `reference` VARCHAR(255) NOT NULL,
    `contentTypeID` INTEGER NOT NULL,
    `dataFormat` VARCHAR(255),
    `contentCategoryID` INTEGER NOT NULL,
    `startDate` DATETIME DEFAULT '2014-01-01',
    `endDate` DATETIME DEFAULT '2099-12-31',
    `active` BOOL DEFAULT true,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `reference` (`reference`),
    INDEX `INDEX_CategoryID` (`contentCategoryID`),
    INDEX `contentRegistry_fi_5cb791` (`contentTypeID`),
    CONSTRAINT `contentRegistry_fk_5cb791`
        FOREIGN KEY (`contentTypeID`)
        REFERENCES `contentType` (`id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- contentType
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `contentType`;

CREATE TABLE `contentType`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- contentCategory
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `contentCategory`;

CREATE TABLE `contentCategory`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- locale
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `locale`;

CREATE TABLE `locale`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `code` VARCHAR(255) NOT NULL,
    `sort` INTEGER NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `code` (`code`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- node
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `node`;

CREATE TABLE `node`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `reference` VARCHAR(255),
    `contentID` INTEGER,
    `template` VARCHAR(45),
    `renderdata` TEXT,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `REFERENCE_UNIQUE` (`reference`),
    INDEX `node_fi_8b39b8` (`contentID`),
    CONSTRAINT `node_fk_8b39b8`
        FOREIGN KEY (`contentID`)
        REFERENCES `contentRegistry` (`id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- nodeToNode
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `nodeToNode`;

CREATE TABLE `nodeToNode`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `parent` INTEGER,
    `child` INTEGER,
    `sort` INTEGER,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `PARENT_CHILD` (`parent`, `child`),
    INDEX `PARENT_KEY` (`parent`),
    INDEX `nodeToNode_fi_fe5e49` (`child`),
    CONSTRAINT `nodeToNode_fk_1c85f2`
        FOREIGN KEY (`parent`)
        REFERENCES `node` (`id`),
    CONSTRAINT `nodeToNode_fk_fe5e49`
        FOREIGN KEY (`child`)
        REFERENCES `node` (`id`)
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO `locale` (`title`, `code`, `sort`) VALUES ('Default', 'no-ne', '0');
UPDATE `locale` SET `id`='0' WHERE `id`='1';
ALTER TABLE `locale` AUTO_INCREMENT = 1;

INSERT INTO `contentType` (`name`) VALUES ( 'BonsaiNode' );
INSERT INTO `contentType` (`name`) VALUES ( 'Vocab' );

INSERT INTO `contentRegistry` (`contentTypeID`, `contentCategoryID`, `dataFormat`, `reference`, `startDate`, `endDate`, `active`) VALUES ( 1,0,null,'parent','2014-01-01 00:00:00','2099-12-31 00:00:00', true );
UPDATE `contentRegistry` SET `id`='0' WHERE `id`='1';
ALTER TABLE `contentRegistry` AUTO_INCREMENT = 1;

INSERT INTO `content` (`contentRegistryID`, `content`, `localeID`) VALUES ( 0,'Content Not Found',0);

INSERT INTO `node` (`reference`, `contentID`, `template`, `renderdata`) VALUES ('index', '0', 'page', '{ }');
