-- MySQL dump 10.9
--
-- Host: localhost    Database: bedstu
-- ------------------------------------------------------
-- Server version	4.1.12-standard

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- DELETE FROM cm_sizes_seq;
-- 
-- DELETE FROM cm_sizes;
-- 
-- DELETE FROM cm_shipmethods_zone_zones_seq;
-- 
-- DELETE FROM cm_shipmethods_zone_zones;
-- 
-- DELETE FROM cm_shipmethods_zone_methods_seq;
-- 
-- DELETE FROM cm_shipmethods_zone_methods;
-- 
-- DELETE FROM cm_shipmethods_zone_locales;
-- 
-- DELETE FROM cm_shipmethods_zone_costs_seq;
-- 
-- DELETE FROM cm_shipmethods_zone_costs;
-- 
-- DELETE FROM cm_shipmethods_flat_methods;
-- 
-- DELETE FROM cm_ship_class_seq;
-- 
-- DELETE FROM cm_ship_class;
-- 
-- DELETE FROM cm_products_seq;
-- 
-- DELETE FROM cm_products_relations;
-- 
-- DELETE FROM cm_products_options_seq;
-- 
-- DELETE FROM cm_products_options;
-- 
-- DELETE FROM cm_products_colorways;
-- 
-- DELETE FROM cm_products_categories;
-- 
-- DELETE FROM cm_products;
-- 
-- DELETE FROM cm_product_images_seq;
-- 
-- DELETE FROM cm_product_images;
-- 
-- DELETE FROM cm_paymentcc_seq;
-- 
-- DELETE FROM cm_paymentcc;
-- 
-- DELETE FROM cm_orders_seq;
-- 
-- DELETE FROM cm_orders;
-- 
-- DELETE FROM cm_order_transactions_seq;
-- 
-- DELETE FROM cm_order_transactions;
-- 
-- DELETE FROM cm_order_items_seq;
-- 
-- DELETE FROM cm_order_items_options;
-- 
-- DELETE FROM cm_order_items;
-- 
-- DELETE FROM cm_order_history_seq;
-- 
-- DELETE FROM cm_order_history;
-- 
-- DELETE FROM cm_manufacturers_seq;
-- 
-- DELETE FROM cm_manufacturers;
-- 
-- DELETE FROM cm_inventory_seq;
-- 
-- DELETE FROM cm_inventory;
-- 
-- DELETE FROM cm_giftcards_seq;
-- 
-- DELETE FROM cm_giftcards;
-- 
-- DELETE FROM cm_coupons;
-- 
-- DELETE FROM cm_colorways_seq;
-- 
-- DELETE FROM cm_colorways;
-- 
-- DELETE FROM cm_categories_seq;
-- 
-- DELETE FROM cm_categories;
-- 
-- DELETE FROM cm_cart_seq;
-- 
-- DELETE FROM cm_cart_items_seq;
-- 
-- DELETE FROM cm_cart_items_options;
-- 
-- DELETE FROM cm_cart_items;
-- 
-- DELETE FROM cm_cart_extra_totals;
-- 
-- DELETE FROM cm_cart;
-- 
-- DELETE FROM cm_address_book_seq;
-- 
-- DELETE FROM cm_address_book;
-- 
-- DELETE FROM auth_user_seq;
-- 
-- DELETE FROM auth_user;

--
-- Dumping data for table `auth_user`
--

/*!40000 ALTER TABLE `auth_user` DISABLE KEYS */;
LOCK TABLES `auth_user` WRITE;
INSERT INTO `auth_user` VALUES (2,'','','Test Co','testbob@spambob.com','testuser1','wfcHf6zHOiKuQ',NULL,4,3,'234 334 2232',NULL,NULL,NULL,'test user',NULL,NULL);
UNLOCK TABLES;
/*!40000 ALTER TABLE `auth_user` ENABLE KEYS */;

--
-- Dumping data for table `auth_user_seq`
--


/*!40000 ALTER TABLE `auth_user_seq` DISABLE KEYS */;
LOCK TABLES `auth_user_seq` WRITE;
INSERT INTO `auth_user_seq` VALUES (2);
UNLOCK TABLES;
/*!40000 ALTER TABLE `auth_user_seq` ENABLE KEYS */;

