SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS sigeru;
CREATE DATABASE sigeru CHARACTER SET utf8mb4;
USE sigeru;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuario (
  ci char(8) NOT NULL,
  nombre1 varchar(50) NOT NULL,
  nombre2 varchar(50) DEFAULT NULL,
  apellido1 varchar(50) NOT NULL,
  apellido2 varchar(50) DEFAULT NULL,
  contrasena varchar(255) NOT NULL,
  rol varchar(30) NOT NULL,
  correo_e varchar(100) NOT NULL,
  PRIMARY KEY (ci),
  UNIQUE KEY correo_e (correo_e)
);

CREATE TABLE camion (
  Matricula varchar(10) NOT NULL,
  Estado varchar(30) DEFAULT NULL,
  Tipo varchar(50) NOT NULL DEFAULT 'Residuos mezclados',
  PRIMARY KEY (Matricula),
  CONSTRAINT chk_camion_tipo CHECK (Tipo IN ('Residuos mezclados', 'Reciclables'))
);

CREATE TABLE poligono (
  Id_Poligono int(11) NOT NULL,
  PRIMARY KEY (Id_Poligono)
);

CREATE TABLE poligono_vertices (
  Id_Poligono int(11) NOT NULL,
  Orden int(11) NOT NULL,
  Latitud decimal(10,7) DEFAULT NULL,
  Longitud decimal(10,7) DEFAULT NULL,
  PRIMARY KEY (Id_Poligono, Orden),
  FOREIGN KEY (Id_Poligono) REFERENCES poligono (Id_Poligono)
);

-- Cada contenedor pertenece a la zona (polígono) en la que está ubicado.
-- Si no cae dentro de ninguna zona queda en NULL y, cuando se cree un
-- polígono que lo abarque, el sistema lo relaciona automáticamente.
CREATE TABLE contenedor (
  Id_Contenedor int(11) NOT NULL,
  Tipo varchar(50) DEFAULT NULL,
  Estado varchar(30) DEFAULT NULL,
  Latitud decimal(10,7) DEFAULT NULL,
  Longitud decimal(10,7) DEFAULT NULL,
  Id_Poligono int(11) DEFAULT NULL,
  PRIMARY KEY (Id_Contenedor),
  KEY Id_Poligono (Id_Poligono),
  FOREIGN KEY (Id_Poligono) REFERENCES poligono (Id_Poligono) ON DELETE SET NULL
);

CREATE TABLE incidencia (
  Id_Incidencia int(11) NOT NULL AUTO_INCREMENT,
  Tipo varchar(50) DEFAULT NULL,
  Estado varchar(30) DEFAULT NULL,
  Id_Contenedor int(11) DEFAULT NULL,
  Foto varchar(255) DEFAULT NULL,
  PRIMARY KEY (Id_Incidencia),
  KEY Id_Contenedor (Id_Contenedor),
  FOREIGN KEY (Id_Contenedor) REFERENCES contenedor (Id_Contenedor)
);

CREATE TABLE access_token (
  token char(64) NOT NULL,
  ci char(8) NOT NULL,
  fecha_creado datetime NOT NULL DEFAULT current_timestamp(),
  fecha_vencimiento datetime NOT NULL,
  PRIMARY KEY (token),
  KEY ci (ci),
  FOREIGN KEY (ci) REFERENCES usuario (ci) ON DELETE CASCADE
);

CREATE TABLE administrador (
  CI char(8) NOT NULL,
  PRIMARY KEY (CI),
  FOREIGN KEY (CI) REFERENCES usuario (ci) ON DELETE CASCADE
);

CREATE TABLE cuadrilla (
  CI char(8) NOT NULL,
  PRIMARY KEY (CI),
  FOREIGN KEY (CI) REFERENCES usuario (ci) ON DELETE CASCADE
);

CREATE TABLE operario (
  CI char(8) NOT NULL,
  PRIMARY KEY (CI),
  FOREIGN KEY (CI) REFERENCES usuario (ci) ON DELETE CASCADE
);

CREATE TABLE vecino (
  CI char(8) NOT NULL,
  PRIMARY KEY (CI),
  FOREIGN KEY (CI) REFERENCES usuario (ci) ON DELETE CASCADE
);

CREATE TABLE vecino_tel (
  CI char(8) NOT NULL,
  Tel varchar(20) NOT NULL,
  PRIMARY KEY (CI, Tel),
  FOREIGN KEY (CI) REFERENCES vecino (CI) ON DELETE CASCADE
);

