-- Wed Nov  2 10:02:27 EST 2005
ALTER TABLE cm_products ADD default_thumb_id int unsigned AFTER view_count; 
ALTER TABLE cm_products ADD feature_rank int(10) unsigned AFTER is_featured;


-- Wed Nov  9 21:58:06 EST 2005
ALTER TABLE cm_order_items ADD normalized_attribs text;

-- date unknown
ALTER TABLE auth_user ADD cust_name varchar(63) not null after lname;
UPDATE auth_user SET cust_name = CONCAT(fname, ' ', lname);
ALTER TABLE cm_cart ADD ship_method varchar(64) NOT NULL AFTER ship_total;
ALTER TABLE cm_cart ADD tax_method varchar(64) NOT NULL AFTER  tax_total;
ALTER TABLE cm_cart ADD cm_paymentcc_id int(10) unsigned;
ALTER TABLE cm_cart MODIFY ship_total double(9,2) NOT NULL;
ALTER TABLE cm_cart MODIFY tax_total double(9,2) NOT NULL;

-- Fri Nov 18 19:43:04 EST 2005
CREATE TABLE `cm_coupons` (
  `code` varchar(10) NOT NULL default '',
  `descrip` varchar(32) NOT NULL default '',
  `percent_off` int(3) default NULL,
  `amt_off` double(5,2) default NULL,
  `used` tinyint(1) default NULL,
  PRIMARY KEY  (`code`)
);
ALTER TABLE cm_cart CHANGE coupon_id coupon_code char(10);
ALTER TABLE cm_orders ADD coupon_code char(10) AFTER currency_value;
ALTER TABLE cm_orders ADD discount_amt decimal(9,2) AFTER coupon_code;
ALTER TABLE cm_orders ADD discount_descrip varchar(32) AFTER discount_amt;
ALTER TABLE cm_orders ADD tax_method varchar(31) AFTER total_tax;
ALTER TABLE cm_orders CHANGE ship_method_descrip ship_method varchar(64);
ALTER TABLE cm_orders CHANGE total_tax tax_total double(15,2) not null;
ALTER TABLE cm_orders CHANGE total_shipping ship_total double(15,2) not null;


-- Wed Nov 23 15:56:55 EST 2005
-- FOR MARQUISJET!
ALTER TABLE cm_cart ADD packaging_option varchar(63);
ALTER TABLE cm_cart ADD enclosure_option varchar(15);
ALTER TABLE cm_cart ADD enclosure_card_text varchar(255);
ALTER TABLE cm_orders ADD packaging_option varchar(63); 
ALTER TABLE cm_orders ADD enclosure_option varchar(15);
ALTER TABLE cm_orders ADD enclosure_card_text varchar(255);
ALTER TABLE cm_cart ADD receive_date date;
ALTER TABLE cm_orders ADD receive_date date;



-- Wed Dec  7 18:45:18 EST 2005
-- add extra_totals capability - for FBB
CREATE TABLE `cm_cart_extra_totals` (
  `id` varchar(16) NOT NULL default '',
  `cart_id` int(10) unsigned NOT NULL default '0',
  `total` double(9,2) default NULL,
  `method` varchar(64) default NULL,
  PRIMARY KEY  (`id`,`cart_id`),
  KEY `ix_cid` (`cart_id`)
);
-- and item_options field
ALTER TABLE cm_cart_items ADD item_options text;
ALTER TABLE cm_order_items ADD item_options text;
-- and the cart_id field to link the order back to the "ghost cart"
ALTER TABLE cm_orders ADD cart_id int unsigned AFTER user_id;
ALTER TABLE cm_orders ADD index ix_cid (cart_id);


-- Thu Dec 15 16:10:36 EST 2005
-- SKU by item work for marquis
ALTER TABLE cm_cart_items ADD product_sku varchar(64) AFTER modified_date; 
ALTER TABLE cm_inventory ADD UNIQUE uq_sku (sku);
ALTER TABLE cm_inventory ADD sku varchar(64);   



-- Mon Feb 27 23:59:34 EST 2006
-- make cart_item_options normalized for TNL
ALTER TABLE cm_cart_items ADD has_item_options bool;
ALTER TABLE cm_order_items ADD has_item_options bool;
ALTER TABLE cm_cart_items ADD id int unsigned not null first;
UPDATE cm_cart_items SET id = (cart_id + inventory_id) * 10 + (UNIX_TIMESTAMP(modified_date) % 256);
ALTER TABLE cm_cart_items ADD primary key(id);

