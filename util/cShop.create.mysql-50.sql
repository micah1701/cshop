-- MySQL dump 10.11
--
-- Host: localhost    Database: shopnetjet
-- ------------------------------------------------------
-- Server version	5.0.45

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cm_auth_user`
--

DROP TABLE IF EXISTS `cm_auth_user`;
CREATE TABLE `cm_auth_user` (
  `id` int(10) unsigned NOT NULL default '0',
  `fname` varchar(32) NOT NULL default '',
  `lname` varchar(32) NOT NULL default '',
  `company` varchar(255) NOT NULL default '',
  `email` varchar(255) default NULL,
  `username` varchar(32) default NULL,
  `password` varchar(64) NOT NULL default '',
  `perms` varchar(16) default NULL,
  `billing_addr_id` int(10) unsigned default NULL,
  `shipping_addr_id` int(10) unsigned default NULL,
  `telephone` varchar(127) default NULL,
  `fax` varchar(127) default NULL,
  `force_pword_change` tinyint(1) default NULL,
  `token` varchar(16) default NULL,
  `cust_name` varchar(64) NOT NULL default '',
  `anon_email` varchar(255) default NULL,
  `is_anon` tinyint(1) default NULL,
  `emp_code` varchar(32) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uq_user` (`username`),
  UNIQUE KEY `uq_em` (`email`),
  KEY `ix_bai` (`billing_addr_id`),
  KEY `ix_sai` (`shipping_addr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `auth_user_seq`
--

DROP TABLE IF EXISTS `auth_user_seq`;
CREATE TABLE `auth_user_seq` (
  `id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_address_book`
--

DROP TABLE IF EXISTS `cm_address_book`;
CREATE TABLE `cm_address_book` (
  `id` int(11) NOT NULL default '0',
  `user_id` int(10) UNSIGNED NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `company` varchar(32) default NULL,
  `street_addr` varchar(128) NOT NULL default '',
  `addr2` varchar(128) NOT NULL default '',
  `city` varchar(64) NOT NULL default '',
  `state` varchar(32) default NULL,
  `postcode` varchar(16) NOT NULL default '',
  `country` varchar(32) NOT NULL default '',
  `zone_id` int(11) NOT NULL default '0',
  `phone` varchar(16) default NULL,
  `addr_title` varchar(255) NOT NULL default '',
  `addr_code` varchar(8) NOT NULL default '',
  `fax` varchar(32) default NULL,
  `email` varchar(128) default NULL,
  PRIMARY KEY  (`id`),
  KEY `ix_uid` (`user_id`),
  CONSTRAINT `cm_address_book_1` FOREIGN KEY (`user_id`) REFERENCES `cm_auth_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_address_book_seq`
--

DROP TABLE IF EXISTS `cm_address_book_seq`;
CREATE TABLE `cm_address_book_seq` (
  `id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_cart`
--

DROP TABLE IF EXISTS `cm_cart`;
CREATE TABLE `cm_cart` (
  `id` int(10) unsigned NOT NULL default '0',
  `user_id` int(10) unsigned default NULL,
  `ship_total` double(9,2) NOT NULL default '0.00',
  `ship_method` varchar(64) NOT NULL default '',
  `tax_total` double(9,2) NOT NULL default '0.00',
  `tax_method` varchar(64) NOT NULL default '',
  `coupon_code` varchar(10) default NULL,
  `modified_date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `create_date` timestamp NOT NULL default '0000-00-00 00:00:00',
  `purchased` tinyint(1) default NULL,
  `is_all_digital` tinyint(1) default NULL,
  `cm_paymentcc_id` int(10) unsigned default NULL,
  uses_wholesale_pricing bool,
  PRIMARY KEY  (`id`),
  KEY `ix_uid` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_cart_extra_totals`
--

DROP TABLE IF EXISTS `cm_cart_extra_totals`;
CREATE TABLE `cm_cart_extra_totals` (
  `id` varchar(16) NOT NULL default '',
  `cart_id` int(10) unsigned NOT NULL default '0',
  `total` double(9,2) default NULL,
  `method` varchar(64) default NULL,
  PRIMARY KEY  (`id`,`cart_id`),
  KEY `ix_cid` (`cart_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_cart_items`
--

DROP TABLE IF EXISTS `cm_cart_items`;
CREATE TABLE `cm_cart_items` (
  `id` int(10) unsigned NOT NULL default '0',
  `cart_id` int(10) unsigned NOT NULL default '0',
  `inventory_id` int(10) unsigned default NULL,
  `product_id` int(10) unsigned NOT NULL default '0',
  `qty` int(9) unsigned NOT NULL default '0',
  `price` double(9,2) NOT NULL default '0.00',
  `discount` double(9,2) NOT NULL default '0.00',
  `modified_date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `product_sku` varchar(64) default NULL,
  `product_descrip` varchar(255) default NULL,
  `product_attribs` text,
  `has_item_options` tinyint(1) default NULL,
  is_bundle bool,
  is_digital bool,
  PRIMARY KEY  (`id`),
  KEY `ix_cit` (`cart_id`),
  KEY `ix_pid` (`product_id`),
  KEY `uq_citem` (`cart_id`,`inventory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_cart_items_options`
--

DROP TABLE IF EXISTS `cm_cart_items_options`;
CREATE TABLE `cm_cart_items_options` (
  `cm_cart_items_id` int(11) NOT NULL default '0',
  `cm_products_options_id` int(10) unsigned default NULL,
  `optkey` varchar(64) NOT NULL default '',
  `opt_descr` varchar(255) NOT NULL default '',
  `opt_value` varchar(255) default NULL,
  UNIQUE KEY `uq_key` (`cm_cart_items_id`,`optkey`),
  KEY `ix_ccii` (`cm_cart_items_id`),
  KEY `ix_ok` (`optkey`),
  KEY `ix_ov` (`opt_value`),
  KEY `ix_xpoid` (`cm_products_options_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_cart_items_seq`
--

DROP TABLE IF EXISTS `cm_cart_items_seq`;
CREATE TABLE `cm_cart_items_seq` (
  `id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_cart_seq`
--

DROP TABLE IF EXISTS `cm_cart_seq`;
CREATE TABLE `cm_cart_seq` (
  `id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_categories`
--

DROP TABLE IF EXISTS `cm_categories`;
CREATE TABLE `cm_categories` (
  `id` int(10) unsigned NOT NULL default '0',
  `ship_class_id` int(10) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `level` int(4) unsigned NOT NULL default '0',
  `is_taxable` tinyint(1) default NULL,
  `is_active` tinyint(1) default '1',
  `parent_cat_id` int(9) default NULL,
  `icon_media_id` int(10) unsigned NOT NULL default '0',
  `feature_rank` tinyint(3) unsigned default NULL,
  `order_weight` int(3) NOT NULL default '0',
  `descrip` text,
  `cat_photo` varchar(255) default NULL,
  `cat_photo_mimetype` varchar(32) default NULL,
  `cat_photo_size` int(4) default NULL,
  `cat_photo_dims` varchar(32) default NULL,
  `swi_cat_class` enum('merchant','consumer','giftcards') default NULL,
  `urlkey` varchar(63) default NULL,
   is_used_in_bundle bool,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uq_urlkey` (`urlkey`),
  KEY `ix_shc` (`ship_class_id`),
  KEY `ix_imid` (`icon_media_id`),
  KEY `ix_scc` (`swi_cat_class`),
  KEY `ix_pcat` (`parent_cat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_categories_seq`
--

DROP TABLE IF EXISTS `cm_categories_seq`;
CREATE TABLE `cm_categories_seq` (
  `id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_colorways`
--

DROP TABLE IF EXISTS `cm_colorways`;
CREATE TABLE `cm_colorways` (
  `id` int(10) unsigned NOT NULL default '0',
  `name` varchar(64) NOT NULL default '',
  `rgb_value` varchar(16) default NULL,
  `code` varchar(16) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_colorways_seq`
--

DROP TABLE IF EXISTS `cm_colorways_seq`;
CREATE TABLE `cm_colorways_seq` (
  `id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_coupons`
--

DROP TABLE IF EXISTS `cm_coupons`;
CREATE TABLE `cm_coupons` (
  `code` varchar(10) NOT NULL default '',
  `descrip` varchar(32) NOT NULL default '',
  `percent_off` int(3) default NULL,
  `amt_off` double(5,2) default NULL,
  `used` int(4) NOT NULL,
  `is_active` tinyint(1) default NULL,
  `belongs_name` varchar(255) default NULL,
  `belongs_email` varchar(255) default NULL,
  `expires` datetime default NULL,
  `never_expires` tinyint(1) default NULL,
  `max_uses` int(4) default NULL,
  `do_notify` tinyint(1) default NULL,
  PRIMARY KEY  (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_coupons_categories`
--

DROP TABLE IF EXISTS `cm_coupons_categories`;
CREATE TABLE `cm_coupons_categories` (
  `cm_coupons_id` varchar(10) NOT NULL,
  `cm_categories_id` int(10) unsigned NOT NULL,
  `level` int(4) NOT NULL,
  PRIMARY KEY  (`cm_coupons_id`,`cm_categories_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_giftcards`
--

DROP TABLE IF EXISTS `cm_giftcards`;
CREATE TABLE `cm_giftcards` (
  `id` int(10) unsigned NOT NULL default '0',
  `cart_id` int(10) unsigned NOT NULL default '0',
  `gc_no` varchar(255) NOT NULL default '',
  `gc_amt` double(9,2) default NULL,
  `redeemed_amt` double(9,2) default NULL,
  `order_id` int(10) unsigned default NULL,
  `auth_reference` varchar(64) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uq_gccart` (`cart_id`,`gc_no`),
  KEY `ix_o` (`order_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_giftcards_seq`
--

DROP TABLE IF EXISTS `cm_giftcards_seq`;
CREATE TABLE `cm_giftcards_seq` (
  `id` int(10) unsigned NOT NULL auto_increment,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;


--
-- Table structure for table `cm_products`
--

DROP TABLE IF EXISTS `cm_products`;
CREATE TABLE `cm_products` (
  `id` int(10) unsigned NOT NULL default '0',
  `cm_ship_class_id` int(10) unsigned NOT NULL default '0',
  `cm_manufacturers_id` int(10) unsigned NOT NULL default '0',
  `price` double(9,2) default NULL,
  `list_price` double(9,2) default NULL,
  `weight` double(9,2) default 0 NOT NULL,
  `inv_qty` int(11) default NULL,
  `title` varchar(255) default NULL,
  `is_active` tinyint(1) default NULL,
  `is_featured` tinyint(1) default NULL,
  `is_digital` tinyint(1) default NULL,
  `feature_rank` int(10) unsigned NOT NULL default '0',
  `sku` varchar(64) default NULL,
  `description` text,
  `size_attr` text,
  `view_count` int(10) unsigned NOT NULL default '0',
  `default_thumb_id` int(10) unsigned default NULL,
  `is_special` enum('consumer','merchant') default NULL,
  `order_weight` int(5) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `ix_mid` (`cm_manufacturers_id`),
  KEY `cm_products_FKIndex2` (`cm_ship_class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



--
-- Table structure for table `cm_inventory`
--

DROP TABLE IF EXISTS `cm_inventory`;
CREATE TABLE `cm_inventory` (
  `id` int(10) unsigned NOT NULL default '0',
  `product_id` int(10) unsigned NOT NULL default '0',
  `qty` int(10) unsigned NOT NULL default '0',
  `sizes_id` int(10) unsigned default NULL,
  `colorways_id` int(10) unsigned default NULL,
  `sku` varchar(64) default NULL,
  `adder` double(9,2) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uq_sku` (`sku`),
  KEY `ix_size` (`sizes_id`),
  KEY `ix_colw` (`colorways_id`),
  CONSTRAINT `cm_inventory_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `cm_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_inventory_seq`
--

DROP TABLE IF EXISTS `cm_inventory_seq`;
CREATE TABLE `cm_inventory_seq` (
  `id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_manufacturers`
--

DROP TABLE IF EXISTS `cm_manufacturers`;
CREATE TABLE `cm_manufacturers` (
  `id` int(10) unsigned NOT NULL default '0',
  `name` varchar(255) default NULL,
  `is_active` tinyint(1) default '1',
  `logoid` int(10) unsigned default NULL,
  `url` varchar(64) default NULL,
  `description` text,
  `logo` varchar(255) default NULL,
  `logo_mimetype` varchar(32) default NULL,
  `logo_size` int(4) default NULL,
  `logo_dims` varchar(32) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_manufacturers_seq`
--

DROP TABLE IF EXISTS `cm_manufacturers_seq`;
CREATE TABLE `cm_manufacturers_seq` (
  `id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_media_files`
--

DROP TABLE IF EXISTS `cm_media_files`;
CREATE TABLE `cm_media_files` (
  `id` int(10) unsigned NOT NULL default '0',
  `user_id` varchar(31) default NULL,
  `filename` varchar(255) NOT NULL default '',
  `system_location` varchar(255) default NULL,
  `mime_type` varchar(255) default NULL,
  `filecreated` datetime default NULL,
  `uploadsource` varchar(255) default NULL,
  `md5sum` varchar(32) default NULL,
  `filesize` int(11) unsigned default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_orders`
--

DROP TABLE IF EXISTS `cm_orders`;
CREATE TABLE `cm_orders` (
  `id` int(11) NOT NULL default '0',
  `user_id` int(10) UNSIGNED NOT NULL default '0',
  `cart_id` int(10) unsigned default NULL,
  `shipping_name` varchar(255) NOT NULL default '',
  `shipping_company` varchar(32) default NULL,
  `shipping_street_addr` varchar(127) NOT NULL default '',
  `shipping_addr2` varchar(127) default NULL,
  `shipping_city` varchar(64) NOT NULL default '',
  `shipping_state` varchar(64) default NULL,
  `shipping_postcode` varchar(16) NOT NULL default '',
  `shipping_country` varchar(32) NOT NULL default '',
  `shipping_addr_format_id` int(5) NOT NULL default '0',
  `shipping_addr_code` varchar(8) default NULL,
  `shipping_fax` varchar(64) default NULL,
  `shipping_phone` varchar(64) default NULL,
  `shipping_email` varchar(128) default NULL,
  `billing_name` varchar(255) NOT NULL default '',
  `billing_company` varchar(32) default NULL,
  `billing_street_addr` varchar(127) NOT NULL default '',
  `billing_addr2` varchar(127) default NULL,
  `billing_city` varchar(64) NOT NULL default '',
  `billing_state` varchar(64) default NULL,
  `billing_postcode` varchar(16) NOT NULL default '',
  `billing_country` varchar(32) NOT NULL default '',
  `billing_phone` varchar(64) default NULL,
  `billing_email` varchar(128) default NULL,
  `billing_addr_format_id` int(5) NOT NULL default '0',
  `payment_method` varchar(32) NOT NULL default '',
  `cc_type` varchar(20) default NULL,
  `cc_owner` varchar(64) default NULL,
  `cc_number` varchar(32) default NULL,
  `cc_expires` varchar(4) default NULL,
  `order_create_date` datetime default NULL,
  `last_modified` datetime default NULL,
  `orders_status` int(5) NOT NULL default '0',
  `orders_date_finished` datetime default NULL,
  `currency` char(3) default NULL,
  `currency_value` decimal(14,6) default NULL,
  `coupon_code` varchar(10) default NULL,
  `discount_amt` decimal(9,2) default NULL,
  `discount_descrip` varchar(32) default NULL,
  `giftcard_total` double(9,2) default NULL,
  `tax_total` double(15,2) NOT NULL default '0.00',
  `tax_method` varchar(31) default NULL,
  `ship_total` double(15,2) NOT NULL default '0.00',
  `ship_method_id` int(10) unsigned default NULL,
  `ship_method` varchar(64) default NULL,
  `tracking_no` varchar(255) default NULL,
  `ship_date` varchar(63) default NULL,
  `delivery_date` varchar(63) default NULL,
  `amt_quoted` decimal(15,2) NOT NULL default '0.00',
  `amt_billed_to_date` decimal(15,2) NOT NULL default '0.00',
  `order_token` varchar(32) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uq_tok` (`order_token`),
  KEY `ix_uid` (`user_id`),
  KEY `ix_shp` (`ship_method_id`),
  KEY `ix_cid` (`cart_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


ALTER TABLE cm_orders  ADD FOREIGN KEY (user_id) REFERENCES cm_auth_user (id) ON DELETE NO ACTION ON UPDATE CASCADE;

--
-- Table structure for table `cm_orders_seq`
--

DROP TABLE IF EXISTS `cm_orders_seq`;
CREATE TABLE `cm_orders_seq` (
  `id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Table structure for table `cm_order_history`
--

DROP TABLE IF EXISTS `cm_order_history`;
CREATE TABLE `cm_order_history` (
  `id` int(10) unsigned NOT NULL default '0',
  `order_id` int NOT NULL default '0',
  `order_status` varchar(32) NOT NULL default '',
  `stamp` datetime NOT NULL,
  `user_notify` tinyint(1) default NULL,
  `comments` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `ix_oid` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE cm_order_history ADD FOREIGN KEY (order_id) REFERENCES cm_orders (id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Table structure for table `cm_order_history_seq`
--

DROP TABLE IF EXISTS `cm_order_history_seq`;
CREATE TABLE `cm_order_history_seq` (
  `id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_order_items`
--

DROP TABLE IF EXISTS `cm_order_items`;
CREATE TABLE `cm_order_items` (
  `id` int(10) unsigned NOT NULL default '0',
  `order_id` int              NOT NULL default '0',
  `product_id` int(10) unsigned NOT NULL default '0',
  `inventory_id` int(10) unsigned NOT NULL default '0',
  `stock_status` int(1) NOT NULL default '1',
  `qty` int(11) NOT NULL default '0',
  `price` double(9,2) NOT NULL default '0.00',
  `discount` double(9,2) NOT NULL default '0.00',
  `tax` double(7,4) NOT NULL default '0.0000',
  `product_sku` varchar(64) default NULL,
  `product_descrip` varchar(255) default NULL,
  `product_attribs` text,
  `normalized_attribs` text,
  `has_item_options` tinyint(1) default NULL,
  `backorder_qty` int(10) unsigned NOT NULL default '0',
  `is_digital` tinyint(1) default NULL,
  `download_token` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `download_token` (`download_token`),
  KEY `ix_oid` (`order_id`),
  KEY `ix_pid` (`product_id`),
  KEY `ix_iid` (`inventory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE cm_order_items ADD FOREIGN KEY (order_id) REFERENCES cm_orders (id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Table structure for table `cm_order_items_options`
--

DROP TABLE IF EXISTS `cm_order_items_options`;
CREATE TABLE `cm_order_items_options` (
  `cm_order_items_id` int(10) unsigned NOT NULL default '0',
  `optkey` varchar(64) NOT NULL default '',
  `opt_descr` varchar(255) NOT NULL default '',
  `opt_value` varchar(255) default NULL,
  UNIQUE KEY `uq_key` (`cm_order_items_id`,`optkey`),
  KEY `ix_ccii` (`cm_order_items_id`),
  KEY `ix_ok` (`optkey`),
  KEY `ix_ov` (`opt_value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_order_items_seq`
--

DROP TABLE IF EXISTS `cm_order_items_seq`;
CREATE TABLE `cm_order_items_seq` (
  `id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_order_transactions`
--

DROP TABLE IF EXISTS `cm_order_transactions`;
CREATE TABLE `cm_order_transactions` (
  `id` int(10) unsigned NOT NULL default '0',
  `cm_orders_id` int              NOT NULL default '0',
  `user_id` int(10) unsigned default NULL,
  `stamp` datetime NOT NULL,
  `trans_type` varchar(32) NOT NULL default '',
  `trans_result` varchar(255) default NULL,
  `trans_amount` double(9,2) default NULL,
  `trans_request` text,
  `trans_response` text,
  `trans_id` varchar(32) default NULL,
  `is_voided` char(1) default NULL,
  `verify_addr` char(1) default NULL,
  `has_avs_result` char(1) default NULL,
  `verify_zip` char(1) default NULL,
  `verify_name` char(1) default NULL,
  `verify_international` char(1) default NULL,
  `verify_csc` char(1) default NULL,
  PRIMARY KEY  (`id`),
  KEY `ix_oid` (`cm_orders_id`),
  KEY `ix_tid` (`trans_id`),
  KEY `ix_uid` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
ALTER TABLE cm_order_transactions ADD trans_auth_code varchar(15) AFTER trans_id;
ALTER TABLE cm_order_transactions ADD trans_result_msg varchar(255) AFTER trans_result;

ALTER TABLE cm_order_transactions ADD FOREIGN KEY (cm_orders_id) REFERENCES cm_orders (id) ON DELETE CASCADE ON UPDATE CASCADE;


--
-- Table structure for table `cm_order_transactions_seq`
--

DROP TABLE IF EXISTS `cm_order_transactions_seq`;
CREATE TABLE `cm_order_transactions_seq` (
  `id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_paymentcc`
--

DROP TABLE IF EXISTS `cm_paymentcc`;
CREATE TABLE `cm_paymentcc` (
  `id` int(10) unsigned NOT NULL default '0',
  `user_id` int(10) unsigned NOT NULL default '0',
  `cctype` varchar(8) NOT NULL default '',
  `ccno` varchar(32) NOT NULL default '',
  `ccexp` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_paymentcc_seq`
--

DROP TABLE IF EXISTS `cm_paymentcc_seq`;
CREATE TABLE `cm_paymentcc_seq` (
  `id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_product_images`
--

DROP TABLE IF EXISTS `cm_product_images`;
CREATE TABLE `cm_product_images` (
  `id` int(10) unsigned NOT NULL default '0',
  `cm_products_id` int(10) unsigned NOT NULL default '0',
  `class` varchar(16) default NULL,
  `colorways_id` int(10) unsigned default NULL,
  `system_location` varchar(255) default NULL,
  `filename_large` varchar(255) default NULL,
  `dims_large` varchar(32) default NULL,
  `filename_thumb` varchar(255) default NULL,
  `dims_thumb` varchar(32) default NULL,
  `filename_zoom` varchar(255) default NULL,
  `mime_type` varchar(32) default NULL,
  `order_weight` int(9) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `fk_pid` (`cm_products_id`),
  KEY `ix_class` (`class`),
  KEY `ix_cwid` (`colorways_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_product_images_seq`
--

DROP TABLE IF EXISTS `cm_product_images_seq`;
CREATE TABLE `cm_product_images_seq` (
  `id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_products_categories`
--

DROP TABLE IF EXISTS `cm_products_categories`;
CREATE TABLE `cm_products_categories` (
  `cm_products_id` int(10) unsigned NOT NULL default '0',
  `cm_categories_id` int(10) unsigned NOT NULL default '0',
  `level` int(4) NOT NULL default '0',
  PRIMARY KEY  (`cm_products_id`,`cm_categories_id`),
  KEY `fk_pid` (`cm_products_id`),
  KEY `fk_pca` (`cm_categories_id`),
  CONSTRAINT `cm_products_categories_ibfk_1` FOREIGN KEY (`cm_products_id`) REFERENCES `cm_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cm_products_categories_ibfk_3` FOREIGN KEY (`cm_categories_id`) REFERENCES `cm_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_products_colorways`
--

DROP TABLE IF EXISTS `cm_products_colorways`;
CREATE TABLE `cm_products_colorways` (
  `cm_products_id` int(10) unsigned NOT NULL default '0',
  `cm_colorways_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`cm_products_id`,`cm_colorways_id`),
  KEY `fk_pid` (`cm_products_id`),
  KEY `fk_cwid` (`cm_colorways_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_products_options`
--

DROP TABLE IF EXISTS `cm_products_options`;
CREATE TABLE `cm_products_options` (
  `id` int(10) unsigned NOT NULL default '0',
  `cm_products_id` int(11) unsigned NOT NULL default '0',
  `optkey` varchar(64) NOT NULL default '',
  `opt_descr` varchar(255) NOT NULL default '',
  `opt_value` varchar(255) NOT NULL default '',
  `adder` double(9,2) NOT NULL default '0.00',
  `order_weight` int(9) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uq_poo` (`cm_products_id`,`optkey`,`opt_value`),
  KEY `ix_ov` (`opt_value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_products_options_seq`
--

DROP TABLE IF EXISTS `cm_products_options_seq`;
CREATE TABLE `cm_products_options_seq` (
  `id` int(10) unsigned NOT NULL auto_increment,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_products_relations`
--

DROP TABLE IF EXISTS `cm_products_relations`;
CREATE TABLE `cm_products_relations` (
  `cm_products_id` int(10) unsigned NOT NULL default '0',
  `related_to` int(10) unsigned NOT NULL default '0',
  `level` int(4) NOT NULL default '0',
  PRIMARY KEY  (`cm_products_id`,`related_to`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_products_seq`
--

DROP TABLE IF EXISTS `cm_products_seq`;
CREATE TABLE `cm_products_seq` (
  `id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_ship_class`
--

DROP TABLE IF EXISTS `cm_ship_class`;
CREATE TABLE `cm_ship_class` (
  `id` int(10) unsigned NOT NULL default '0',
  `name` varchar(32) default NULL,
  `adder` double(5,2) default NULL,
  `is_free` tinyint(1) default NULL,
  `class_map` varchar(255) default NULL,
  `descrip` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_ship_class_seq`
--

DROP TABLE IF EXISTS `cm_ship_class_seq`;
CREATE TABLE `cm_ship_class_seq` (
  `id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_shipmethods_flat_methods`
--

DROP TABLE IF EXISTS `cm_shipmethods_flat_methods`;
CREATE TABLE `cm_shipmethods_flat_methods` (
  `id` int(10) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `cost` double(9,2) NOT NULL default '0.00',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_shipmethods_zone_costs`
--

DROP TABLE IF EXISTS `cm_shipmethods_zone_costs`;
CREATE TABLE `cm_shipmethods_zone_costs` (
  `id` int(10) unsigned NOT NULL default '0',
  `cm_shipmethods_zone_methods_id` int(10) unsigned NOT NULL default '0',
  `basis_min` double(9,2) default NULL,
  `basis_max` double(9,2) default NULL,
  `cost` double(9,2) NOT NULL default '0.00',
  PRIMARY KEY  (`id`),
  KEY `ix_smf` (`cm_shipmethods_zone_methods_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_shipmethods_zone_costs_seq`
--

DROP TABLE IF EXISTS `cm_shipmethods_zone_costs_seq`;
CREATE TABLE `cm_shipmethods_zone_costs_seq` (
  `id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_shipmethods_zone_locales`
--

DROP TABLE IF EXISTS `cm_shipmethods_zone_locales`;
CREATE TABLE `cm_shipmethods_zone_locales` (
  `cm_shipmethods_zone_zones_id` int(10) unsigned default NULL,
  `country` char(2) NOT NULL default '',
  `locality` char(4) NOT NULL default '',
  UNIQUE KEY `uq_zn` (`cm_shipmethods_zone_zones_id`,`country`,`locality`),
  KEY `ix_cn` (`country`),
  KEY `ix_lo` (`locality`),
  KEY `ix_zid` (`cm_shipmethods_zone_zones_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_shipmethods_zone_methods`
--

DROP TABLE IF EXISTS `cm_shipmethods_zone_methods`;
CREATE TABLE `cm_shipmethods_zone_methods` (
  `id` int(10) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `basis` enum('price','weight') NOT NULL default 'price',
  `cm_shipmethods_zone_zones_id` int(10) unsigned default NULL,
  `order_weight` int(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `ix_zid` (`cm_shipmethods_zone_zones_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_shipmethods_zone_methods_seq`
--

DROP TABLE IF EXISTS `cm_shipmethods_zone_methods_seq`;
CREATE TABLE `cm_shipmethods_zone_methods_seq` (
  `id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_shipmethods_zone_zones`
--

DROP TABLE IF EXISTS `cm_shipmethods_zone_zones`;
CREATE TABLE `cm_shipmethods_zone_zones` (
  `id` int(10) unsigned NOT NULL default '0',
  `zone_name` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_shipmethods_zone_zones_seq`
--

DROP TABLE IF EXISTS `cm_shipmethods_zone_zones_seq`;
CREATE TABLE `cm_shipmethods_zone_zones_seq` (
  `id` int(10) unsigned NOT NULL auto_increment,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_sizes`
--

DROP TABLE IF EXISTS `cm_sizes`;
CREATE TABLE `cm_sizes` (
  `id` int(10) unsigned NOT NULL default '0',
  `code` varchar(8) NOT NULL default '',
  `fullname` varchar(32) NOT NULL default '',
  `order_weight` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `ix_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `cm_sizes_seq`
--

DROP TABLE IF EXISTS `cm_sizes_seq`;
CREATE TABLE `cm_sizes_seq` (
  `id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


 
CREATE TABLE cm_bundles (
    id int unsigned not null AUTO_INCREMENT,
    title varchar(255) not null,
    base_price double (9,2) not null,
    assembly_fee double (9,2) not null,
    description varchar(1023) not null,
    sku varchar(63) not null,
    weight double(5,2) not null,
    long_description text,
    PRIMARY KEY (`id`)
) Engine=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

CREATE TABLE `cm_bundles_categories` (
  id int unsigned not null AUTO_INCREMENT,
  `cm_bundles_id` int(10) unsigned NOT NULL,
  `cm_categories_id` int(10) unsigned NOT NULL,
  `required` int(4) unsigned,
  PRIMARY KEY (`id`),
  UNIQUE (`cm_bundles_id`, `cm_categories_id`),
  CONSTRAINT `cm_bc_ibfk_1` FOREIGN KEY (`cm_categories_id`) REFERENCES `cm_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cm_bc_ibfk_2` FOREIGN KEY (`cm_bundles_id`) REFERENCES `cm_bundles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) Engine=InnoDB AUTO_INCREMENT=30;

CREATE TABLE `cm_products_downloads` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `url` varchar(1023) NOT NULL,
  `cm_products_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `cm_products_id` (`cm_products_id`),
  CONSTRAINT `cm_products_downloads_ibfk_1` FOREIGN KEY (`cm_products_id`) REFERENCES `cm_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;




/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2008-11-14 18:45:59