CREATE TABLE centro (
  ID int(11) NOT NULL,
  Servicio varchar(100) DEFAULT NULL,
  ContAlmacenados int(11) DEFAULT NULL,
  Capacidad int(11) DEFAULT NULL,
  CamAlmacenados int(11) DEFAULT NULL,
  PRIMARY KEY (ID)
);

CREATE TABLE centrodeacopio (
  ID int(11) NOT NULL,
  PRIMARY KEY (ID),
  FOREIGN KEY (ID) REFERENCES centro (ID)
);

CREATE TABLE maquinaria (
  Id_Maquinaria int(11) NOT NULL AUTO_INCREMENT,
  ID int(11) NOT NULL,
  Nombre varchar(100) NOT NULL,
  Cantidad int(11) NOT NULL DEFAULT 1,
  Estado varchar(30) NOT NULL DEFAULT 'Operativa',
  PRIMARY KEY (Id_Maquinaria),
  KEY ID (ID),
  FOREIGN KEY (ID) REFERENCES centro (ID)
);

CREATE TABLE vertedero (
  ID int(11) NOT NULL,
  PRIMARY KEY (ID),
  FOREIGN KEY (ID) REFERENCES centro (ID)
);

CREATE TABLE metodo (
  Id_Metodo int(11) NOT NULL,
  PRIMARY KEY (Id_Metodo)
);

CREATE TABLE ruta (
  Id_Ruta int(11) NOT NULL AUTO_INCREMENT,
  Id_Poligono int(11) NOT NULL,
  Tipo varchar(30) NOT NULL,
  Matricula varchar(10) DEFAULT NULL,
  Estado varchar(20) NOT NULL DEFAULT 'Pendiente',
  Fecha_Creacion datetime NOT NULL DEFAULT current_timestamp(),
  Fecha_Fin datetime DEFAULT NULL,
  PRIMARY KEY (Id_Ruta),
  KEY Id_Poligono (Id_Poligono),
  KEY Matricula (Matricula),
  FOREIGN KEY (Id_Poligono) REFERENCES poligono (Id_Poligono) ON DELETE CASCADE,
  FOREIGN KEY (Matricula) REFERENCES camion (Matricula) ON DELETE SET NULL,
  CONSTRAINT chk_ruta_tipo CHECK (Tipo IN ('Residuos mezclados', 'Reciclables', 'Incidencias')),
  CONSTRAINT chk_ruta_estado CHECK (Estado IN ('Pendiente', 'En curso', 'Finalizada'))
);

CREATE TABLE ruta_parada (
  Id_Ruta int(11) NOT NULL,
  Orden int(11) NOT NULL,
  Id_Contenedor int(11) NOT NULL,
  Estado varchar(20) NOT NULL DEFAULT 'Pendiente',
  Peso decimal(10,2) DEFAULT NULL,
  Fecha_Recoleccion datetime DEFAULT NULL,
  PRIMARY KEY (Id_Ruta, Orden),
  KEY Id_Contenedor (Id_Contenedor),
  FOREIGN KEY (Id_Ruta) REFERENCES ruta (Id_Ruta) ON DELETE CASCADE,
  FOREIGN KEY (Id_Contenedor) REFERENCES contenedor (Id_Contenedor) ON DELETE CASCADE,
  CONSTRAINT chk_parada_estado CHECK (Estado IN ('Pendiente', 'Recogido'))
);

CREATE TABLE al (
  Id_Incidencia int(11) NOT NULL,
  Id_Contenedor int(11) NOT NULL,
  PRIMARY KEY (Id_Incidencia, Id_Contenedor),
  KEY Id_Contenedor (Id_Contenedor),
  FOREIGN KEY (Id_Incidencia) REFERENCES incidencia (Id_Incidencia),
  FOREIGN KEY (Id_Contenedor) REFERENCES contenedor (Id_Contenedor)
);

CREATE TABLE circula (
  CI char(8) NOT NULL,
  Id_Ruta int(11) NOT NULL,
  PRIMARY KEY (CI, Id_Ruta),
  KEY Id_Ruta (Id_Ruta),
  FOREIGN KEY (CI) REFERENCES cuadrilla (CI) ON DELETE CASCADE,
  FOREIGN KEY (Id_Ruta) REFERENCES ruta (Id_Ruta) ON DELETE CASCADE
);

CREATE TABLE recoge (
  Matricula varchar(10) NOT NULL,
  Id_Poligono int(11) NOT NULL,
  Tipo_Ruta varchar(30) NOT NULL,
  PRIMARY KEY (Matricula),
  KEY Id_Poligono (Id_Poligono),
  FOREIGN KEY (Matricula) REFERENCES camion (Matricula) ON DELETE CASCADE,
  FOREIGN KEY (Id_Poligono) REFERENCES poligono (Id_Poligono) ON DELETE CASCADE,
  CONSTRAINT chk_recoge_tipo CHECK (Tipo_Ruta IN ('Residuos mezclados', 'Reciclables', 'Incidencias'))
);