ALTER TABLE cm_order_items DROP item_options;
ALTER TABLE cm_cart_items DROP item_options;

UPDATE cm_cart_items_seq set id = (SELECT MAX(id) FROM cm_cart_items);

CREATE TABLE `cm_order_items_options` (
  `cm_order_items_id` int(10) unsigned NOT NULL default '0',
  `optkey` varchar(64) NOT NULL default '',
  `opt_descr` varchar(255) NOT NULL default '',
  `opt_value` varchar(255) default NULL,
  UNIQUE KEY `uq_key` (`cm_order_items_id`,`optkey`),
  KEY `ix_ccii` (`cm_order_items_id`),
  KEY `ix_ok` (`optkey`),
  KEY `ix_ov` (`opt_value`));

CREATE TABLE `cm_cart_items_options` (
  `cm_cart_items_id` int(11) NOT NULL default '0',
  `optkey` varchar(64) NOT NULL default '',
  `opt_descr` varchar(255) NOT NULL default '',
  `opt_value` varchar(255) default NULL,
  UNIQUE KEY `uq_key` (`cm_cart_items_id`,`optkey`),
  KEY `ix_ccii` (`cm_cart_items_id`),
  KEY `ix_ok` (`optkey`),
  KEY `ix_ov` (`opt_value`));

-- OMG allow items over $1000 
-- Tue Feb 28 16:46:11 EST 2006
ALTER TABLE cm_products MODIFY  price double(9,2);
ALTER TABLE cm_products MODIFY  list_price double(9,2);

-- bump up the order id, so it looks better:
UPDATE cm_orders_seq SET id = id + 1000;



-- fix bug with cm_categories, allow parent to be NULL
-- Sun Mar  5 22:49:21 EST 2006
ALTER TABLE cm_categories DROP parent_categoryid;
ALTER TABLE cm_categories MODIFY parent_cat_id int(9);
UPDATE cm_categories SET parent_cat_id = NULL WHERE parent_cat_id = 0;


-- add tables for the flat shipping matrix
-- Fri Jun  9 19:25:59 EDT 2006
CREATE TABLE `cm_shipmethods_flat` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `basis` enum('price','weight') NOT NULL,
  `country` char(2) default NULL,
  `order_weight` int(4) NOT NULL,
  PRIMARY KEY  (`id`)
);
CREATE TABLE `cm_shipmethods_flat_costs` (
  `id` int(10) unsigned NOT NULL,
  `cm_shipmethods_flat_id` int(10) unsigned NOT NULL,
  `basis_min` double(9,2) default NULL,
  `basis_max` double(9,2) default NULL,
  `cost` double(9,2) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `ix_smf` (`cm_shipmethods_flat_id`)
);

-- add cols for category icon file
-- Fri Aug  4 10:11:25 EDT 2006
ALTER TABLE cm_categories ADD  `cat_photo` varchar(255) default NULL;
ALTER TABLE cm_categories ADD  `cat_photo_mimetype` varchar(32) default NULL;
ALTER TABLE cm_categories ADD  `cat_photo_size` int(4) default NULL;
ALTER TABLE cm_categories ADD  `cat_photo_dims` varchar(32) default NULL;

-- Mon Aug 14 10:56:22 EDT 2006
ALTER TABLE auth_user MODIFY email varchar(255);
ALTER TABLE auth_user MODIFY username varchar(32);
ALTER TABLE auth_user ADD anon_email varchar(255);
ALTER TABLE auth_user ADD is_anon bool;


-- Fri Sep  1 10:48:17 EDT 2006
/* new products relations table, used on ANN */
CREATE TABLE `cm_products_relations` (
  `cm_products_id` int(10) unsigned NOT NULL default '0',
  `related_to` int(1) unsigned NOT NULL,
  `level` int(4) NOT NULL,
  PRIMARY KEY  (`cm_products_id`,`related_to`)
);


-- Mon Nov 20 16:51:26 EST 2006
-- products options table created for swipeit
CREATE TABLE `cm_products_options` (
        `id` int(10) unsigned NOT NULL default '0',
        `cm_products_id` int(11) unsigned NOT NULL default '0',
        `optkey` varchar(64) NOT NULL default '',
        `opt_descr` varchar(255) NOT NULL,
        `opt_value` varchar(255) NOT NULL default '',
        `adder` double(9,2) NOT NULL default '0.00',
        `order_weight` int(9) NOT NULL default '0',
        PRIMARY KEY  (`id`),
        UNIQUE KEY `uq_poo` (`cm_products_id`,`optkey`,`opt_value`),
        KEY `ix_ov` (`opt_value`)
        );

