CREATE DATABASE  IF NOT EXISTS `natupath` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `natupath`;
-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: natupath
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `boas_praticas`
--

DROP TABLE IF EXISTS `boas_praticas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `boas_praticas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `setor_id` int NOT NULL,
  `data_pratica` date NOT NULL,
  `impacto` enum('baixo','medio','alto') COLLATE utf8mb4_unicode_ci DEFAULT 'medio',
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pendente','aprovado','rejeitado') COLLATE utf8mb4_unicode_ci DEFAULT 'pendente',
  `observacao` text COLLATE utf8mb4_unicode_ci,
  `aprovado_por` int DEFAULT NULL,
  `aprovado_em` datetime DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `aprovado_por` (`aprovado_por`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_setor` (`setor_id`),
  KEY `idx_categoria` (`categoria_id`),
  KEY `idx_status` (`status`),
  KEY `idx_data` (`data_pratica`),
  CONSTRAINT `boas_praticas_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_boas_praticas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `boas_praticas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `boas_praticas_ibfk_3` FOREIGN KEY (`setor_id`) REFERENCES `setores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `boas_praticas_ibfk_4` FOREIGN KEY (`aprovado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `boas_praticas`
--

LOCK TABLES `boas_praticas` WRITE;
/*!40000 ALTER TABLE `boas_praticas` DISABLE KEYS */;
INSERT INTO `boas_praticas` VALUES (1,'FEWGWEHAHAHRAHRHA','HARAHRAHRHAQHRAHERHER',2,1,5,'2025-11-16','medio',NULL,'rejeitado','dgsgjsdhjghjsdjghisdhjgihsdghd',1,'2025-11-18 00:07:36','2025-11-17 02:38:55','2025-11-18 03:07:36'),(2,'ADADSADASDASDASDAS','DADSDASDADSADSADADADAD',2,1,5,'2025-11-16','medio',NULL,'rejeitado','vdsvdsvdsvds',1,'2025-11-18 00:04:50','2025-11-17 02:40:11','2025-11-18 03:04:50'),(3,'gagdaggagdsagdas','gdagdagdasgasgagdsagag',1,2,5,'2025-11-17','medio',NULL,'aprovado','Prática aprovada.',1,'2025-11-18 00:03:32','2025-11-17 19:01:43','2025-11-18 03:03:32'),(4,'twegewrgegewgwegwegewg','gewgwegewgwegwegwegwegwegweg',4,1,1,'2025-11-18','medio',NULL,'aprovado','Prática aprovada.',1,'2025-11-18 00:23:27','2025-11-18 03:23:11','2025-11-18 03:23:27'),(5,'dadsadad','dasdasdasdasdasdasdada',3,1,1,'2025-11-18','medio',NULL,'aprovado','Prática aprovada.',1,'2025-11-18 00:28:49','2025-11-18 03:28:28','2025-11-18 03:28:49'),(6,'sf\\fs\\fsfsafsafafs','fsafsfasfasfasfasfsafa',12,2,5,'2025-11-18','medio',NULL,'aprovado','Prática aprovada.',2,'2025-11-18 00:31:51','2025-11-18 03:29:45','2025-11-18 03:31:51'),(7,'ijidjsgijfgjdsjgifjos','gdasgdghjsadghadhsghuahsug',1,1,1,'2025-11-18','medio',NULL,'pendente',NULL,NULL,NULL,'2025-11-18 16:00:26','2025-11-18 16:00:26'),(8,'dasdasdasdasdasdas','dasdasdasdasdasdasdasd',2,1,1,'2025-11-18','medio',NULL,'pendente',NULL,NULL,NULL,'2025-11-18 16:18:44','2025-11-18 16:18:44'),(9,'não aceitar','sfsafasfasfasfasfasfasfasfas',12,1,1,'2025-11-19','medio',NULL,'pendente',NULL,NULL,NULL,'2025-11-19 19:24:40','2025-11-19 19:24:40'),(10,'dasdsadasdasdadas','dasdadasdsadasdasdasd',2,4,5,'2025-11-21','medio',NULL,'pendente',NULL,NULL,NULL,'2025-11-21 23:44:47','2025-11-21 23:44:47');
/*!40000 ALTER TABLE `boas_praticas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorias_boas_praticas`
--

DROP TABLE IF EXISTS `categorias_boas_praticas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias_boas_praticas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `icone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'fa-leaf',
  `cor` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#10b981',
  `setor_id` int DEFAULT NULL,
  `criado_por` int DEFAULT NULL,
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci DEFAULT 'ativo',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_setor` (`setor_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `categorias_boas_praticas_ibfk_1` FOREIGN KEY (`setor_id`) REFERENCES `setores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `categorias_boas_praticas_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias_boas_praticas`
--

LOCK TABLES `categorias_boas_praticas` WRITE;
/*!40000 ALTER TABLE `categorias_boas_praticas` DISABLE KEYS */;
INSERT INTO `categorias_boas_praticas` VALUES (1,'Economia de Energia','Práticas relacionadas à economia de energia elétrica','fa-bolt','#fbbf24',NULL,NULL,'ativo','2025-11-13 15:35:34'),(2,'Descarte Correto','Descarte adequado de resíduos e materiais','fa-recycle','#10b981',NULL,NULL,'ativo','2025-11-13 15:35:34'),(3,'Uso Consciente de Água','Práticas de economia e uso racional de água','fa-tint','#3b82f6',NULL,NULL,'ativo','2025-11-13 15:35:34'),(4,'Redução de Papel','Uso consciente de papel e digitalização','fa-file-alt','#8b5cf6',NULL,NULL,'ativo','2025-11-13 15:35:34'),(5,'Transporte Sustentável','Uso de transporte alternativo e carona solidária','fa-car','#06b6d4',NULL,NULL,'ativo','2025-11-13 15:35:34'),(12,'tweqteqwt','tqewtqwetewqtqtewqtweteqw','fa-tree','#10b981',NULL,1,'ativo','2025-11-17 01:04:19');
/*!40000 ALTER TABLE `categorias_boas_praticas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorias_nao_conformidades`
--

DROP TABLE IF EXISTS `categorias_nao_conformidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias_nao_conformidades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `icone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'fa-exclamation-triangle',
  `cor` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#ef4444',
  `setor_id` int DEFAULT NULL,
  `criado_por` int DEFAULT NULL,
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci DEFAULT 'ativo',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_setor` (`setor_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `categorias_nao_conformidades_ibfk_1` FOREIGN KEY (`setor_id`) REFERENCES `setores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `categorias_nao_conformidades_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias_nao_conformidades`
--

LOCK TABLES `categorias_nao_conformidades` WRITE;
/*!40000 ALTER TABLE `categorias_nao_conformidades` DISABLE KEYS */;
INSERT INTO `categorias_nao_conformidades` VALUES (1,'Desperdício de Energia','Uso desnecessário de energia elétrica','fa-lightbulb','#f59e0b',NULL,NULL,'ativo','2025-11-13 15:35:34'),(2,'Descarte Inadequado','Descarte incorreto de resíduos','fa-trash','#ef4444',NULL,NULL,'ativo','2025-11-13 15:35:34'),(3,'Vazamento de Água','Vazamentos e desperdício de água','fa-faucet','#3b82f6',NULL,NULL,'ativo','2025-11-13 15:35:34'),(4,'Condições Insalubres','Ambiente de trabalho inadequado','fa-exclamation-circle','#dc2626',NULL,NULL,'ativo','2025-11-13 15:35:34'),(5,'Falta de EPIs','Ausência de equipamentos de proteção','fa-hard-hat','#ef4444',NULL,NULL,'ativo','2025-11-13 15:35:34');
/*!40000 ALTER TABLE `categorias_nao_conformidades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nao_conformidades`
--

DROP TABLE IF EXISTS `nao_conformidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nao_conformidades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `setor_id` int NOT NULL,
  `local` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_ocorrencia` date NOT NULL,
  `gravidade` enum('baixa','media','alta','critica') COLLATE utf8mb4_unicode_ci DEFAULT 'media',
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('aberto','em_analise','resolvido','fechado') COLLATE utf8mb4_unicode_ci DEFAULT 'aberto',
  `solucao` text COLLATE utf8mb4_unicode_ci,
  `resolvido_por` int DEFAULT NULL,
  `resolvido_em` datetime DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `resolvido_por` (`resolvido_por`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_setor` (`setor_id`),
  KEY `idx_categoria` (`categoria_id`),
  KEY `idx_status` (`status`),
  KEY `idx_data` (`data_ocorrencia`),
  CONSTRAINT `nao_conformidades_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_nao_conformidades` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nao_conformidades_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nao_conformidades_ibfk_3` FOREIGN KEY (`setor_id`) REFERENCES `setores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nao_conformidades_ibfk_4` FOREIGN KEY (`resolvido_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nao_conformidades`
--

LOCK TABLES `nao_conformidades` WRITE;
/*!40000 ALTER TABLE `nao_conformidades` DISABLE KEYS */;
INSERT INTO `nao_conformidades` VALUES (1,'GASHGADFHAFHA','HAFADHDFHAHFAHRHARHHAE',2,1,5,'HRAHRAHA','2025-11-16','media',NULL,'resolvido','xbfbxbxbfdfegegesgsgegsegeg',1,'2025-11-18 00:07:09','2025-11-17 02:37:32','2025-11-18 03:07:09'),(2,'ksihjisjhisjihjidjhiosjd','hajmshjsdjjihjidsjhiosjdiohjsidojhiosd',1,1,1,'gagsagasgag','2025-11-18','media',NULL,'resolvido','gbsdhghsdhghsdughusd',1,'2025-11-18 00:27:21','2025-11-18 03:08:31','2025-11-18 03:27:21'),(3,'gjsdijgjdsgjsdjgjsdgjisod','gdjsfghjidsjgjdsjgisjigujiosdjgjs',2,1,1,'gragragrga','2025-11-18','media',NULL,'aberto',NULL,NULL,NULL,'2025-11-18 16:19:06','2025-11-18 16:19:06');
/*!40000 ALTER TABLE `nao_conformidades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `setores`
--

DROP TABLE IF EXISTS `setores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `setores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `gestor_id` int DEFAULT NULL,
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci DEFAULT 'ativo',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `gestor_id` (`gestor_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `setores_ibfk_1` FOREIGN KEY (`gestor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `setores`
--

LOCK TABLES `setores` WRITE;
/*!40000 ALTER TABLE `setores` DISABLE KEYS */;
INSERT INTO `setores` VALUES (1,'Administração','Setor administrativo da empresa',2,'ativo','2025-11-13 15:35:34','2025-11-25 19:55:03'),(2,'Produção','Setor de produção e manufatura',NULL,'ativo','2025-11-13 15:35:34','2025-11-13 15:35:34'),(3,'Logística','Setor de logística e transporte',NULL,'ativo','2025-11-13 15:35:34','2025-11-13 15:35:34'),(4,'Recursos Humanos','Gestão de pessoas',NULL,'ativo','2025-11-13 15:35:34','2025-11-13 15:35:34'),(5,'TI','Tecnologia da Informação',NULL,'ativo','2025-11-13 15:35:34','2025-11-13 15:35:34');
/*!40000 ALTER TABLE `setores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` enum('super_admin','gestor','usuario') COLLATE utf8mb4_unicode_ci DEFAULT 'usuario',
  `setor_id` int DEFAULT NULL,
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci DEFAULT 'ativo',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ultimo_login` datetime DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_setor` (`setor_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Administrador do Sistema','admin@natupath.com','perfil_1_1763344040.jpeg','super_admin',1,'ativo',NULL,'2025-11-25 17:57:31','2025-11-13 15:35:34'),(2,'rafa','$2y$10$MO3ubPEOcB3bdXnCOOojUe4h.NMQVvL0mGBmIvzRIJi4lumhMdK7e','rafahelen','rafa@gmail.com',NULL,'gestor',1,'ativo',NULL,'2025-11-21 20:49:15','2025-11-14 13:56:03'),(3,'joh','$2y$10$Kin3joct5M0UoqoFjIO8newlWSd5grA7QI2aRCVLwylDYgG5wycdi','joão','brunorodriguesbsr@gmail.com',NULL,'usuario',NULL,'ativo',NULL,'2025-11-14 11:01:50','2025-11-14 14:01:08'),(4,'teste','$2y$10$yR4Ut1cAWAFwDA1Ef0Gl0On3GjaWXTkxCsYPc1/lZavh4BOZ5ysuu','teste','teste@gmail.com',NULL,'usuario',5,'ativo',NULL,'2025-11-21 20:44:30','2025-11-21 21:15:46');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-25 18:17:42
