#DROP DATABASE IF EXISTS `u720146064_shops `;
#CREATE DATABASE u720146064_shops ;
#USE u720146064_shops ;


DROP TABLE IF EXISTS `payment`;
DROP TABLE IF EXISTS `event_item`;
DROP TABLE IF EXISTS `event`;
DROP TABLE IF EXISTS `item`;
DROP TABLE IF EXISTS `event_type`;
DROP TABLE IF EXISTS `item_category`;
DROP TABLE IF EXISTS `business_partner`;
DROP TABLE IF EXISTS `user`;
DROP TABLE IF EXISTS `payment_type`;
DROP TABLE IF EXISTS `subscription`;
DROP TABLE IF EXISTS `app_minimal_version`;
DROP TABLE IF EXISTS `company`;

CREATE TABLE `company` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `tel` varchar(45),
  `email` varchar(45) NOT NULL,
  `city` varchar(45),
  `country` varchar(45),
  `devise` varchar(45),
  `address_details` varchar(200) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'DRAFT',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ;


CREATE TABLE `user` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `email` varchar(45) NOT NULL,
  `name` varchar(255) NOT NULL,
  `password` varchar(100) NOT NULL,
  `role` varchar(45) NOT NULL DEFAULT 'user',
  `company_id` BIGINT NOT NULL,
  `status`  varchar(50) NOT NULL DEFAULT 'ENABLED',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_UNIQUE` (`email`),
  FOREIGN KEY (`company_id`) REFERENCES `company` (`id`)
);



CREATE TABLE `item_category` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `company_id` BIGINT NOT NULL,
  `user_id` BIGINT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_category` (`name`,`company_id`),
  FOREIGN KEY (`company_id`) REFERENCES `company` (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
); 



CREATE TABLE `event_type` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `is_free` tinyint NOT NULL DEFAULT '0',
  `is_an_increase_stock_type` tinyint NOT NULL DEFAULT '0',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
);

INSERT INTO event_type (name, is_an_increase_stock_type, is_free) VALUES ("VENTES",0, 0);
INSERT INTO event_type (name, is_an_increase_stock_type, is_free) VALUES ("ACHATS",1, 0);
INSERT INTO event_type (name, is_an_increase_stock_type, is_free) VALUES ("DEFECTUEUX",0, 1);
INSERT INTO event_type (name, is_an_increase_stock_type, is_free) VALUES ("APPORTS GRATUITS",1, 1);
INSERT INTO event_type (name, is_an_increase_stock_type, is_free) VALUES ("DEVIS",2, 1);

CREATE TABLE `business_partner` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(45) DEFAULT NULL,
  `tel` varchar(45) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `city` varchar(45) DEFAULT NULL,
  `country` varchar(45) DEFAULT NULL,
  `type` ENUM('CLIENT', 'FOURNISSEUR', 'GENERAL') NOT NULL DEFAULT 'GENERAL',
  `company_id` BIGINT NOT NULL,
  `user_id` BIGINT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`,`company_id`),
  FOREIGN KEY (`company_id`) REFERENCES `company` (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
);


CREATE TABLE `item` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `reference` varchar(45) NOT NULL,
  `name` varchar(100) NOT NULL,
  `picture` varchar(300) DEFAULT NULL,
  `sell_price` double DEFAULT '0',
  `buy_price` double DEFAULT '0',
  `quantity` int DEFAULT '0',
  `item_category_id` BIGINT NOT NULL,
  `company_id` BIGINT NOT NULL,
  `user_id` BIGINT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`,`company_id`),
  UNIQUE KEY `reference_UNIQUE` (`reference`,`company_id`),
  FOREIGN KEY (`company_id`) REFERENCES `company` (`id`),
  FOREIGN KEY (`item_category_id`) REFERENCES `item_category` (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
);