CREATE TABLE usa (
  CI char(8) NOT NULL,
  Matricula varchar(10) NOT NULL,
  PRIMARY KEY (CI),
  UNIQUE KEY Matricula (Matricula),
  FOREIGN KEY (CI) REFERENCES cuadrilla (CI) ON DELETE CASCADE,
  FOREIGN KEY (Matricula) REFERENCES camion (Matricula) ON DELETE CASCADE
);

CREATE TABLE reporta (
  CI char(8) NOT NULL,
  Id_Incidencia int(11) NOT NULL,
  PRIMARY KEY (CI, Id_Incidencia),
  KEY Id_Incidencia (Id_Incidencia),
  FOREIGN KEY (CI) REFERENCES usuario (ci) ON DELETE CASCADE,
  FOREIGN KEY (Id_Incidencia) REFERENCES incidencia (Id_Incidencia)
);

CREATE TABLE reporta_centro (
  CI char(8) NOT NULL,
  ID int(11) NOT NULL,
  Cant_Residuos int(11) DEFAULT NULL,
  PRIMARY KEY (CI, ID),
  KEY ID (ID),
  FOREIGN KEY (CI) REFERENCES operario (CI),
  FOREIGN KEY (ID) REFERENCES centro (ID)
);

CREATE TABLE termina (
  ID int(11) NOT NULL,
  Id_Contenedor int(11) NOT NULL,
  PRIMARY KEY (ID, Id_Contenedor),
  KEY Id_Contenedor (Id_Contenedor),
  FOREIGN KEY (ID) REFERENCES centro (ID),
  FOREIGN KEY (Id_Contenedor) REFERENCES contenedor (Id_Contenedor)
);

CREATE TABLE tiene (
  Id_Metodo int(11) NOT NULL,
  ID int(11) NOT NULL,
  PRIMARY KEY (Id_Metodo, ID),
  KEY ID (ID),
  FOREIGN KEY (Id_Metodo) REFERENCES metodo (Id_Metodo),
  FOREIGN KEY (ID) REFERENCES centrodeacopio (ID)
);

CREATE TABLE registro_camion (
  Id_Registro int(11) NOT NULL AUTO_INCREMENT,
  Matricula varchar(10) NOT NULL,
  CI char(8) NOT NULL,
  ID int(11) NOT NULL,
  Fecha datetime NOT NULL DEFAULT current_timestamp(),
  Tipo_Carga varchar(50) NOT NULL,
  Cantidad_Carga decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (Id_Registro),
  KEY Matricula (Matricula),
  KEY CI (CI),
  KEY ID (ID),
  FOREIGN KEY (Matricula) REFERENCES camion (Matricula),
  FOREIGN KEY (CI) REFERENCES operario (CI),
  FOREIGN KEY (ID) REFERENCES centro (ID)
);

INSERT INTO usuario (ci, nombre1, nombre2, apellido1, apellido2, contrasena, rol, correo_e) VALUES
('12341234', 'Administrador', NULL, 'ejemplo', NULL, '$2b$10$y4cARLVaeb5vZ7LmSMgFvuAXk8KgIiSuimZQUGBjIVfd/Zoi0CSgC', 'Administrador', 'admin@gmail.com'),
('44444444', 'Vecino', NULL, 'ejmelp', NULL, '$2b$10$y4cARLVaeb5vZ7LmSMgFvuAXk8KgIiSuimZQUGBjIVfd/Zoi0CSgC', 'Vecino', 'vecino@gmail.com'),
('55555555', 'Operario', NULL, 'Ejemplo', NULL, '$2b$10$y4cARLVaeb5vZ7LmSMgFvuAXk8KgIiSuimZQUGBjIVfd/Zoi0CSgC', 'Operario', 'operario@gmail.com'),
('66666666', 'Cuadrilla', NULL, 'Uno', NULL, '$2b$10$y4cARLVaeb5vZ7LmSMgFvuAXk8KgIiSuimZQUGBjIVfd/Zoi0CSgC', 'Cuadrilla', 'cuadrilla@gmail.com'),
('77777777', 'Cuadrilla', NULL, 'Dos', NULL, '$2b$10$y4cARLVaeb5vZ7LmSMgFvuAXk8KgIiSuimZQUGBjIVfd/Zoi0CSgC', 'Cuadrilla', 'cuadrilla2@gmail.com');