--Mon Nov 27 13:57:30 EST 2006
-- mfr logo needed for swipeit
ALTER TABLE cm_manufacturers ADD logo varchar(255);
ALTER TABLE cm_manufacturers ADD logo_mimetype varchar(32);
ALTER TABLE cm_manufacturers ADD logo_size int(4);
ALTER TABLE cm_manufacturers ADD logo_dims varchar(32);


-- Sun Dec 10 23:25:11 EST 2006
-- improvments to discount codes, for KHA
ALTER TABLE cm_coupons ADD is_active bool;
ALTER TABLE cm_coupons ADD belongs_name varchar(255);
ALTER TABLE cm_coupons ADD belongs_email varchar(255);
ALTER TABLE cm_coupons ADD expires datetime;
ALTER TABLE cm_coupons ADD never_expires bool;
ALTER TABLE cm_coupons ADD max_uses int(4);
ALTER TABLE cm_coupons MODIFY used int(4) not null;
ALTER TABLE cm_coupons ADD do_notify bool;


-- Tue Dec 19 18:53:49 EST 2006
-- for the giftcards for swi
CREATE TABLE `cm_giftcards` (
  `id` int(10) unsigned NOT NULL,
  `cart_id` int(10) unsigned NOT NULL,
  `gc_no` varchar(255) NOT NULL,
  `gc_amt` double(9,2) default NULL,
  `redeemed_amt` double(9,2) default NULL,
  `order_id` int(10) unsigned default NULL,
  `auth_reference` varchar(64) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uq_gccart` (`cart_id`,`gc_no`),
  KEY `ix_o` (`order_id`)
);
ALTER TABLE cm_orders ADD giftcard_total double(9,2) AFTER discount_descrip;



-- Fri Dec 29 15:22:59 EST 2006
-- rename 'Flat' shipping to 'Zone' and create Flat shipping stub
ALTER TABLE cm_shipmethods_flat RENAME cm_shipmethods_zone_methods;
ALTER TABLE cm_shipmethods_flat_costs RENAME cm_shipmethods_zone_costs;
ALTER TABLE cm_shipmethods_zone_costs CHANGE cm_shipmethods_flat_id cm_shipmethods_zone_methods_id int unsigned not null;
ALTER TABLE cm_shipmethods_flat_costs_seq RENAME cm_shipmethods_zone_costs_seq ;
ALTER TABLE cm_shipmethods_flat_seq RENAME cm_shipmethods_zone_methods_seq ;


CREATE TABLE `cm_shipmethods_zone_locales` (
  `zone_id` int(10) unsigned NOT NULL,
  `country` char(2) NOT NULL,
  `locality` char(4) NOT NULL,
  UNIQUE KEY `uq_zn` (`zone_id`,`country`,`locality`),
  KEY `ix_cn` (`country`),
  KEY `ix_lo` (`locality`),
  KEY `ix_zid` (`zone_id`)
);
CREATE TABLE `cm_shipmethods_zone_zones` (
  `id` int(10) unsigned NOT NULL,
  `zone_name` varchar(32) NOT NULL,
  PRIMARY KEY  (`id`)
);
CREATE TABLE `cm_shipmethods_flat_methods` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `cost` double(9,2) NOT NULL,
  PRIMARY KEY  (`id`)
);



-- Mon Feb 12 13:53:31 EST 2007
-- the adder field for cm_inventory
ALTER TABLE cm_inventory ADD adder double(9,2);


-- Wed Feb 28 15:38:51 EST 2007
-- create control to maintain zone/locales
ALTER TABLE cm_shipmethods_zone_locales CHANGE zone_id cm_shipmethods_zone_zones_id int unsigned;
ALTER TABLE cm_shipmethods_zone_methods CHANGE country cm_shipmethods_zone_zones_id int unsigned;
ALTER TABLE cm_shipmethods_zone_methods ADD INDEX ix_zid (cm_shipmethods_zone_zones_id);


-- Thu Jan 10 21:13:13 EST 2008
ALTER TABLE cm_order_items ADD backorder_qty int unsigned default 0 not null;
ALTER TABLE cm_categories ADD urlkey varchar(16);
ALTER TABLE cm_categories ADD index ix_urlkey (urlkey);

ALTER TABLE cm_products ADD order_weight int(5) not null;


-- Mon Feb  4 22:50:30 EST 2008
 ALTER TABLE cm_categories ADD index ix_pcat (parent_cat_id);


-- Wed Apr 23 21:43:45 EDT 2008
-- table to enable coupon<->category mapping (BED-2)
CREATE TABLE `cm_coupons_categories` (
  `cm_coupons_id` varchar(10) NOT NULL,
  `cm_categories_id` int(10) unsigned NOT NULL,
  `level` int(4) NOT NULL,
  PRIMARY KEY  (`cm_coupons_id`,`cm_categories_id`)
);


-- Fri May  2 08:35:57 EDT 2008
ALTER TABLE cm_order_transactions ADD has_avs_result bool;


-- Mon Jul 14 16:31:48 EDT 2008
ALTER TABLE cm_orders ADD UNIQUE uq_tok (order_token);



-- Tue Nov 25 10:55:19 EST 2008
-- this was overlooked at somepoint
ALTER TABLE cm_cart_items_options ADD cm_products_options_id int unsigned AFTER cm_cart_items_id;
ALTER TABLE cm_cart_items_options ADD INDEX ix_poi (cm_products_options_id);


-- Sat Nov 29 21:45:33 EST 2008
ALTER TABLE cm_order_transactions ADD trans_auth_code varchar(15) AFTER trans_id;
ALTER TABLE cm_order_transactions ADD trans_result_msg varchar(255) AFTER trans_result;
UPDATE cm_order_transactions SET trans_result_msg = SUBSTRING(trans_result FROM LOCATE(':', trans_result)+1), stamp=stamp;
UPDATE cm_order_transactions SET trans_result = TRIM(SUBSTRING(trans_result, 1, LOCATE(':', trans_result)-1)), stamp=stamp;


-- Fri Sep 18 19:27:04 EDT 2009
ALTER TABLE cm_cart MODIFY user_id int unsigned default NULL;


-- Wed Sep 23 22:53:30 EDT 2009
ALTER TABLE cm_order_history MODIFY `stamp` datetime NOT NULL;
ALTER TABLE cm_order_transactions MODIFY `stamp` datetime NOT NULL;


-- Fri Sep 25 14:22:38 EDT 2009
ALTER TABLE cm_orders ADD billing_phone varchar(64) AFTER billing_country;
ALTER TABLE cm_orders ADD billing_email varchar(128) AFTER billing_phone;


-- Mon Oct 26 15:43:10 EDT 2009
CREATE TABLE cm_bundles (
    id int unsigned not null AUTO_INCREMENT,
    title varchar(255) not null,
    base_price double (9,2) not null,
    assembly_fee double (9,2) not null,
    description varchar(1023) not null,
    PRIMARY KEY (`id`)
) Engine=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

ALTER TABLE cm_categories ENGINE InnoDB;
CREATE TABLE `cm_bundles_categories` (
  id int unsigned not null AUTO_INCREMENT,
  `cm_bundles_id` int(10) unsigned NOT NULL,
  `cm_categories_id` int(10) unsigned NOT NULL,
  `required` int(4) unsigned,
  PRIMARY KEY (`id`),
  UNIQUE (`cm_bundles_id`, `cm_categories_id`),
  CONSTRAINT `cm_bc_ibfk_1` FOREIGN KEY (`cm_categories_id`) REFERENCES `cm_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cm_bc_ibfk_2` FOREIGN KEY (`cm_bundles_id`) REFERENCES `cm_bundles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) Engine=InnoDB AUTO_INCREMENT=2;