--
-- Dumping data for table `cem_emailoptout`
--

--
-- Dumping data for table `cm_address_book`
--


/*!40000 ALTER TABLE `cm_address_book` DISABLE KEYS */;
LOCK TABLES `cm_address_book` WRITE;
INSERT INTO `cm_address_book` VALUES (1,2,'','Test Co','','','',NULL,'','',0,NULL,'','',NULL,NULL),(2,2,'test user','Test Co','123 Example Ave','','Bev Hil','CA','90210','US',0,NULL,'','',NULL,NULL),(3,2,'test user','Test Co','123 Example Ave','','Bev Hil','CA','90210','US',0,NULL,'','',NULL,NULL),(4,2,'test user','Test Co','123 Example Ave','','Bev Hil','CA','90210','US',0,NULL,'','',NULL,NULL);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_address_book` ENABLE KEYS */;

--
-- Dumping data for table `cm_address_book_seq`
--


/*!40000 ALTER TABLE `cm_address_book_seq` DISABLE KEYS */;
LOCK TABLES `cm_address_book_seq` WRITE;
INSERT INTO `cm_address_book_seq` VALUES (4);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_address_book_seq` ENABLE KEYS */;

--
-- Dumping data for table `cm_cart`
--


/*!40000 ALTER TABLE `cm_cart` DISABLE KEYS */;
LOCK TABLES `cm_cart` WRITE;
INSERT INTO `cm_cart` VALUES (1,1,5.75,'GROUND',0.00,'','ABC124','2007-09-18 14:56:15','2007-09-18 12:35:02',1,1),(2,2,5.75,'GROUND',0.00,'',NULL,'2007-09-18 15:04:17','2007-09-18 15:02:35',1,1);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_cart` ENABLE KEYS */;

--
-- Dumping data for table `cm_cart_extra_totals`
--


/*!40000 ALTER TABLE `cm_cart_extra_totals` DISABLE KEYS */;
LOCK TABLES `cm_cart_extra_totals` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_cart_extra_totals` ENABLE KEYS */;

--
-- Dumping data for table `cm_cart_items`
--