INSERT INTO administrador (CI) VALUES
('12341234');

INSERT INTO vecino (CI) VALUES
('44444444');

INSERT INTO operario (CI) VALUES
('55555555');

INSERT INTO cuadrilla (CI) VALUES
('66666666'),
('77777777');

INSERT INTO vecino_tel (CI, Tel) VALUES
('44444444', '091632224');

INSERT INTO poligono (Id_Poligono) VALUES
(2),
(3),
(4);

INSERT INTO poligono_vertices (Id_Poligono, Orden, Latitud, Longitud) VALUES
(2, 1, -34.8975120, -56.1645985),
(2, 2, -34.8962448, -56.1613798),
(2, 3, -34.8961040, -56.1591911),
(2, 4, -34.8979695, -56.1581182),
(2, 5, -34.9027563, -56.1613798),
(2, 6, -34.9022988, -56.1641693),
(3, 1, -34.9074020, -56.1596847),
(3, 2, -34.9058535, -56.1633539),
(3, 3, -34.9028267, -56.1613369),
(3, 4, -34.9031611, -56.1560369),
(3, 5, -34.9090737, -56.1535263),
(4, 1, -34.9081763, -56.1956263),
(4, 2, -34.9043753, -56.1960340),
(4, 3, -34.9038826, -56.1892962),
(4, 4, -34.9077012, -56.1888885);

INSERT INTO contenedor (Id_Contenedor, Tipo, Estado, Latitud, Longitud, Id_Poligono) VALUES
(1, 'Residuos mezclados', 'Nuevo', -34.8970236, -56.1618465, 2),
(2, 'Reciclables', 'Nuevo', -34.9007677, -56.1628137, 2),
(4, 'Residuos mezclados', 'Nuevo', -34.9041993, -56.1605104, 3),
(5, 'Reciclables', 'Nuevo', -34.9059371, -56.1914033, 4),
(6, 'Residuos mezclados', 'Nuevo', -34.9006021, -56.1636210, 2),
(7, 'Residuos mezclados', 'Nuevo', -34.8984261, -56.1641291, 2),
(8, 'Reciclables', 'Nuevo', -34.8991915, -56.1622287, 2),
(9, 'Reciclables', 'Nuevo', -34.9023705, -56.1613102, 2),
(10, 'Residuos mezclados', 'Nuevo', -34.9022916, -56.1640107, 2),
(11, 'Reciclables', 'Nuevo', -34.9064217, -56.1552279, 3),
(12, 'Reciclables', 'Nuevo', -34.9054685, -56.1594555, 3),
(13, 'Residuos mezclados', 'Nuevo', -34.9037108, -56.1605077, 3),
(14, 'Reciclables', 'Nuevo', -34.9071466, -56.1553333, 3),
(15, 'Residuos mezclados', 'Nuevo', -34.9074003, -56.1918782, 4),
(16, 'Residuos mezclados', 'Nuevo', -34.9054330, -56.1933730, 4),
(17, 'Reciclables', 'Nuevo', -34.9058244, -56.1955853, 4);

INSERT INTO incidencia (Id_Incidencia, Tipo, Estado, Id_Contenedor, Foto) VALUES
(1, 'Contenedor lleno', 'Pendiente', 5, 'incidencia_6aa2d0c5ca7e0.jpg'),
(2, 'Contenedor roto', 'Pendiente', 2, 'incidencia_6aa2e3790d573.jpg');

INSERT INTO camion (Matricula, Estado, Tipo) VALUES
('SBI1234', 'Disponible', 'Residuos mezclados'),
('SBJ5678', 'Disponible', 'Reciclables');

INSERT INTO recoge (Matricula, Id_Poligono, Tipo_Ruta) VALUES
('SBI1234', 2, 'Residuos mezclados'),
('SBJ5678', 3, 'Reciclables');

INSERT INTO usa (CI, Matricula) VALUES
('66666666', 'SBI1234'),
('77777777', 'SBJ5678');

INSERT INTO centro (ID, Servicio, ContAlmacenados, Capacidad, CamAlmacenados) VALUES
(1, 'Cent. Felipe Cardozo', 250, 600, 12);

INSERT INTO centrodeacopio (ID) VALUES
(1);

INSERT INTO maquinaria (ID, Nombre, Cantidad, Estado) VALUES
(1, 'Gato hidráulico', 2, 'Operativa');

INSERT INTO registro_camion (Matricula, CI, ID, Tipo_Carga, Cantidad_Carga) VALUES
('SBI1234', '55555555', 1, 'Orgánico', 350.00);