-- this isn't used and won't be for the forseeable future.
-- CREATE TABLE cm_bundles_products (
    -- id int unsigned not null AUTO_INCREMENT,
    -- cm_products_id int unsigned not null,
    -- cm_bundles_id int unsigned not null,
    -- adder double(9,2) not null,
    -- UNIQUE KEY (cm_products_id, cm_bundles_id),
    -- CONSTRAINT `cm_bp_ibfk_1` FOREIGN KEY (`cm_products_id`) REFERENCES `cm_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    -- CONSTRAINT `cm_bp_ibfk_2` FOREIGN KEY (`cm_bundles_id`) REFERENCES `cm_bundles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    -- PRIMARY KEY (`id`)
-- ) Engine=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

ALTER TABLE cm_categories ADD is_used_in_bundle bool;

-- Tue Oct 27 20:03:07 EDT 2009
ALTER TABLE cm_bundles ADD sku varchar(63) not null;


-- Thu Oct 29 00:33:58 EDT 2009
ALTER TABLE cm_bundles ADD weight double(5,2) not null;

-- Thu Oct 29 11:18:55 EDT 2009
ALTER TABLE cm_bundles ADD long_description text;


-- Fri Mar 26 00:43:21 EDT 2010
ALTER TABLE cm_cart_items ADD is_bundle bool;


