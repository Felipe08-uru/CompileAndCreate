CREATE DATABASE sigeru;
USE sigeru;

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
  Tipo varchar(50) DEFAULT NULL,
  PRIMARY KEY (Matricula)
);

CREATE TABLE contenedor (
  Id_Contenedor int(11) NOT NULL,
  Tipo varchar(50) DEFAULT NULL,
  Estado varchar(30) DEFAULT NULL,
  Latitud decimal(10,7) DEFAULT NULL,
  Longitud decimal(10,7) DEFAULT NULL,
  PRIMARY KEY (Id_Contenedor)
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

CREATE TABLE access_token (
  token char(64) NOT NULL,
  ci char(8) NOT NULL,
  fecha_creado datetime NOT NULL DEFAULT current_timestamp(),
  fecha_vencimiento datetime NOT NULL,
  PRIMARY KEY (token),
  KEY ci (ci),
  FOREIGN KEY (ci) REFERENCES usuario (ci)
);

CREATE TABLE administrador (
  CI char(8) NOT NULL,
  PRIMARY KEY (CI),
  FOREIGN KEY (CI) REFERENCES usuario (ci)
);

CREATE TABLE cuadrilla (
  CI char(8) NOT NULL,
  PRIMARY KEY (CI),
  FOREIGN KEY (CI) REFERENCES usuario (ci)
);

CREATE TABLE operario (
  CI char(8) NOT NULL,
  PRIMARY KEY (CI),
  FOREIGN KEY (CI) REFERENCES usuario (ci)
);

CREATE TABLE vecino (
  CI char(8) NOT NULL,
  PRIMARY KEY (CI),
  FOREIGN KEY (CI) REFERENCES usuario (ci)
);

CREATE TABLE vecino_tel (
  CI char(8) NOT NULL,
  Tel varchar(20) NOT NULL,
  PRIMARY KEY (CI, Tel),
  FOREIGN KEY (CI) REFERENCES vecino (CI)
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
  Id_Contenedor int(11) NOT NULL,
  Id_Poligono int(11) DEFAULT NULL,
  Trayecto text DEFAULT NULL,
  Cantidad_Contenedores int(11) DEFAULT NULL,
  PRIMARY KEY (Id_Contenedor),
  KEY Id_Poligono (Id_Poligono),
  FOREIGN KEY (Id_Contenedor) REFERENCES contenedor (Id_Contenedor),
  FOREIGN KEY (Id_Poligono) REFERENCES poligono (Id_Poligono)
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
  Id_Contenedor int(11) NOT NULL,
  PRIMARY KEY (CI, Id_Contenedor),
  KEY Id_Contenedor (Id_Contenedor),
  FOREIGN KEY (CI) REFERENCES cuadrilla (CI),
  FOREIGN KEY (Id_Contenedor) REFERENCES ruta (Id_Contenedor)
);

CREATE TABLE recoge (
  Id_Contenedor int(11) NOT NULL,
  Matricula varchar(10) NOT NULL,
  PRIMARY KEY (Id_Contenedor, Matricula),
  KEY Matricula (Matricula),
  FOREIGN KEY (Id_Contenedor) REFERENCES contenedor (Id_Contenedor),
  FOREIGN KEY (Matricula) REFERENCES camion (Matricula)
);

CREATE TABLE reporta (
  CI char(8) NOT NULL,
  Id_Incidencia int(11) NOT NULL,
  PRIMARY KEY (CI, Id_Incidencia),
  KEY Id_Incidencia (Id_Incidencia),
  FOREIGN KEY (CI) REFERENCES usuario (ci),
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

CREATE TABLE usa (
  CI char(8) NOT NULL,
  Matricula varchar(10) NOT NULL,
  PRIMARY KEY (CI, Matricula),
  KEY Matricula (Matricula),
  FOREIGN KEY (CI) REFERENCES cuadrilla (CI),
  FOREIGN KEY (Matricula) REFERENCES camion (Matricula)
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
('55555555', 'Operario', NULL, 'Ejemplo', NULL, '$2b$10$y4cARLVaeb5vZ7LmSMgFvuAXk8KgIiSuimZQUGBjIVfd/Zoi0CSgC', 'Operario', 'operario@gmail.com');

INSERT INTO administrador (CI) VALUES
('12341234');

INSERT INTO vecino (CI) VALUES
('44444444');

INSERT INTO operario (CI) VALUES
('55555555');

INSERT INTO vecino_tel (CI, Tel) VALUES
('44444444', '091632224');

INSERT INTO contenedor (Id_Contenedor, Tipo, Estado, Latitud, Longitud) VALUES
(1, 'Residuos mezclados', 'Nuevo', -34.8970236, -56.1618465),
(2, 'Reciclables', 'Nuevo', -34.9007677, -56.1628137),
(4, 'Residuos mezclados', 'Nuevo', -34.9041993, -56.1605104),
(5, 'Reciclables', 'Nuevo', -34.9059371, -56.1914033);

INSERT INTO incidencia (Id_Incidencia, Tipo, Estado, Id_Contenedor, Foto) VALUES
(1, 'Contenedor lleno', 'Pendiente', 5, 'incidencia_6aa2d0c5ca7e0.jpg'),
(2, 'Contenedor roto', 'Pendiente', 2, 'incidencia_6aa2e3790d573.jpg');

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

INSERT INTO camion (Matricula, Estado, Tipo) VALUES
('SBI1234', 'Disponible', 'Compactador'),
('SBJ5678', 'Disponible', 'Volqueta');

INSERT INTO centro (ID, Servicio, ContAlmacenados, Capacidad, CamAlmacenados) VALUES
(1, 'Cent. Felipe Cardozo', 250, 600, 12);

INSERT INTO centrodeacopio (ID) VALUES
(1);

INSERT INTO maquinaria (ID, Nombre, Cantidad, Estado) VALUES
(1, 'Gato hidráulico', 2, 'Operativa');

INSERT INTO registro_camion (Matricula, CI, ID, Tipo_Carga, Cantidad_Carga) VALUES
('SBI1234', '55555555', 1, 'Orgánico', 350.00);
