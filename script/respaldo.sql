-- MySQL dump 10.13  Distrib 8.4.10, for Linux (x86_64)
--
-- Host: localhost    Database: sigeru
-- ------------------------------------------------------
-- Server version	8.4.10-0ubuntu0.26.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `Administrador`
--

DROP TABLE IF EXISTS `Administrador`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Administrador` (
  `CI` char(8) NOT NULL,
  PRIMARY KEY (`CI`),
  CONSTRAINT `Administrador_ibfk_1` FOREIGN KEY (`CI`) REFERENCES `Usuario` (`ci`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Administrador`
--

LOCK TABLES `Administrador` WRITE;
/*!40000 ALTER TABLE `Administrador` DISABLE KEYS */;
INSERT INTO `Administrador` VALUES ('34567890');
/*!40000 ALTER TABLE `Administrador` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Al`
--

DROP TABLE IF EXISTS `Al`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Al` (
  `Id_Incidencia` int NOT NULL,
  `Id_Contenedor` int NOT NULL,
  PRIMARY KEY (`Id_Incidencia`,`Id_Contenedor`),
  KEY `Id_Contenedor` (`Id_Contenedor`),
  CONSTRAINT `Al_ibfk_1` FOREIGN KEY (`Id_Incidencia`) REFERENCES `Incidencia` (`Id_Incidencia`),
  CONSTRAINT `Al_ibfk_2` FOREIGN KEY (`Id_Contenedor`) REFERENCES `Contenedor` (`Id_Contenedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Al`
--

LOCK TABLES `Al` WRITE;
/*!40000 ALTER TABLE `Al` DISABLE KEYS */;
INSERT INTO `Al` VALUES (1,1),(1,2),(1,3);
/*!40000 ALTER TABLE `Al` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Camion`
--

DROP TABLE IF EXISTS `Camion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Camion` (
  `Matricula` varchar(10) NOT NULL,
  `Estado` varchar(30) DEFAULT NULL,
  `Tipo` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`Matricula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Camion`
--

LOCK TABLES `Camion` WRITE;
/*!40000 ALTER TABLE `Camion` DISABLE KEYS */;
INSERT INTO `Camion` VALUES ('SBI1234','Disponible','Compactador');
/*!40000 ALTER TABLE `Camion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Centro`
--

DROP TABLE IF EXISTS `Centro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Centro` (
  `ID` int NOT NULL,
  `Servicio` varchar(100) DEFAULT NULL,
  `ContAlmacenados` int DEFAULT NULL,
  `Capacidad` int DEFAULT NULL,
  `CamAlmacenados` int DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Centro`
--

LOCK TABLES `Centro` WRITE;
/*!40000 ALTER TABLE `Centro` DISABLE KEYS */;
INSERT INTO `Centro` VALUES (1,'Clasificación',250,600,12);
/*!40000 ALTER TABLE `Centro` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CentroDeAcopio`
--

DROP TABLE IF EXISTS `CentroDeAcopio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CentroDeAcopio` (
  `ID` int NOT NULL,
  PRIMARY KEY (`ID`),
  CONSTRAINT `CentroDeAcopio_ibfk_1` FOREIGN KEY (`ID`) REFERENCES `Centro` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CentroDeAcopio`
--

LOCK TABLES `CentroDeAcopio` WRITE;
/*!40000 ALTER TABLE `CentroDeAcopio` DISABLE KEYS */;
INSERT INTO `CentroDeAcopio` VALUES (1);
/*!40000 ALTER TABLE `CentroDeAcopio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Centro_Herramientas`
--

DROP TABLE IF EXISTS `Centro_Herramientas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Centro_Herramientas` (
  `ID` int NOT NULL,
  `Herramienta` varchar(100) NOT NULL,
  PRIMARY KEY (`ID`,`Herramienta`),
  CONSTRAINT `Centro_Herramientas_ibfk_1` FOREIGN KEY (`ID`) REFERENCES `Centro` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Centro_Herramientas`
--

LOCK TABLES `Centro_Herramientas` WRITE;
/*!40000 ALTER TABLE `Centro_Herramientas` DISABLE KEYS */;
INSERT INTO `Centro_Herramientas` VALUES (1,'Carretilla'),(1,'Escoba'),(1,'Hidrolavadora'),(1,'Pala');
/*!40000 ALTER TABLE `Centro_Herramientas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Circula`
--

DROP TABLE IF EXISTS `Circula`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Circula` (
  `CI` char(8) NOT NULL,
  `Id_Contenedor` int NOT NULL,
  PRIMARY KEY (`CI`,`Id_Contenedor`),
  KEY `Id_Contenedor` (`Id_Contenedor`),
  CONSTRAINT `Circula_ibfk_1` FOREIGN KEY (`CI`) REFERENCES `Cuadrilla` (`CI`),
  CONSTRAINT `Circula_ibfk_2` FOREIGN KEY (`Id_Contenedor`) REFERENCES `Ruta` (`Id_Contenedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Circula`
--

LOCK TABLES `Circula` WRITE;
/*!40000 ALTER TABLE `Circula` DISABLE KEYS */;
INSERT INTO `Circula` VALUES ('45678901',1);
/*!40000 ALTER TABLE `Circula` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Contenedor`
--

DROP TABLE IF EXISTS `Contenedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Contenedor` (
  `Id_Contenedor` int NOT NULL,
  `Tipo` varchar(50) DEFAULT NULL,
  `Estado` varchar(30) DEFAULT NULL,
  `Latitud` decimal(10,7) DEFAULT NULL,
  `Longitud` decimal(10,7) DEFAULT NULL,
  PRIMARY KEY (`Id_Contenedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Contenedor`
--

LOCK TABLES `Contenedor` WRITE;
/*!40000 ALTER TABLE `Contenedor` DISABLE KEYS */;
INSERT INTO `Contenedor` VALUES (1,'Orgánico','Lleno',-34.9058300,-56.1916200),(2,'Reciclable','Vacío',-34.9062500,-56.1904500),(3,'Orgánico','Medio',-34.9049000,-56.1897000);
/*!40000 ALTER TABLE `Contenedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Cuadrilla`
--

DROP TABLE IF EXISTS `Cuadrilla`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Cuadrilla` (
  `CI` char(8) NOT NULL,
  PRIMARY KEY (`CI`),
  CONSTRAINT `Cuadrilla_ibfk_1` FOREIGN KEY (`CI`) REFERENCES `Usuario` (`ci`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Cuadrilla`
--

LOCK TABLES `Cuadrilla` WRITE;
/*!40000 ALTER TABLE `Cuadrilla` DISABLE KEYS */;
INSERT INTO `Cuadrilla` VALUES ('45678901');
/*!40000 ALTER TABLE `Cuadrilla` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Incidencia`
--

DROP TABLE IF EXISTS `Incidencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Incidencia` (
  `Id_Incidencia` int NOT NULL,
  `Tipo` varchar(50) DEFAULT NULL,
  `Estado` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`Id_Incidencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Incidencia`
--

LOCK TABLES `Incidencia` WRITE;
/*!40000 ALTER TABLE `Incidencia` DISABLE KEYS */;
INSERT INTO `Incidencia` VALUES (1,'Contenedor desbordado','Pendiente');
/*!40000 ALTER TABLE `Incidencia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Metodo`
--

DROP TABLE IF EXISTS `Metodo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Metodo` (
  `Id_Metodo` int NOT NULL,
  PRIMARY KEY (`Id_Metodo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Metodo`
--

LOCK TABLES `Metodo` WRITE;
/*!40000 ALTER TABLE `Metodo` DISABLE KEYS */;
INSERT INTO `Metodo` VALUES (1);
/*!40000 ALTER TABLE `Metodo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Operario`
--

DROP TABLE IF EXISTS `Operario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Operario` (
  `CI` char(8) NOT NULL,
  PRIMARY KEY (`CI`),
  CONSTRAINT `Operario_ibfk_1` FOREIGN KEY (`CI`) REFERENCES `Usuario` (`ci`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Operario`
--

LOCK TABLES `Operario` WRITE;
/*!40000 ALTER TABLE `Operario` DISABLE KEYS */;
INSERT INTO `Operario` VALUES ('23456789');
/*!40000 ALTER TABLE `Operario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Poligono`
--

DROP TABLE IF EXISTS `Poligono`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Poligono` (
  `Id_Poligono` int NOT NULL,
  PRIMARY KEY (`Id_Poligono`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Poligono`
--

LOCK TABLES `Poligono` WRITE;
/*!40000 ALTER TABLE `Poligono` DISABLE KEYS */;
INSERT INTO `Poligono` VALUES (1);
/*!40000 ALTER TABLE `Poligono` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Poligono_Vertices`
--

DROP TABLE IF EXISTS `Poligono_Vertices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Poligono_Vertices` (
  `Id_Poligono` int NOT NULL,
  `Orden` int NOT NULL,
  `Latitud` decimal(10,7) DEFAULT NULL,
  `Longitud` decimal(10,7) DEFAULT NULL,
  PRIMARY KEY (`Id_Poligono`,`Orden`),
  CONSTRAINT `Poligono_Vertices_ibfk_1` FOREIGN KEY (`Id_Poligono`) REFERENCES `Poligono` (`Id_Poligono`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Poligono_Vertices`
--

LOCK TABLES `Poligono_Vertices` WRITE;
/*!40000 ALTER TABLE `Poligono_Vertices` DISABLE KEYS */;
INSERT INTO `Poligono_Vertices` VALUES (1,1,-34.9068000,-56.1926000),(1,2,-34.9068000,-56.1887000),(1,3,-34.9037000,-56.1887000),(1,4,-34.9037000,-56.1926000);
/*!40000 ALTER TABLE `Poligono_Vertices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Recoge`
--

DROP TABLE IF EXISTS `Recoge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Recoge` (
  `Id_Contenedor` int NOT NULL,
  `Matricula` varchar(10) NOT NULL,
  PRIMARY KEY (`Id_Contenedor`,`Matricula`),
  KEY `Matricula` (`Matricula`),
  CONSTRAINT `Recoge_ibfk_1` FOREIGN KEY (`Id_Contenedor`) REFERENCES `Contenedor` (`Id_Contenedor`),
  CONSTRAINT `Recoge_ibfk_2` FOREIGN KEY (`Matricula`) REFERENCES `Camion` (`Matricula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Recoge`
--

LOCK TABLES `Recoge` WRITE;
/*!40000 ALTER TABLE `Recoge` DISABLE KEYS */;
INSERT INTO `Recoge` VALUES (1,'SBI1234'),(2,'SBI1234'),(3,'SBI1234');
/*!40000 ALTER TABLE `Recoge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Reporta`
--

DROP TABLE IF EXISTS `Reporta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Reporta` (
  `CI` char(8) NOT NULL,
  `Id_Incidencia` int NOT NULL,
  PRIMARY KEY (`CI`,`Id_Incidencia`),
  KEY `Id_Incidencia` (`Id_Incidencia`),
  CONSTRAINT `Reporta_ibfk_1` FOREIGN KEY (`CI`) REFERENCES `Usuario` (`ci`),
  CONSTRAINT `Reporta_ibfk_2` FOREIGN KEY (`Id_Incidencia`) REFERENCES `Incidencia` (`Id_Incidencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Reporta`
--

LOCK TABLES `Reporta` WRITE;
/*!40000 ALTER TABLE `Reporta` DISABLE KEYS */;
INSERT INTO `Reporta` VALUES ('12345678',1);
/*!40000 ALTER TABLE `Reporta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Reporta_Centro`
--

DROP TABLE IF EXISTS `Reporta_Centro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Reporta_Centro` (
  `CI` char(8) NOT NULL,
  `ID` int NOT NULL,
  `Cant_Residuos` int DEFAULT NULL,
  PRIMARY KEY (`CI`,`ID`),
  KEY `ID` (`ID`),
  CONSTRAINT `Reporta_Centro_ibfk_1` FOREIGN KEY (`CI`) REFERENCES `Operario` (`CI`),
  CONSTRAINT `Reporta_Centro_ibfk_2` FOREIGN KEY (`ID`) REFERENCES `Centro` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Reporta_Centro`
--

LOCK TABLES `Reporta_Centro` WRITE;
/*!40000 ALTER TABLE `Reporta_Centro` DISABLE KEYS */;
INSERT INTO `Reporta_Centro` VALUES ('23456789',1,180);
/*!40000 ALTER TABLE `Reporta_Centro` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Ruta`
--

DROP TABLE IF EXISTS `Ruta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Ruta` (
  `Id_Contenedor` int NOT NULL,
  `Id_Poligono` int DEFAULT NULL,
  `Trayecto` text,
  `Cantidad_Contenedores` int DEFAULT NULL,
  PRIMARY KEY (`Id_Contenedor`),
  KEY `Id_Poligono` (`Id_Poligono`),
  CONSTRAINT `Ruta_ibfk_1` FOREIGN KEY (`Id_Contenedor`) REFERENCES `Contenedor` (`Id_Contenedor`),
  CONSTRAINT `Ruta_ibfk_2` FOREIGN KEY (`Id_Poligono`) REFERENCES `Poligono` (`Id_Poligono`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Ruta`
--

LOCK TABLES `Ruta` WRITE;
/*!40000 ALTER TABLE `Ruta` DISABLE KEYS */;
INSERT INTO `Ruta` VALUES (1,1,'4412,4413,4414,',3),(2,1,'4415,4416,4417,',3),(3,1,'4418,4419,4420,',3);
/*!40000 ALTER TABLE `Ruta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Termina`
--

DROP TABLE IF EXISTS `Termina`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Termina` (
  `ID` int NOT NULL,
  `Id_Contenedor` int NOT NULL,
  PRIMARY KEY (`ID`,`Id_Contenedor`),
  KEY `Id_Contenedor` (`Id_Contenedor`),
  CONSTRAINT `Termina_ibfk_1` FOREIGN KEY (`ID`) REFERENCES `Centro` (`ID`),
  CONSTRAINT `Termina_ibfk_2` FOREIGN KEY (`Id_Contenedor`) REFERENCES `Contenedor` (`Id_Contenedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Termina`
--

LOCK TABLES `Termina` WRITE;
/*!40000 ALTER TABLE `Termina` DISABLE KEYS */;
INSERT INTO `Termina` VALUES (1,1);
/*!40000 ALTER TABLE `Termina` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Tiene`
--

DROP TABLE IF EXISTS `Tiene`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Tiene` (
  `Id_Metodo` int NOT NULL,
  `ID` int NOT NULL,
  PRIMARY KEY (`Id_Metodo`,`ID`),
  KEY `ID` (`ID`),
  CONSTRAINT `Tiene_ibfk_1` FOREIGN KEY (`Id_Metodo`) REFERENCES `Metodo` (`Id_Metodo`),
  CONSTRAINT `Tiene_ibfk_2` FOREIGN KEY (`ID`) REFERENCES `CentroDeAcopio` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Tiene`
--

LOCK TABLES `Tiene` WRITE;
/*!40000 ALTER TABLE `Tiene` DISABLE KEYS */;
INSERT INTO `Tiene` VALUES (1,1);
/*!40000 ALTER TABLE `Tiene` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Usa`
--

DROP TABLE IF EXISTS `Usa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Usa` (
  `CI` char(8) NOT NULL,
  `Matricula` varchar(10) NOT NULL,
  PRIMARY KEY (`CI`,`Matricula`),
  KEY `Matricula` (`Matricula`),
  CONSTRAINT `Usa_ibfk_1` FOREIGN KEY (`CI`) REFERENCES `Cuadrilla` (`CI`),
  CONSTRAINT `Usa_ibfk_2` FOREIGN KEY (`Matricula`) REFERENCES `Camion` (`Matricula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Usa`
--

LOCK TABLES `Usa` WRITE;
/*!40000 ALTER TABLE `Usa` DISABLE KEYS */;
INSERT INTO `Usa` VALUES ('45678901','SBI1234');
/*!40000 ALTER TABLE `Usa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Usuario`
--

DROP TABLE IF EXISTS `Usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Usuario` (
  `ci` char(8) NOT NULL,
  `nombre1` varchar(50) NOT NULL,
  `nombre2` varchar(50) DEFAULT NULL,
  `apellido1` varchar(50) NOT NULL,
  `apellido2` varchar(50) DEFAULT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` varchar(30) NOT NULL,
  `correo_e` varchar(100) NOT NULL,
  PRIMARY KEY (`ci`),
  UNIQUE KEY `correo_e` (`correo_e`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Usuario`
--

LOCK TABLES `Usuario` WRITE;
/*!40000 ALTER TABLE `Usuario` DISABLE KEYS */;
INSERT INTO `Usuario` VALUES ('12345678','Juan','Ignacio','Da Rosa','Alonso','1234','Vecino','juan@gmail.com'),('23456789','Felipe',NULL,'Bellas','Salvo','1234','Operario','felipe@gmail.com'),('34567890','Adrian','Alfonso','Bolivar','Chiarelli','1234','Administrador','adrian@gmail.com'),('45678901','John',NULL,'Doe','Smith','1234','Operario','john@gmail.com');
/*!40000 ALTER TABLE `Usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Vecino`
--

DROP TABLE IF EXISTS `Vecino`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Vecino` (
  `CI` char(8) NOT NULL,
  PRIMARY KEY (`CI`),
  CONSTRAINT `Vecino_ibfk_1` FOREIGN KEY (`CI`) REFERENCES `Usuario` (`ci`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Vecino`
--

LOCK TABLES `Vecino` WRITE;
/*!40000 ALTER TABLE `Vecino` DISABLE KEYS */;
INSERT INTO `Vecino` VALUES ('12345678');
/*!40000 ALTER TABLE `Vecino` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Vecino_Tel`
--

DROP TABLE IF EXISTS `Vecino_Tel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Vecino_Tel` (
  `CI` char(8) NOT NULL,
  `Tel` varchar(20) NOT NULL,
  PRIMARY KEY (`CI`,`Tel`),
  CONSTRAINT `Vecino_Tel_ibfk_1` FOREIGN KEY (`CI`) REFERENCES `Vecino` (`CI`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Vecino_Tel`
--

LOCK TABLES `Vecino_Tel` WRITE;
/*!40000 ALTER TABLE `Vecino_Tel` DISABLE KEYS */;
INSERT INTO `Vecino_Tel` VALUES ('12345678','098654321'),('12345678','099123456');
/*!40000 ALTER TABLE `Vecino_Tel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Vertedero`
--

DROP TABLE IF EXISTS `Vertedero`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Vertedero` (
  `ID` int NOT NULL,
  PRIMARY KEY (`ID`),
  CONSTRAINT `Vertedero_ibfk_1` FOREIGN KEY (`ID`) REFERENCES `Centro` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Vertedero`
--

LOCK TABLES `Vertedero` WRITE;
/*!40000 ALTER TABLE `Vertedero` DISABLE KEYS */;
INSERT INTO `Vertedero` VALUES (1);
/*!40000 ALTER TABLE `Vertedero` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-09 13:08:21
