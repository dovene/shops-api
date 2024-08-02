#DROP DATABASE IF EXISTS `u720146064_shops `;
#CREATE DATABASE u720146064_shops ;
#USE u720146064_shops ;


DROP TABLE IF EXISTS `event_item`;
DROP TABLE IF EXISTS `event`;
DROP TABLE IF EXISTS `item`;
DROP TABLE IF EXISTS `event_type`;
DROP TABLE IF EXISTS `item_category`;
DROP TABLE IF EXISTS `business_partner`;
DROP TABLE IF EXISTS `user`;
DROP TABLE IF EXISTS `company`;


CREATE TABLE `company` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `tel` varchar(45),
  `email` varchar(45) NOT NULL,
  `city` varchar(45),
  `country` varchar(45),
  `devise` varchar(45),
  `address_details` varchar(200) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `status` varchar(45) NOT NULL DEFAULT 'draft',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ;

INSERT INTO company (name, email, code) VALUES ("company","mail@mail.com","0000");

CREATE TABLE `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(45) NOT NULL,
  `name` varchar(255) NOT NULL,
  `password` varchar(100) NOT NULL,
  `role` varchar(45) NOT NULL DEFAULT 'user',
  `company_id` int NOT NULL,
  `status` varchar(45) NOT NULL DEFAULT 'enabled',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_UNIQUE` (`email`),
  FOREIGN KEY (`company_id`) REFERENCES `company` (`id`)
);

INSERT INTO user (name, email, password, company_id) VALUES ("tester","tester@mail.com","coucou10", 1);


CREATE TABLE `item_category` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `company_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_category` (`name`,`company_id`),
  FOREIGN KEY (`company_id`) REFERENCES `company` (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
); 

INSERT INTO item_category (name, user_id, company_id) VALUES ("global",1,1);


CREATE TABLE `event_type` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `is_free` tinyint NOT NULL DEFAULT '0',
  `is_an_increase_stock_type` tinyint NOT NULL DEFAULT '0',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
);

INSERT INTO event_type (name, is_an_increase_stock_type) VALUES ("ACHATS",1);
INSERT INTO event_type (name, is_an_increase_stock_type) VALUES ("VENTES",0);
INSERT INTO event_type (name, is_an_increase_stock_type) VALUES ("DEFECTUEUX",0);
INSERT INTO event_type (name, is_an_increase_stock_type) VALUES ("APPORTS GRATUITS",1);


CREATE TABLE `business_partner` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(45) DEFAULT NULL,
  `tel` varchar(45) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `city` varchar(45) DEFAULT NULL,
  `country` varchar(45) DEFAULT NULL,
  `type` varchar(45) NOT NULL DEFAULT 'CUSTOMER',
  `company_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`,`company_id`),
  FOREIGN KEY (`company_id`) REFERENCES `company` (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
);

INSERT INTO business_partner (name, user_id, company_id) VALUES ("madi bar",1,1);
INSERT INTO business_partner (name, user_id, company_id) VALUES ("koko star",1,1);

CREATE TABLE `item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reference` varchar(45) NOT NULL,
  `name` varchar(100) NOT NULL,
  `picture` varchar(300) DEFAULT NULL,
  `sell_price` double DEFAULT '0',
  `buy_price` double DEFAULT '0',
  `quantity` int DEFAULT '0',
  `item_category_id` int NOT NULL,
  `company_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`,`company_id`),
  UNIQUE KEY `reference_UNIQUE` (`reference`,`company_id`),
  FOREIGN KEY (`company_id`) REFERENCES `company` (`id`),
  FOREIGN KEY (`item_category_id`) REFERENCES `item_category` (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
);


CREATE TABLE `event` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `tva` double DEFAULT NULL,
  `event_type_id` int NOT NULL,
  `business_partner_id` int NOT NULL,
  `company_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`business_partner_id`) REFERENCES `business_partner` (`id`),
  FOREIGN KEY (`company_id`) REFERENCES `company` (`id`),
  FOREIGN KEY (`event_type_id`) REFERENCES `event_type` (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ;


CREATE TABLE `event_item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quantity` int NOT NULL,
  `price` double DEFAULT NULL,
  `event_id` int NOT NULL,
  `item_id` int NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`event_id`) REFERENCES `event` (`id`),
  FOREIGN KEY (`item_id`) REFERENCES `item` (`id`)
) ;