/*!40000 ALTER TABLE `cm_cart_items` DISABLE KEYS */;
LOCK TABLES `cm_cart_items` WRITE;
INSERT INTO `cm_cart_items` VALUES (1,1,2,1,1,45.00,0.00,'2007-09-18 14:42:06','A1-S-GRN','Test Shirt A1',NULL,0),(2,1,1,1,1,45.00,0.00,'2007-09-18 14:46:22','A1-S-BLU','Test Shirt A1',NULL,0),(3,1,3,1,1,45.00,0.00,'2007-09-18 14:51:25','A1-S-GRY','Test Shirt A1',NULL,0),(4,2,5,2,1,78.00,0.00,'2007-09-18 15:02:45','W1-S-GRY','Test Shirt W1',NULL,0),(5,2,4,1,1,45.00,0.00,'2007-09-18 15:03:27','A1-S-RD','Test Shirt A1',NULL,0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_cart_items` ENABLE KEYS */;

--
-- Dumping data for table `cm_cart_items_options`
--


/*!40000 ALTER TABLE `cm_cart_items_options` DISABLE KEYS */;
LOCK TABLES `cm_cart_items_options` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_cart_items_options` ENABLE KEYS */;

--
-- Dumping data for table `cm_cart_items_seq`
--


/*!40000 ALTER TABLE `cm_cart_items_seq` DISABLE KEYS */;
LOCK TABLES `cm_cart_items_seq` WRITE;
INSERT INTO `cm_cart_items_seq` VALUES (5);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_cart_items_seq` ENABLE KEYS */;

--
-- Dumping data for table `cm_cart_seq`
--


/*!40000 ALTER TABLE `cm_cart_seq` DISABLE KEYS */;
LOCK TABLES `cm_cart_seq` WRITE;
INSERT INTO `cm_cart_seq` VALUES (2);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_cart_seq` ENABLE KEYS */;

--
-- Dumping data for table `cm_categories`
--


/*!40000 ALTER TABLE `cm_categories` DISABLE KEYS */;
LOCK TABLES `cm_categories` WRITE;
INSERT INTO `cm_categories` VALUES (1,1,'Men\'s',0,1,1,0,0,0,10,'',NULL,NULL,NULL,NULL,NULL),(2,1,'Women\'s',0,1,1,0,0,1,20,'',NULL,NULL,NULL,NULL,NULL),(3,1,'Accessories',0,1,1,0,0,2,30,'',NULL,NULL,NULL,NULL,NULL),(4,1,'Shirts',1,1,1,1,0,0,12,'',NULL,NULL,NULL,NULL,NULL),(5,1,'Shoes',1,1,1,1,0,3,14,'',NULL,NULL,NULL,NULL,NULL),(6,1,'Shirts',1,1,1,2,0,0,22,'',NULL,NULL,NULL,NULL,NULL),(7,1,'Shoes',1,1,1,2,0,0,24,'',NULL,NULL,NULL,NULL,NULL),(8,1,'Scarves',1,1,1,2,0,0,26,'',NULL,NULL,NULL,NULL,NULL);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_categories` ENABLE KEYS */;

--
-- Dumping data for table `cm_categories_seq`
--


/*!40000 ALTER TABLE `cm_categories_seq` DISABLE KEYS */;
LOCK TABLES `cm_categories_seq` WRITE;
INSERT INTO `cm_categories_seq` VALUES (8);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_categories_seq` ENABLE KEYS */;

--
-- Dumping data for table `cm_colorways`
--


/*!40000 ALTER TABLE `cm_colorways` DISABLE KEYS */;
LOCK TABLES `cm_colorways` WRITE;
INSERT INTO `cm_colorways` VALUES (1,'Red','#ff0000','RD'),(2,'Green','#666600','GRN'),(3,'Blue','#0033cc','BLU'),(4,'Grey','#33333','GRY');
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_colorways` ENABLE KEYS */;

--
-- Dumping data for table `cm_colorways_seq`
--


/*!40000 ALTER TABLE `cm_colorways_seq` DISABLE KEYS */;
LOCK TABLES `cm_colorways_seq` WRITE;
INSERT INTO `cm_colorways_seq` VALUES (4);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_colorways_seq` ENABLE KEYS */;

--
-- Dumping data for table `cm_coupons`
--


/*!40000 ALTER TABLE `cm_coupons` DISABLE KEYS */;
LOCK TABLES `cm_coupons` WRITE;
INSERT INTO `cm_coupons` VALUES ('ABC123','25% test code',25,0.00,0,1,'','','2007-01-01 00:00:00',1,0,0),('ABC124','$10 off coup',0,10.00,0,1,'Test Bob','TestBob@spambob.com','2010-01-01 00:00:00',0,0,1);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_coupons` ENABLE KEYS */;

--
-- Dumping data for table `cm_giftcards`
--


/*!40000 ALTER TABLE `cm_giftcards` DISABLE KEYS */;
LOCK TABLES `cm_giftcards` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_giftcards` ENABLE KEYS */;

--
-- Dumping data for table `cm_giftcards_seq`
--


/*!40000 ALTER TABLE `cm_giftcards_seq` DISABLE KEYS */;
LOCK TABLES `cm_giftcards_seq` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_giftcards_seq` ENABLE KEYS */;

--
-- Dumping data for table `cm_inventory`
--


/*!40000 ALTER TABLE `cm_inventory` DISABLE KEYS */;
LOCK TABLES `cm_inventory` WRITE;
INSERT INTO `cm_inventory` VALUES (1,1,15,6,3,'A1-S-BLU',NULL),(2,1,15,6,2,'A1-S-GRN',NULL),(3,1,15,6,4,'A1-S-GRY',NULL),(4,1,15,6,1,'A1-S-RD',NULL),(5,2,24,6,4,'W1-S-GRY',NULL),(6,2,25,2,4,'W1-M-GRY',NULL),(7,2,25,3,4,'W1-L-GRY',NULL),(8,2,25,1,4,'W1-XS-GRY',NULL);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_inventory` ENABLE KEYS */;

--
-- Dumping data for table `cm_inventory_seq`
--


/*!40000 ALTER TABLE `cm_inventory_seq` DISABLE KEYS */;
LOCK TABLES `cm_inventory_seq` WRITE;
INSERT INTO `cm_inventory_seq` VALUES (8);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_inventory_seq` ENABLE KEYS */;

--
-- Dumping data for table `cm_manufacturers`
--


/*!40000 ALTER TABLE `cm_manufacturers` DISABLE KEYS */;
LOCK TABLES `cm_manufacturers` WRITE;
INSERT INTO `cm_manufacturers` VALUES (1,'DEMO COMPANY',1,NULL,'','a sample manufacturer',NULL,NULL,NULL,NULL),(2,'Another Company',1,NULL,'','another manufacturer',NULL,NULL,NULL,NULL);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_manufacturers` ENABLE KEYS */;

--
-- Dumping data for table `cm_manufacturers_seq`
--


/*!40000 ALTER TABLE `cm_manufacturers_seq` DISABLE KEYS */;
LOCK TABLES `cm_manufacturers_seq` WRITE;
INSERT INTO `cm_manufacturers_seq` VALUES (2);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_manufacturers_seq` ENABLE KEYS */;

--
-- Dumping data for table `cm_media_files`
--


--
-- Dumping data for table `cm_order_history`
--


/*!40000 ALTER TABLE `cm_order_history` DISABLE KEYS */;
LOCK TABLES `cm_order_history` WRITE;
INSERT INTO `cm_order_history` VALUES (1,5,'NEW','2007-09-18 15:04:16',1,'This is the first order. It is only for testing.');
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_order_history` ENABLE KEYS */;

--
-- Dumping data for table `cm_order_history_seq`
--


/*!40000 ALTER TABLE `cm_order_history_seq` DISABLE KEYS */;
LOCK TABLES `cm_order_history_seq` WRITE;
INSERT INTO `cm_order_history_seq` VALUES (1);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_order_history_seq` ENABLE KEYS */;

--
-- Dumping data for table `cm_order_items`
--


/*!40000 ALTER TABLE `cm_order_items` DISABLE KEYS */;
LOCK TABLES `cm_order_items` WRITE;
INSERT INTO `cm_order_items` VALUES (11,5,1,4,1,1,45.00,0.00,0.0000,'A1-S-RD','Test Shirt A1','N;','a:2:{s:4:\"Size\";s:5:\"Small\";s:5:\"Style\";s:3:\"Red\";}',0),(12,5,2,5,1,1,78.00,0.00,0.0000,'W1-S-GRY','Test Shirt W1','N;','a:2:{s:4:\"Size\";s:5:\"Small\";s:5:\"Style\";s:4:\"Grey\";}',0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_order_items` ENABLE KEYS */;

--
-- Dumping data for table `cm_order_items_options`
--


/*!40000 ALTER TABLE `cm_order_items_options` DISABLE KEYS */;
LOCK TABLES `cm_order_items_options` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_order_items_options` ENABLE KEYS */;

--
-- Dumping data for table `cm_order_items_seq`
--


/*!40000 ALTER TABLE `cm_order_items_seq` DISABLE KEYS */;
LOCK TABLES `cm_order_items_seq` WRITE;
INSERT INTO `cm_order_items_seq` VALUES (12);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_order_items_seq` ENABLE KEYS */;

--
-- Dumping data for table `cm_order_transactions`
--


/*!40000 ALTER TABLE `cm_order_transactions` DISABLE KEYS */;
LOCK TABLES `cm_order_transactions` WRITE;
INSERT INTO `cm_order_transactions` VALUES (3,5,2,'2007-09-18 15:04:16','MANUAL',':',128.75,NULL,NULL,NULL,NULL,'0','0',NULL,'0',NULL);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_order_transactions` ENABLE KEYS */;

--
-- Dumping data for table `cm_order_transactions_seq`
--


/*!40000 ALTER TABLE `cm_order_transactions_seq` DISABLE KEYS */;
LOCK TABLES `cm_order_transactions_seq` WRITE;
INSERT INTO `cm_order_transactions_seq` VALUES (3);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_order_transactions_seq` ENABLE KEYS */;

--
-- Dumping data for table `cm_orders`
--


/*!40000 ALTER TABLE `cm_orders` DISABLE KEYS */;
LOCK TABLES `cm_orders` WRITE;
INSERT INTO `cm_orders` VALUES (5,2,2,'test user','Test Co','123 Example Ave','','Bev Hil','CA','90210','US',0,NULL,NULL,NULL,NULL,'test user','Test Co','123 Example Ave','','Bev Hil','CA','90210','US',0,'CC Payment Terminal','VISA','test user','4111111111111111','0110','2007-09-18 15:04:16','2007-09-18 15:04:16',1,NULL,'USD',NULL,NULL,NULL,NULL,NULL,0.00,'',5.75,NULL,'GROUND',NULL,NULL,NULL,'128.75','128.75','aa080d2c9534516b7773cd61cad56419');
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_orders` ENABLE KEYS */;

--
-- Dumping data for table `cm_orders_seq`
--


/*!40000 ALTER TABLE `cm_orders_seq` DISABLE KEYS */;
LOCK TABLES `cm_orders_seq` WRITE;
INSERT INTO `cm_orders_seq` VALUES (990);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_orders_seq` ENABLE KEYS */;

--
-- Dumping data for table `cm_paymentcc`
--


/*!40000 ALTER TABLE `cm_paymentcc` DISABLE KEYS */;
LOCK TABLES `cm_paymentcc` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_paymentcc` ENABLE KEYS */;

--
-- Dumping data for table `cm_paymentcc_seq`
--


/*!40000 ALTER TABLE `cm_paymentcc_seq` DISABLE KEYS */;
LOCK TABLES `cm_paymentcc_seq` WRITE;
INSERT INTO `cm_paymentcc_seq` VALUES (1);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_paymentcc_seq` ENABLE KEYS */;

--
-- Dumping data for table `cm_product_images`
--


/*!40000 ALTER TABLE `cm_product_images` DISABLE KEYS */;
LOCK TABLES `cm_product_images` WRITE;
INSERT INTO `cm_product_images` VALUES (1,1,NULL,3,'/uploads/cshop','tshirt99blue.U46f002eb5135e.jpeg','width=\"400\" height=\"400\"','t_tshirt99blue.U46f002eb5135e.png','width=\"120\" height=\"120\"',NULL,'image/jpeg',1),(2,1,NULL,2,'/uploads/cshop','tshirt99gr.U46f00318dba4a.jpeg','width=\"400\" height=\"400\"','t_tshirt99gr.U46f00318dba4a.png','width=\"120\" height=\"120\"',NULL,'image/jpeg',3),(3,1,NULL,4,'/uploads/cshop','tshirt99grey.U46f0034ab8707.jpeg','width=\"400\" height=\"400\"','t_tshirt99grey.U46f0034ab8707.png','width=\"120\" height=\"120\"',NULL,'image/jpeg',6),(4,1,NULL,1,'/uploads/cshop','tshirt99rd.U46f00354ccbe2.jpeg','width=\"400\" height=\"400\"','t_tshirt99rd.U46f00354ccbe2.png','width=\"120\" height=\"120\"',NULL,'image/jpeg',9),(5,2,NULL,4,'/uploads/cshop','tshirt-1.grey.U46f00478eeda6.jpeg','width=\"480\" height=\"480\"','t_tshirt-1.grey.U46f00478eeda6.png','width=\"120\" height=\"120\"',NULL,'image/jpeg',1);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_product_images` ENABLE KEYS */;

--
-- Dumping data for table `cm_product_images_seq`
--


/*!40000 ALTER TABLE `cm_product_images_seq` DISABLE KEYS */;
LOCK TABLES `cm_product_images_seq` WRITE;
INSERT INTO `cm_product_images_seq` VALUES (5);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_product_images_seq` ENABLE KEYS */;

--
-- Dumping data for table `cm_products`
--


/*!40000 ALTER TABLE `cm_products` DISABLE KEYS */;
LOCK TABLES `cm_products` WRITE;
INSERT INTO `cm_products` VALUES (1,1,2,45.00,NULL,1.10,NULL,'Test Shirt A1',1,1,1,'A1','',NULL,14,NULL,NULL),(2,1,1,78.00,NULL,1.20,NULL,'Test Shirt W1',1,1,7,'W1','',NULL,3,NULL,NULL);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_products` ENABLE KEYS */;

--
-- Dumping data for table `cm_products_categories`
--


/*!40000 ALTER TABLE `cm_products_categories` DISABLE KEYS */;
LOCK TABLES `cm_products_categories` WRITE;
INSERT INTO `cm_products_categories` VALUES (1,1,0),(1,4,0),(2,2,0),(2,6,0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_products_categories` ENABLE KEYS */;

--
-- Dumping data for table `cm_products_colorways`
--


/*!40000 ALTER TABLE `cm_products_colorways` DISABLE KEYS */;
LOCK TABLES `cm_products_colorways` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_products_colorways` ENABLE KEYS */;

--
-- Dumping data for table `cm_products_options`
--


/*!40000 ALTER TABLE `cm_products_options` DISABLE KEYS */;
LOCK TABLES `cm_products_options` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_products_options` ENABLE KEYS */;

--
-- Dumping data for table `cm_products_options_seq`
--


/*!40000 ALTER TABLE `cm_products_options_seq` DISABLE KEYS */;
LOCK TABLES `cm_products_options_seq` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_products_options_seq` ENABLE KEYS */;

--
-- Dumping data for table `cm_products_relations`
--


/*!40000 ALTER TABLE `cm_products_relations` DISABLE KEYS */;
LOCK TABLES `cm_products_relations` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_products_relations` ENABLE KEYS */;

--
-- Dumping data for table `cm_products_seq`
--


/*!40000 ALTER TABLE `cm_products_seq` DISABLE KEYS */;
LOCK TABLES `cm_products_seq` WRITE;
INSERT INTO `cm_products_seq` VALUES (2);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_products_seq` ENABLE KEYS */;

--
-- Dumping data for table `cm_ship_class`
--


/*!40000 ALTER TABLE `cm_ship_class` DISABLE KEYS */;
LOCK TABLES `cm_ship_class` WRITE;
INSERT INTO `cm_ship_class` VALUES (1,'normal',0.00,0,'Zone',''),(2,'free',0.00,1,'Zone','');
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_ship_class` ENABLE KEYS */;

--
-- Dumping data for table `cm_ship_class_seq`
--


/*!40000 ALTER TABLE `cm_ship_class_seq` DISABLE KEYS */;
LOCK TABLES `cm_ship_class_seq` WRITE;
INSERT INTO `cm_ship_class_seq` VALUES (2);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_ship_class_seq` ENABLE KEYS */;

--
-- Dumping data for table `cm_shipmethods_flat_methods`
--


/*!40000 ALTER TABLE `cm_shipmethods_flat_methods` DISABLE KEYS */;
LOCK TABLES `cm_shipmethods_flat_methods` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_shipmethods_flat_methods` ENABLE KEYS */;

--
-- Dumping data for table `cm_shipmethods_zone_costs`
--


/*!40000 ALTER TABLE `cm_shipmethods_zone_costs` DISABLE KEYS */;
LOCK TABLES `cm_shipmethods_zone_costs` WRITE;
INSERT INTO `cm_shipmethods_zone_costs` VALUES (6,1,0.00,10.00,5.75),(7,1,10.00,30.00,8.78),(8,1,30.00,60.00,12.55),(9,1,60.00,300.00,45.01),(16,2,0.00,5.00,12.03),(17,2,5.00,40.00,19.24),(18,2,40.00,100.00,48.21),(19,2,100.00,0.00,99.01),(23,3,0.00,10.00,13.20),(24,3,10.00,100.00,18.22),(25,3,100.00,0.00,5.00),(36,4,0.00,10.00,45.00),(37,4,10.00,100.00,78.00),(38,4,100.00,1000.00,99.00);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_shipmethods_zone_costs` ENABLE KEYS */;

--
-- Dumping data for table `cm_shipmethods_zone_costs_seq`
--


/*!40000 ALTER TABLE `cm_shipmethods_zone_costs_seq` DISABLE KEYS */;
LOCK TABLES `cm_shipmethods_zone_costs_seq` WRITE;
INSERT INTO `cm_shipmethods_zone_costs_seq` VALUES (38);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_shipmethods_zone_costs_seq` ENABLE KEYS */;

--
-- Dumping data for table `cm_shipmethods_zone_locales`
--


/*!40000 ALTER TABLE `cm_shipmethods_zone_locales` DISABLE KEYS */;
LOCK TABLES `cm_shipmethods_zone_locales` WRITE;
INSERT INTO `cm_shipmethods_zone_locales` VALUES (1,'US','0'),(5,'CA','0'),(6,'AU','0'),(6,'CK','0'),(6,'CN','0'),(6,'FJ','0'),(6,'JP','0'),(6,'KR','0'),(6,'MV','0'),(6,'PF','0'),(6,'PG','0'),(6,'PH','0'),(6,'PW','0');
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_shipmethods_zone_locales` ENABLE KEYS */;

--
-- Dumping data for table `cm_shipmethods_zone_methods`
--


/*!40000 ALTER TABLE `cm_shipmethods_zone_methods` DISABLE KEYS */;
LOCK TABLES `cm_shipmethods_zone_methods` WRITE;
INSERT INTO `cm_shipmethods_zone_methods` VALUES (1,'GROUND','weight',1,0),(2,'EXPRESS','weight',1,0),(3,'CANADA POST','price',5,0),(4,'INTL AIR WEST','weight',6,0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_shipmethods_zone_methods` ENABLE KEYS */;

--
-- Dumping data for table `cm_shipmethods_zone_methods_seq`
--


/*!40000 ALTER TABLE `cm_shipmethods_zone_methods_seq` DISABLE KEYS */;
LOCK TABLES `cm_shipmethods_zone_methods_seq` WRITE;
INSERT INTO `cm_shipmethods_zone_methods_seq` VALUES (4);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_shipmethods_zone_methods_seq` ENABLE KEYS */;

--
-- Dumping data for table `cm_shipmethods_zone_zones`
--


/*!40000 ALTER TABLE `cm_shipmethods_zone_zones` DISABLE KEYS */;
LOCK TABLES `cm_shipmethods_zone_zones` WRITE;
INSERT INTO `cm_shipmethods_zone_zones` VALUES (1,'US'),(5,'Canada'),(6,'Asia/Pacific');
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_shipmethods_zone_zones` ENABLE KEYS */;

--
-- Dumping data for table `cm_shipmethods_zone_zones_seq`
--


/*!40000 ALTER TABLE `cm_shipmethods_zone_zones_seq` DISABLE KEYS */;
LOCK TABLES `cm_shipmethods_zone_zones_seq` WRITE;
INSERT INTO `cm_shipmethods_zone_zones_seq` VALUES (6);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_shipmethods_zone_zones_seq` ENABLE KEYS */;

--
-- Dumping data for table `cm_sizes`
--


/*!40000 ALTER TABLE `cm_sizes` DISABLE KEYS */;
LOCK TABLES `cm_sizes` WRITE;
INSERT INTO `cm_sizes` VALUES (1,'XS','X-Small',10),(2,'M','Medium',20),(3,'L','Large',30),(4,'XL','X-Large',40),(5,'XXL','XX-Large',50),(6,'S','Small',15);
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_sizes` ENABLE KEYS */;

--
-- Dumping data for table `cm_sizes_seq`
--


/*!40000 ALTER TABLE `cm_sizes_seq` DISABLE KEYS */;
LOCK TABLES `cm_sizes_seq` WRITE;
INSERT INTO `cm_sizes_seq` VALUES (6)order_weight int(5) not null;
UNLOCK TABLES;
/*!40000 ALTER TABLE `cm_sizes_seq` ENABLE KEYS */;

--
-- Dumping data for table `control_accounts`
--


/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