-- Tue Apr 13 15:09:40 EDT 2010
ALTER TABLE cm_inventory ENGINE InnoDB;
ALTER TABLE cm_products ENGINE InnoDB;

ALTER TABLE cm_inventory ADD KEY `fk_pid_inv` (`product_id`);
ALTER TABLE cm_inventory ADD CONSTRAINT `cm_inventory_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `cm_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;


-- Fri Apr 16 14:06:04 EDT 2010
ALTER TABLE cm_products_categories ENGINE InnoDB;
ALTER TABLE cm_products_categories ADD FOREIGN KEY (cm_categories_id) REFERENCES cm_categories (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE cm_products_categories ADD FOREIGN KEY (cm_products_id) REFERENCES cm_products (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE cm_categories MODIFY urlkey varchar(63) not null;


-- Fri Apr 23 18:48:29 EDT 2010
ALTER TABLE cm_products MODIFY weight double(9,2) NOT NULL;


-- Fri Apr 23 22:46:24 EDT 2010
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

-- Sun Apr 25 20:00:34 EDT 2010
ALTER TABLE cm_products ADD is_digital bool;
ALTER TABLE cm_cart ADD is_all_digital bool;


-- Mon Apr 26 09:38:41 EDT 2010
ALTER TABLE cm_cart_items ADD is_digital bool;
ALTER TABLE cm_order_items ADD is_digital bool;
ALTER TABLE cm_order_items ADD download_token varchar(255);
ALTER TABLE cm_order_items ADD UNIQUE (download_token);


-- Fri Apr 30 14:37:30 EDT 2010
delete from cm_order_transactions where cm_orders_id not in (select id from cm_orders);
ALTER TABLE cm_orders ENGINE InnoDB;
ALTER TABLE cm_order_transactions ENGINE InnoDB;
ALTER TABLE cm_order_transactions MODIFY cm_orders_id int not null;
ALTER TABLE cm_order_transactions ADD FOREIGN KEY (cm_orders_id) REFERENCES cm_orders (id) ON DELETE CASCADE ON UPDATE CASCADE;

delete from cm_order_history where order_id not in (select id from cm_orders);
ALTER TABLE cm_order_history ENGINE InnoDB;
ALTER TABLE cm_order_history MODIFY order_id int not null;
ALTER TABLE cm_order_history ADD FOREIGN KEY (order_id) REFERENCES cm_orders (id) ON DELETE CASCADE ON UPDATE CASCADE;

delete from cm_order_items where order_id not in (select id from cm_orders);
ALTER TABLE cm_order_items ENGINE InnoDB;
ALTER TABLE cm_order_items MODIFY order_id int not null;
ALTER TABLE cm_order_items ADD FOREIGN KEY (order_id) REFERENCES cm_orders (id) ON DELETE CASCADE ON UPDATE CASCADE;

-- Tue May 25 20:56:57 EDT 2010
ALTER TABLE auth_user RENAME cm_auth_user;

ALTER TABLE auth_user_seq RENAME cm_auth_user_seq;
UPDATE cm_auth_user_seq SET id = (SELECT MAX(id)+1000 FROM cm_auth_user);

ALTER TABLE cm_auth_user ENGINE InnoDB;
ALTER TABLE cm_orders MODIFY user_id INT(10) UNSIGNED NOT NULL;
-- !!! 
DELETE FROM cm_orders where user_id NOT IN (SELECT id from cm_auth_user);
-- !!! 
ALTER TABLE cm_orders ADD FOREIGN KEY (user_id) REFERENCES cm_auth_user (id) ON DELETE NO ACTIOn ON UPDATE CASCADE;

ALTER TABLE cm_address_book ENGINE InnoDB;
DELETE FROM cm_address_book WHERE user_id NOT IN (SELECT id from cm_auth_user);
ALTER TABLE cm_address_book MODIFY user_id int(10) unsigned not null;
ALTER TABLE cm_address_book ADD FOREIGN KEY (user_id) REFERENCES cm_auth_user (id) ON DELETE CASCADE ON UPDATE CASCADE;


-- Wed Jul  7 13:22:49 EDT 2010
ALTER TABLE cm_cart ADD uses_wholesale_pricing bool;
ALTER TABLE cm_orders ADD uses_wholesale_pricing bool;
ALTER TABLE cm_auth_user ADD is_active bool DEFAULT 1;


-- Fri Jul 29 13:32:41 EDT 2011
ALTER TABLE cm_giftcards ADD transaction_id VARCHAR(32); 


