-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: ecommerce_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`cart_id`),
  UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
INSERT INTO `cart` VALUES (1,2,1,2,'2026-05-15 20:03:22'),(3,2,2,1,'2026-05-15 20:47:59'),(15,1,3,1,'2026-05-16 20:49:36'),(16,1,2,1,'2026-05-16 21:13:00');
/*!40000 ALTER TABLE `cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Electronics','Electronic devices and accessories','2026-05-15 16:28:47','cat_1778955888_c728548d.png'),(2,'Clothing','Fashion and apparel','2026-05-15 16:28:47','cat_1778940783_07cd8bfa.png'),(3,'Home & Garden','Home improvement and garden supplies','2026-05-15 16:28:47','cat_1778958342_ebdd59a7.png'),(4,'Sports & Outdoors','Sports equipment and outdoor gear','2026-05-15 16:28:47','cat_1778958311_747b7394.png'),(5,'Books','Books and educational materials','2026-05-15 16:28:47','cat_1778936514_b238175c.png'),(6,'Food and Beverages','','2026-05-16 19:11:37','cat_1778958697_e892fa3c.png');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `newsletter_subscribe` tinyint(1) DEFAULT 0,
  `status` enum('new','read','replied','closed') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hero_slides`
--

DROP TABLE IF EXISTS `hero_slides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hero_slides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `badge_text` varchar(50) DEFAULT NULL,
  `title_black` varchar(100) NOT NULL,
  `title_gray` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `button_text` varchar(50) DEFAULT 'Shop Now',
  `button_link` varchar(255) DEFAULT 'shop.php',
  `secondary_button_text` varchar(50) DEFAULT NULL,
  `secondary_button_link` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `card_bg` varchar(20) DEFAULT '#FFFFFF',
  `text_color` varchar(20) DEFAULT '#1A1A1A',
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hero_slides`
--

LOCK TABLES `hero_slides` WRITE;
/*!40000 ALTER TABLE `hero_slides` DISABLE KEYS */;
INSERT INTO `hero_slides` VALUES (1,'Innovation First','Future of ','Technology.','Discover our curated collection of cutting-edge hardware designed to elevate your digital experience.','Shop Tech','shop.php','Learn More','about.php','assets/images/hero_tech.png','#FFFFFF','#1A1A1A',1,1,'2026-05-15 22:54:35','2026-05-15 22:54:35'),(2,'Freshness Guaranteed','Organic & ','Natural.','We source only the finest organic produce directly from local farms to ensure your kitchen is always vibrant.','Explore Fresh','shop.php','Our Farms','shop.php','assets/images/hero_groceries.png','#FFFFFF','#1A1A1A',2,1,'2026-05-15 22:54:35','2026-05-15 22:54:35'),(3,'Modern Living','Elevate Your ','Space.','Premium lifestyle essentials and smart home integration that blends seamlessly with your aesthetic.','Shop Lifestyle','shop.php','Inspiration','about.php','assets/images/hero_lifestyle.png','#FFFFFF','#1A1A1A',3,1,'2026-05-15 22:54:35','2026-05-15 22:54:35');
/*!40000 ALTER TABLE `hero_slides` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter_subscribers`
--

DROP TABLE IF EXISTS `newsletter_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `unsubscribed_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter_subscribers`
--

LOCK TABLES `newsletter_subscribers` WRITE;
/*!40000 ALTER TABLE `newsletter_subscribers` DISABLE KEYS */;
/*!40000 ALTER TABLE `newsletter_subscribers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `product_id` (`product_id`),
  KEY `idx_order_items_order` (`order_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,2,1,129.99),(2,1,4,1,79.99),(3,1,5,1,89.99),(4,2,1,1,599.99),(5,3,5,1,89.99);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_status_updates`
--

DROP TABLE IF EXISTS `order_status_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_status_updates` (
  `update_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `update_notes` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`update_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_status_updates_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_status_updates`
--

LOCK TABLES `order_status_updates` WRITE;
/*!40000 ALTER TABLE `order_status_updates` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_status_updates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(50) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_status` enum('pending','processing','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT 'cash_on_delivery',
  `payment_reference` varchar(255) DEFAULT NULL,
  `payment_status` enum('pending','completed','paid','failed','cancelled') DEFAULT 'pending',
  `shipping_address` text NOT NULL,
  `billing_address` text NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `order_notes` text DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `idx_orders_user` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,1,'ORD-1778888926-6a07b0de6acf8',299.97,'shipped','paystack',NULL,'pending','vvk','vvk',NULL,NULL,NULL,'2026-05-15 23:48:46','2026-05-16 15:06:58'),(2,1,'ORD-1778944252-6a0888fc6c40b',599.99,'confirmed','paystack',NULL,'pending','tt','tt',NULL,NULL,NULL,'2026-05-16 15:10:52','2026-05-16 15:15:57'),(3,1,'ORD-1778944496-6a0889f0e81cb',89.99,'confirmed','paystack','TXN_1778944497_6a0889f1079f6','completed','kgli','kgli',NULL,NULL,NULL,'2026-05-16 15:14:56','2026-05-16 15:15:13');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_comparisons`
--

DROP TABLE IF EXISTS `product_comparisons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_comparisons` (
  `comparison_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`comparison_id`),
  UNIQUE KEY `unique_comparison` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_comparisons_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `product_comparisons_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_comparisons`
--

LOCK TABLES `product_comparisons` WRITE;
/*!40000 ALTER TABLE `product_comparisons` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_comparisons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`image_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES (3,3,'tshirt.jpg',1,0,'2026-05-16 15:50:50'),(4,4,'jeans.jpg',1,0,'2026-05-16 15:50:50'),(5,5,'coffeemaker.jpg',1,0,'2026-05-16 15:50:50'),(6,6,'runningshoes.jpg',1,0,'2026-05-16 15:50:50'),(8,1,'products/prod_1_1778947399_87bc4926.jpg',1,0,'2026-05-16 16:03:19'),(9,1,'products/prod_1_1778947399_1f027349.jpg',0,0,'2026-05-16 16:03:19'),(10,1,'products/prod_1_1778947399_63628630.jpg',0,0,'2026-05-16 16:03:19'),(11,2,'products/prod_2_1778970927_8c0fdce4.jpg',1,0,'2026-05-16 22:35:27'),(12,2,'products/prod_2_1778970927_f65d7782.jpg',0,0,'2026-05-16 22:35:27');
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_tag_relations`
--

DROP TABLE IF EXISTS `product_tag_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_tag_relations` (
  `product_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`product_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `product_tag_relations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  CONSTRAINT `product_tag_relations_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `product_tags` (`tag_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_tag_relations`
--

LOCK TABLES `product_tag_relations` WRITE;
/*!40000 ALTER TABLE `product_tag_relations` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_tag_relations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_tags`
--

DROP TABLE IF EXISTS `product_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_tags` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`tag_id`),
  UNIQUE KEY `tag_name` (`tag_name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_tags`
--

LOCK TABLES `product_tags` WRITE;
/*!40000 ALTER TABLE `product_tags` DISABLE KEYS */;
INSERT INTO `product_tags` VALUES (1,'Featured','2026-05-15 16:28:47'),(2,'Sale','2026-05-15 16:28:47'),(3,'New Arrival','2026-05-15 16:28:47'),(4,'Popular','2026-05-15 16:28:47'),(5,'Limited Edition','2026-05-15 16:28:47'),(6,'Eco-Friendly','2026-05-15 16:28:47'),(7,'Premium','2026-05-15 16:28:47'),(8,'Budget-Friendly','2026-05-15 16:28:47'),(9,'Trending','2026-05-15 16:28:47'),(10,'Best Seller','2026-05-15 16:28:47');
/*!40000 ALTER TABLE `product_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `subcategory_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `features` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `has_multiple_images` tinyint(1) DEFAULT 0,
  `main_image_id` int(11) DEFAULT NULL,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `review_count` int(11) DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`product_id`),
  KEY `subcategory_id` (`subcategory_id`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_price` (`price`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE,
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`subcategory_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,NULL,'Samsung s25 ultra new','Latest smartphone with advanced features','6.9-inch QHD+ Dynamic AMOLED 2X Display\r\n\r\n120Hz Adaptive Refresh Rate\r\n\r\n2,600 nits peak brightness\r\n\r\nSnapdragon 8 Elite for Galaxy processor\r\n\r\nUp to 4.47GHz clock speed\r\n\r\n12GB RAM (16GB in some markets)\r\n\r\n256GB / 512GB / 1TB storage\r\n\r\nNo microSD card slot\r\n\r\n200MP main rear camera with OIS\r\n\r\n50MP ultra-wide camera\r\n\r\n50MP telephoto camera (5x optical zoom)\r\n\r\n10MP telephoto camera (3x optical zoom)\r\n\r\n12MP front camera\r\n\r\nUp to 100x Space Zoom\r\n\r\n8K video recording at 30fps\r\n\r\nNight Video mode\r\n\r\nAudio Eraser feature\r\n\r\nLog Video recording\r\n\r\n5,000 mAh battery\r\n\r\n45W wired fast charging\r\n\r\nFast Wireless Charging 2.0\r\n\r\nWireless PowerShare\r\n\r\nTitanium frame\r\n\r\nIP68 water and dust resistance\r\n\r\nCorning Gorilla Armor 2 or Victus 2\r\n\r\n5G connectivity\r\n\r\nWi-Fi 7\r\n\r\nBluetooth 5.4',599.99,689.99,50,'products/prod_1_1778947399_87bc4926.jpg',1,8,5.00,1,0,'2026-05-15 16:28:47','2026-05-16 20:31:00'),(2,1,NULL,'Google Pixel 9','The Google Pixel 9, released in August 2024, is Google\'s entry-level flagship phone that brings a refined design, powerful AI capabilities, and excellent cameras at a more accessible price point than the Pro models. It features a complete redesign with a new pill-shaped camera bar, flat edges, and a glossy glass back with a matte aluminum frame.\r\n\r\nPowered by the Google Tensor G4 processor and 12GB of RAM (up from 8GB), the Pixel 9 is built to handle advanced AI tasks efficiently. The device introduces Gemini as the built-in AI assistant, replacing Google Assistant, and includes a suite of creative AI tools like Add Me for group photos and Pixel Studio for image generation.','Display\r\n\r\n6.3-inch Actua OLED display\r\n\r\n1080 x 2424 resolution (422 PPI)\r\n\r\n120Hz Smooth Display (60-120Hz adaptive)\r\n\r\nUp to 1800 nits (HDR) and 2700 nits peak brightness\r\n\r\nCorning Gorilla Glass Victus 2 protection\r\n\r\nHDR10+ support\r\n\r\nProcessor & Memory\r\n\r\nGoogle Tensor G4 chipset (4nm)\r\n\r\n12GB RAM\r\n\r\n128GB or 256GB storage (UFS 3.1)\r\n\r\nNo microSD card slot\r\n\r\nRear Camera System\r\n\r\n50MP wide camera (f/1.68, OIS, 1/1.31\" sensor)\r\n\r\n48MP ultrawide camera (f/1.7, 123° FoV, autofocus)\r\n\r\nMacro Focus on ultrawide lens\r\n\r\nSuper Res Zoom up to 8x (optical quality at 0.5x, 1x, 2x)\r\n\r\nLaser Detect AutoFocus (LDAF)\r\n\r\nSpectral and flicker sensor\r\n\r\nFront Camera\r\n\r\n10.5MP ultrawide (f/2.2, 95° FoV)\r\n\r\nAutofocus support',9000.00,10000.06,30,'products/prod_2_1778970927_8c0fdce4.jpg',1,11,0.00,0,0,'2026-05-15 16:28:47','2026-05-16 22:35:37'),(3,2,3,'Cotton T-Shirt','Comfortable cotton t-shirt',NULL,19.99,NULL,100,'tshirt.jpg',0,3,0.00,0,0,'2026-05-15 16:28:47','2026-05-16 15:52:05'),(4,2,4,'Denim Jeans','Classic denim jeans',NULL,79.99,NULL,75,'jeans.jpg',0,4,0.00,0,0,'2026-05-15 16:28:47','2026-05-16 15:52:05'),(5,3,NULL,'Coffee Maker','Automatic coffee maker',NULL,89.99,NULL,25,'coffeemaker.jpg',0,5,0.00,0,0,'2026-05-15 16:28:47','2026-05-16 15:52:05'),(6,4,NULL,'Running Shoes','Lightweight running shoes',NULL,99.99,NULL,40,'runningshoes.jpg',0,6,0.00,0,0,'2026-05-15 16:28:47','2026-05-16 15:52:05');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promo_cards`
--

DROP TABLE IF EXISTS `promo_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promo_cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `badge_text` varchar(50) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `subtitle` varchar(100) DEFAULT NULL,
  `price_text` varchar(50) DEFAULT NULL,
  `button_text` varchar(50) DEFAULT 'Buy Now',
  `button_link` varchar(255) DEFAULT 'shop.php',
  `image_path` varchar(255) NOT NULL,
  `card_bg` varchar(20) DEFAULT '#F2F4F7',
  `text_color` varchar(20) DEFAULT '#1A1A1A',
  `badge_color` varchar(20) DEFAULT '#666666',
  `is_button` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `promo_cards_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promo_cards`
--

LOCK TABLES `promo_cards` WRITE;
/*!40000 ALTER TABLE `promo_cards` DISABLE KEYS */;
/*!40000 ALTER TABLE `promo_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`review_id`),
  UNIQUE KEY `unique_user_product_review` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,1,1,5,'not bad','2026-05-16 17:18:52','2026-05-16 17:18:52');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES ('accent_color','#A3B18A','2026-05-15 16:55:24'),('announcement_text','','2026-05-15 23:35:01'),('footer_notice','','2026-05-15 23:35:01'),('primary_color','#0a4722','2026-05-16 17:58:14'),('products_per_row','5','2026-05-15 23:36:17'),('promo_popup_btn_link','shop.php','2026-05-16 22:39:10'),('promo_popup_btn_text','Shop Now','2026-05-16 22:39:10'),('promo_popup_content','Discover our amazing collection.','2026-05-16 22:39:10'),('promo_popup_enabled','1','2026-05-16 22:39:10'),('promo_popup_frequency','always','2026-05-16 22:39:10'),('promo_popup_image','promo/1778971929_Pixel10-Indigo-Front.jpg','2026-05-16 22:52:09'),('promo_popup_title','Welcome to Our Store!','2026-05-16 22:39:10'),('secondary_color','#588157','2026-05-15 16:55:24'),('site_description','Your one-stop shop for everything','2026-05-15 16:55:24'),('site_name','ASO ONLINE MARKET','2026-05-15 18:01:58');
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subcategories`
--

DROP TABLE IF EXISTS `subcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subcategories` (
  `subcategory_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `subcategory_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`subcategory_id`),
  UNIQUE KEY `unique_cat_subcat` (`category_id`,`subcategory_name`),
  CONSTRAINT `subcategories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subcategories`
--

LOCK TABLES `subcategories` WRITE;
/*!40000 ALTER TABLE `subcategories` DISABLE KEYS */;
INSERT INTO `subcategories` VALUES (1,1,'Phones','Smartphones and accessories','2026-05-15 16:28:47'),(2,1,'Audio','Headphones and speakers','2026-05-15 16:28:47'),(3,2,'Men','Menswear','2026-05-15 16:28:47'),(4,2,'Women','Womenswear','2026-05-15 16:28:47');
/*!40000 ALTER TABLE `subcategories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `reference` varchar(255) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'GHS',
  `status` enum('pending','success','failed','cancelled') DEFAULT 'pending',
  `gateway_response` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'paystack',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`transaction_id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`),
  KEY `idx_reference` (`reference`),
  KEY `idx_status` (`status`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrator','admin@shop.com','$2y$10$/s1JUyvapbuPaN.Rzfz2IeDXSCoZDqXou54iaqKOyIfo3BfwiPER6',NULL,NULL,'admin',1,'2026-05-15 16:28:47','2026-05-15 23:05:40'),(2,'Emmanuel Atio','minatoflash82@gmail.com','$2y$10$xeqCc44hI1GC.2MuXIUbVu6lwbcKsqS7c5YwaUefnHm6LB6.l9xl2','0550599755',NULL,'customer',1,'2026-05-15 18:48:48','2026-05-15 18:48:48');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wishlist` (
  `wishlist_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`wishlist_id`),
  UNIQUE KEY `unique_user_wishlist` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
INSERT INTO `wishlist` VALUES (8,1,1,'2026-05-16 22:51:05');
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-17  0:23:40
