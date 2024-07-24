#DROP DATABASE IF EXISTS `u720146064_shops `;
#CREATE DATABASE u720146064_shops ;
USE u720146064_shops ;

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


DROP TABLE IF EXISTS `user`;
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