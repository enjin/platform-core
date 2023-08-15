/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attributes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `collection_id` bigint unsigned NOT NULL,
  `token_id` bigint unsigned DEFAULT NULL,
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attributes_collection_id_token_id_index` (`collection_id`,`token_id`),
  KEY `attributes_collection_id_token_id_key_index` (`collection_id`,`token_id`,`key`),
  KEY `attributes_collection_id_index` (`collection_id`),
  KEY `attributes_token_id_index` (`token_id`),
  CONSTRAINT `attributes_collection_id_foreign` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `attributes_token_id_foreign` FOREIGN KEY (`token_id`) REFERENCES `tokens` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blocks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `number` int NOT NULL,
  `hash` varchar(255) DEFAULT NULL,
  `synced` tinyint(1) NOT NULL DEFAULT '0',
  `failed` tinyint(1) NOT NULL DEFAULT '0',
  `exception` longtext,
  `retried` tinyint(1) NOT NULL DEFAULT '0',
  `events` longtext,
  `extrinsics` longtext,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blocks_number_unique` (`number`),
  KEY `blocks_synced_index` (`synced`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `collection_account_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collection_account_approvals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `collection_account_id` bigint unsigned NOT NULL,
  `wallet_id` bigint unsigned NOT NULL,
  `expiration` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `collection_account_approvals_collection_account_id_index` (`collection_account_id`),
  KEY `collection_account_approvals_wallet_id_index` (`wallet_id`),
  CONSTRAINT `collection_account_approvals_collection_account_id_foreign` FOREIGN KEY (`collection_account_id`) REFERENCES `collection_accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `collection_account_approvals_wallet_id_foreign` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `collection_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collection_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `wallet_id` bigint unsigned NOT NULL,
  `collection_id` bigint unsigned NOT NULL,
  `is_frozen` tinyint(1) NOT NULL DEFAULT '0',
  `account_count` varchar(255) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `collection_accounts_collection_id_wallet_id_index` (`collection_id`,`wallet_id`),
  KEY `collection_accounts_wallet_id_index` (`wallet_id`),
  KEY `collection_accounts_collection_id_index` (`collection_id`),
  CONSTRAINT `collection_accounts_collection_id_foreign` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `collection_accounts_wallet_id_foreign` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `collection_royalty_currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collection_royalty_currencies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `collection_id` bigint unsigned NOT NULL,
  `currency_collection_chain_id` varchar(255) NOT NULL,
  `currency_token_chain_id` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `collection_royalty_currencies_collection_id_index` (`collection_id`),
  KEY `collection_royalty_currencies_currency_collection_chain_id_index` (`currency_collection_chain_id`),
  KEY `collection_royalty_currencies_currency_token_chain_id_index` (`currency_token_chain_id`),
  CONSTRAINT `collection_royalty_currencies_collection_id_foreign` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `collection_chain_id` varchar(255) NOT NULL,
  `owner_wallet_id` bigint unsigned NOT NULL,
  `max_token_count` varchar(255) DEFAULT NULL,
  `max_token_supply` varchar(255) DEFAULT NULL,
  `force_single_mint` tinyint(1) NOT NULL DEFAULT '0',
  `royalty_wallet_id` bigint unsigned DEFAULT NULL,
  `royalty_percentage` double(8,2) DEFAULT NULL,
  `is_frozen` tinyint(1) NOT NULL DEFAULT '0',
  `token_count` varchar(255) NOT NULL DEFAULT '0',
  `attribute_count` varchar(255) NOT NULL DEFAULT '0',
  `total_deposit` varchar(255) NOT NULL DEFAULT '0',
  `network` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `collections_collection_chain_id_index` (`collection_chain_id`),
  KEY `collections_owner_wallet_id_index` (`owner_wallet_id`),
  KEY `collections_royalty_wallet_id_index` (`royalty_wallet_id`),
  CONSTRAINT `collections_owner_wallet_id_foreign` FOREIGN KEY (`owner_wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `collections_royalty_wallet_id_foreign` FOREIGN KEY (`royalty_wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint unsigned NOT NULL,
  `phase` int NOT NULL,
  `look_up` varchar(255) NOT NULL,
  `module_id` varchar(255) NOT NULL,
  `event_id` varchar(255) NOT NULL,
  `params` text,
  PRIMARY KEY (`id`),
  KEY `events_module_id_event_id_index` (`module_id`,`event_id`),
  KEY `events_transaction_id_index` (`transaction_id`),
  KEY `events_module_id_index` (`module_id`),
  KEY `events_event_id_index` (`event_id`),
  CONSTRAINT `events_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pending_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pending_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `sent` timestamp NOT NULL,
  `channels` json NOT NULL,
  `data` json NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pending_events_uuid_unique` (`uuid`),
  KEY `pending_events_name_index` (`name`),
  KEY `pending_events_sent_index` (`sent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `token_account_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `token_account_approvals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `token_account_id` bigint unsigned NOT NULL,
  `wallet_id` bigint unsigned NOT NULL,
  `amount` varchar(255) NOT NULL DEFAULT '0',
  `expiration` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `token_account_approvals_token_account_id_index` (`token_account_id`),
  KEY `token_account_approvals_wallet_id_index` (`wallet_id`),
  CONSTRAINT `token_account_approvals_token_account_id_foreign` FOREIGN KEY (`token_account_id`) REFERENCES `token_accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `token_account_approvals_wallet_id_foreign` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `token_account_named_reserves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `token_account_named_reserves` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `token_account_id` bigint unsigned NOT NULL,
  `pallet` varchar(255) NOT NULL,
  `amount` varchar(255) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `token_account_named_reserves_token_account_id_index` (`token_account_id`),
  CONSTRAINT `token_account_named_reserves_token_account_id_foreign` FOREIGN KEY (`token_account_id`) REFERENCES `token_accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `token_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `token_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `wallet_id` bigint unsigned NOT NULL,
  `collection_id` bigint unsigned NOT NULL,
  `token_id` bigint unsigned NOT NULL,
  `balance` varchar(255) NOT NULL DEFAULT '1',
  `reserved_balance` varchar(255) NOT NULL DEFAULT '0',
  `is_frozen` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `token_accounts_wallet_id_collection_id_index` (`wallet_id`,`collection_id`),
  KEY `token_accounts_wallet_id_collection_id_token_id_index` (`wallet_id`,`collection_id`,`token_id`),
  KEY `token_accounts_wallet_id_index` (`wallet_id`),
  KEY `token_accounts_collection_id_index` (`collection_id`),
  KEY `token_accounts_token_id_index` (`token_id`),
  CONSTRAINT `token_accounts_collection_id_foreign` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `token_accounts_token_id_foreign` FOREIGN KEY (`token_id`) REFERENCES `tokens` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `token_accounts_wallet_id_foreign` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `collection_id` bigint unsigned NOT NULL,
  `token_chain_id` varchar(255) NOT NULL,
  `supply` varchar(255) NOT NULL DEFAULT '1',
  `cap` varchar(255) NOT NULL,
  `cap_supply` varchar(255) DEFAULT NULL,
  `royalty_wallet_id` bigint unsigned DEFAULT NULL,
  `royalty_percentage` double(8,2) DEFAULT NULL,
  `is_currency` tinyint(1) NOT NULL DEFAULT '0',
  `listing_forbidden` tinyint(1) NOT NULL DEFAULT '0',
  `is_frozen` tinyint(1) NOT NULL DEFAULT '0',
  `minimum_balance` varchar(255) NOT NULL DEFAULT '1',
  `unit_price` varchar(255) NOT NULL DEFAULT '0',
  `attribute_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tokens_collection_id_token_chain_id_index` (`collection_id`,`token_chain_id`),
  KEY `tokens_collection_id_index` (`collection_id`),
  KEY `tokens_token_chain_id_index` (`token_chain_id`),
  KEY `tokens_royalty_wallet_id_index` (`royalty_wallet_id`),
  CONSTRAINT `tokens_collection_id_foreign` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tokens_royalty_wallet_id_foreign` FOREIGN KEY (`royalty_wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `idempotency_key` varchar(255) DEFAULT NULL,
  `transaction_chain_id` varchar(255) DEFAULT NULL,
  `transaction_chain_hash` varchar(255) DEFAULT NULL,
  `wallet_public_key` char(70) NOT NULL,
  `method` varchar(255) NOT NULL,
  `state` char(15) NOT NULL DEFAULT 'PENDING',
  `result` varchar(255) DEFAULT NULL,
  `encoded_data` text,
  `signed_at_block` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transactions_idempotency_key_unique` (`idempotency_key`),
  KEY `transactions_state_wallet_public_key_index` (`state`,`wallet_public_key`),
  KEY `transactions_transaction_chain_hash_wallet_public_key_index` (`transaction_chain_hash`,`wallet_public_key`),
  KEY `transactions_transaction_chain_id_index` (`transaction_chain_id`),
  KEY `transactions_wallet_public_key_index` (`wallet_public_key`),
  KEY `transactions_state_index` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `verifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `verification_id` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `public_key` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `verifications_verification_id_unique` (`verification_id`),
  KEY `verifications_public_key_index` (`public_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `external_id` varchar(255) DEFAULT NULL,
  `verification_id` varchar(255) DEFAULT NULL,
  `public_key` varchar(255) DEFAULT NULL,
  `managed` tinyint(1) NOT NULL DEFAULT '0',
  `network` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wallets_external_id_unique` (`external_id`),
  UNIQUE KEY `wallets_verification_id_unique` (`verification_id`),
  UNIQUE KEY `wallets_public_key_unique` (`public_key`),
  KEY `wallets_managed_public_key_index` (`managed`,`public_key`),
  KEY `wallets_managed_index` (`managed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` VALUES (1,'2022_04_09_120404_create_wallets_table',1);
INSERT INTO `migrations` VALUES (2,'2022_04_09_120405_create_collections_table',1);
INSERT INTO `migrations` VALUES (3,'2022_04_09_120406_create_collection_accounts_table',1);
INSERT INTO `migrations` VALUES (4,'2022_04_09_120407_create_tokens_table',1);
INSERT INTO `migrations` VALUES (5,'2022_04_09_120408_create_token_accounts_table',1);
INSERT INTO `migrations` VALUES (6,'2022_04_09_120409_create_attributes_table',1);
INSERT INTO `migrations` VALUES (7,'2022_04_09_120410_create_blocks_table',1);
INSERT INTO `migrations` VALUES (8,'2022_04_09_120411_create_transactions_table',1);
INSERT INTO `migrations` VALUES (9,'2022_05_26_101200_create_verifications_table',1);
INSERT INTO `migrations` VALUES (10,'2022_06_19_103015_create_token_account_approvals_table',1);
INSERT INTO `migrations` VALUES (11,'2022_06_19_144041_create_token_account_named_reserves_table',1);
INSERT INTO `migrations` VALUES (12,'2022_06_19_152115_create_collection_account_approvals_table',1);
INSERT INTO `migrations` VALUES (13,'2022_09_14_144041_create_collection_royalty_currencies_table',1);
INSERT INTO `migrations` VALUES (14,'2022_10_22_131819_create_events_table',1);
INSERT INTO `migrations` VALUES (15,'2023_02_03_135022_create_pending_events_table',1);
INSERT INTO `migrations` VALUES (16,'2023_03_20_204012_add_signed_at_block',1);
INSERT INTO `migrations` VALUES (17,'2023_05_23_034339_remove_link_code_from_wallets_table',1);
INSERT INTO `migrations` VALUES (18,'2023_08_01_175612_remove_mint_deposit_from_tokens_table',1);