CREATE TABLE `event` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `event_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `tva` double DEFAULT NULL,
  `total_price` double DEFAULT NULL,
  `total_quantity` double DEFAULT NULL,
  `discount` double DEFAULT NULL,
  `event_type_id` BIGINT NOT NULL,
  `business_partner_id` BIGINT NOT NULL,
  `company_id` BIGINT NOT NULL,
  `user_id` BIGINT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
   `status` varchar(50) NOT NULL DEFAULT 'VALIDATED',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`business_partner_id`) REFERENCES `business_partner` (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `company` (`id`),
  FOREIGN KEY (`event_type_id`) REFERENCES `event_type` (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ;


CREATE TABLE `event_item` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `quantity` int NOT NULL,
  `price` double DEFAULT NULL,
  `event_id` BIGINT NOT NULL,
  `item_id` BIGINT NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`event_id`) REFERENCES `event` (`id`),
  FOREIGN KEY (`item_id`) REFERENCES `item` (`id`)
) ;


CREATE TABLE `payment_type` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
);

INSERT INTO payment_type (name) VALUES ("ESPECES");
INSERT INTO payment_type (name) VALUES ("VIREMENT");
INSERT INTO payment_type (name) VALUES ("MOBILE MONEY");
INSERT INTO payment_type (name) VALUES ("CB");
INSERT INTO payment_type (name) VALUES ("TMONEY");
INSERT INTO payment_type (name) VALUES ("FLOOZ");

CREATE TABLE `payment` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `amount` double DEFAULT NULL,
  `payment_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `event_id` BIGINT NOT NULL,
  `payment_type_id` BIGINT NOT NULL,
  `user_id` BIGINT NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`event_id`) REFERENCES `event` (`id`),
  FOREIGN KEY (`payment_type_id`) REFERENCES `payment_type` (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ;

ALTER TABLE event
ADD COLUMN total_payment DOUBLE DEFAULT NULL;


CREATE TABLE `subscription` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `debut` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `end` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `company_id` BIGINT NOT NULL,
  `type`  varchar(50) NOT NULL DEFAULT 'standard',
  `status`  varchar(50) NOT NULL DEFAULT 'enabled',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `company` (`id`)
);

ALTER TABLE company
ADD COLUMN currency varchar(25) DEFAULT NULL;

ALTER TABLE company
DROP COLUMN devise;

CREATE TABLE `app_minimal_version` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `app_id` varchar(50) DEFAULT NULL,
  `app_version` varchar(50) DEFAULT NULL,
  `app_os` varchar(50) DEFAULT NULL,
  `app_name` varchar(50) DEFAULT NULL,
  `is_minimal_version_mandatory` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
); 

INSERT INTO `app_minimal_version` (`id`, `app_id`, `app_version`, `app_os`, `app_name`, `is_minimal_version_mandatory`) VALUES
(1, 'com.dov.shopique', '0.0.0', 'android', 'Shopiques', 0),
(2, 'com.dov.shopique', '0.0.0', 'ios', 'Shopiques', 0);

ALTER TABLE item
ADD COLUMN requires_stock_management tinyint(1) NOT NULL DEFAULT 1;

ALTER TABLE item
ADD COLUMN initial_quantity int DEFAULT 0;

ALTER TABLE `event`
ADD COLUMN title varchar(300) DEFAULT NULL;

ALTER TABLE company
ADD COLUMN can_default_users_create_items tinyint(1) NOT NULL DEFAULT 1;

ALTER TABLE company
ADD COLUMN can_default_users_cancel_events tinyint(1) NOT NULL DEFAULT 1;

ALTER TABLE user
ADD COLUMN reset_token varchar(100) DEFAULT NULL;

ALTER TABLE user
ADD COLUMN reset_token_expiry DATETIME DEFAULT NULL;

INSERT INTO event_type (name, is_an_increase_stock_type, is_free) VALUES ("BON DE COMMANDE",2, 1);

ALTER TABLE company
ADD COLUMN terms_and_conditions varchar(1200) DEFAULT NULL;

ALTER TABLE company
ADD COLUMN should_display_terms tinyint(1) NOT NULL DEFAULT 1;