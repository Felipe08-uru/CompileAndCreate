CREATE DATABASE sigeru;
USE sigeru;

CREATE TABLE Usuario (
    ci CHAR(8) PRIMARY KEY,
    nombre1 VARCHAR(50) NOT NULL,
    nombre2 VARCHAR(50),
    apellido1 VARCHAR(50) NOT NULL,
    apellido2 VARCHAR(50),
    contrasena VARCHAR(255) NOT NULL,
    rol VARCHAR(30) NOT NULL,
    correo_c VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE Vecino (
    CI CHAR(8) PRIMARY KEY,
    FOREIGN KEY (CI) REFERENCES Usuario(CI)
);

CREATE TABLE Vecino_Tel (
    CI CHAR(8),
    Tel VARCHAR(20),
    PRIMARY KEY (CI, Tel),
    FOREIGN KEY (CI) REFERENCES Vecino(CI)
);

CREATE TABLE Operario (
    CI CHAR(8) PRIMARY KEY,
    FOREIGN KEY (CI) REFERENCES Usuario(CI)
);

CREATE TABLE Administrador (
    CI CHAR(8) PRIMARY KEY,
    FOREIGN KEY (CI) REFERENCES Usuario(CI)
);

CREATE TABLE Cuadrilla (
    CI CHAR(8) PRIMARY KEY,
    FOREIGN KEY (CI) REFERENCES Usuario(CI)
);

CREATE TABLE Camion (
    Matricula VARCHAR(10) PRIMARY KEY,
    Estado VARCHAR(30),
    Tipo VARCHAR(50)
);

CREATE TABLE Usa (
    CI CHAR(8),
    Matricula VARCHAR(10),
    PRIMARY KEY (CI, Matricula),
    FOREIGN KEY (CI) REFERENCES Cuadrilla(CI),
    FOREIGN KEY (Matricula) REFERENCES Camion(Matricula)
);

CREATE TABLE Incidencia (
    Id_Incidencia INT PRIMARY KEY,
    Tipo VARCHAR(50),
    Estado VARCHAR(30)
);

CREATE TABLE Reporta (
    CI CHAR(8),
    Id_Incidencia INT,
    PRIMARY KEY (CI, Id_Incidencia),
    FOREIGN KEY (CI) REFERENCES Usuario(CI),
    FOREIGN KEY (Id_Incidencia) REFERENCES Incidencia(Id_Incidencia)
);

CREATE TABLE Contenedor (
    Id_Contenedor INT PRIMARY KEY,
    Tipo VARCHAR(50),
    Estado VARCHAR(30),
    Latitud DECIMAL(10,7),
    Longitud DECIMAL(10,7)
);

CREATE TABLE Recoge (
    Id_Contenedor INT,
    Matricula VARCHAR(10),
    PRIMARY KEY (Id_Contenedor, Matricula),
    FOREIGN KEY (Id_Contenedor) REFERENCES Contenedor(Id_Contenedor),
    FOREIGN KEY (Matricula) REFERENCES Camion(Matricula)
);

CREATE TABLE Al (
    Id_Incidencia INT,
    Id_Contenedor INT,
    PRIMARY KEY (Id_Incidencia, Id_Contenedor),
    FOREIGN KEY (Id_Incidencia) REFERENCES Incidencia(Id_Incidencia),
    FOREIGN KEY (Id_Contenedor) REFERENCES Contenedor(Id_Contenedor)
);

CREATE TABLE Poligono (
    Id_Poligono INT PRIMARY KEY
);

CREATE TABLE Poligono_Vertices (
    Id_Poligono INT,
    Orden INT,
    Latitud DECIMAL(10,7),
    Longitud DECIMAL(10,7),
    PRIMARY KEY (Id_Poligono, Orden),
    FOREIGN KEY (Id_Poligono) REFERENCES Poligono(Id_Poligono)
);

CREATE TABLE Ruta (
    Id_Contenedor INT PRIMARY KEY,
    Id_Poligono INT,
    Trayecto TEXT,
    Cantidad_Contenedores INT,
    FOREIGN KEY (Id_Contenedor) REFERENCES Contenedor(Id_Contenedor),
    FOREIGN KEY (Id_Poligono) REFERENCES Poligono(Id_Poligono)
);

CREATE TABLE Circula (
    CI CHAR(8),
    Id_Contenedor INT,
    PRIMARY KEY (CI, Id_Contenedor),
    FOREIGN KEY (CI) REFERENCES Cuadrilla(CI),
    FOREIGN KEY (Id_Contenedor) REFERENCES Ruta(Id_Contenedor)
);

CREATE TABLE Centro (
    ID INT PRIMARY KEY,
    Servicio VARCHAR(100),
    ContAlmacenados INT,
    Capacidad INT,
    CamAlmacenados INT
);

CREATE TABLE Centro_Herramientas (
    ID INT,
    Herramienta VARCHAR(100),
    PRIMARY KEY (ID, Herramienta),
    FOREIGN KEY (ID) REFERENCES Centro(ID)
);

CREATE TABLE Termina (
    ID INT,
    Id_Contenedor INT,
    PRIMARY KEY (ID, Id_Contenedor),
    FOREIGN KEY (ID) REFERENCES Centro(ID),
    FOREIGN KEY (Id_Contenedor) REFERENCES Contenedor(Id_Contenedor)
);

CREATE TABLE Reporta_Centro (
    CI CHAR(8),
    ID INT,
    Cant_Residuos INT,
    PRIMARY KEY (CI, ID),
    FOREIGN KEY (CI) REFERENCES Operario(CI),
    FOREIGN KEY (ID) REFERENCES Centro(ID)
);

CREATE TABLE Vertedero (
    ID INT PRIMARY KEY,
    FOREIGN KEY (ID) REFERENCES Centro(ID)
);

CREATE TABLE CentroDeAcopio (
    ID INT PRIMARY KEY,
    FOREIGN KEY (ID) REFERENCES Centro(ID)
);

CREATE TABLE Metodo (
    Id_Metodo INT PRIMARY KEY
);

CREATE TABLE Tiene (
    Id_Metodo INT,
    ID INT,
    PRIMARY KEY (Id_Metodo, ID),
    FOREIGN KEY (Id_Metodo) REFERENCES Metodo(Id_Metodo),
    FOREIGN KEY (ID) REFERENCES CentroDeAcopio(ID)
);

INSERT INTO Usuario VALUES
('12345678','Juan','Ignacio','Da Rosa','Alonso','1234','Vecino','juan@gmail.com'),
('23456789','Felipe',NULL,'Bellas','Salvo','1234','Operario','felipe@gmail.com'),
('34567890','Adrian','Alfonso','Bolivar','Chiarelli','1234','Administrador','adrian@gmail.com'),
('45678901','John',NULL,'Doe','Smith','1234','Operario','john@gmail.com');

INSERT INTO Vecino VALUES
('12345678');

INSERT INTO Vecino_Tel VALUES
('12345678','099123456'),
('12345678','098654321');

INSERT INTO Operario VALUES
('23456789');

INSERT INTO Administrador VALUES
('34567890');

INSERT INTO Cuadrilla VALUES
('45678901');

INSERT INTO Camion VALUES
('SBI1234','Disponible','Compactador');

INSERT INTO Usa VALUES
('45678901','SBI1234');

INSERT INTO Incidencia VALUES
(1,'Contenedor desbordado','Pendiente');

INSERT INTO Reporta VALUES
('12345678',1);

INSERT INTO Contenedor VALUES
(1,'Orgánico','Lleno',-34.9058300,-56.1916200),
(2,'Reciclable','Vacío',-34.9062500,-56.1904500),
(3,'Orgánico','Medio',-34.9049000,-56.1897000);

INSERT INTO Recoge VALUES
(1,'SBI1234'),
(2,'SBI1234'),
(3,'SBI1234');

INSERT INTO Al VALUES
(1,1),
(1,2),
(1,3);

INSERT INTO Poligono VALUES
(1);

INSERT INTO Poligono_Vertices VALUES
(1,1,-34.9068000,-56.1926000),
(1,2,-34.9068000,-56.1887000),
(1,3,-34.9037000,-56.1887000),
(1,4,-34.9037000,-56.1926000);

INSERT INTO Ruta VALUES
(1,1,'4412,4413,4414,',3),
(2,1,'4415,4416,4417,',3),
(3,1,'4418,4419,4420,',3);

INSERT INTO Circula VALUES
('45678901',1);

INSERT INTO Centro VALUES
(1,'Clasificación',250,600,12);

INSERT INTO Centro_Herramientas VALUES
(1,'Pala'),
(1,'Escoba'),
(1,'Carretilla'),
(1,'Hidrolavadora');

INSERT INTO Termina VALUES
(1,1);

INSERT INTO Reporta_Centro VALUES
('23456789',1,180);

INSERT INTO Vertedero VALUES
(1);

INSERT INTO CentroDeAcopio VALUES
(1);

INSERT INTO Metodo VALUES
(1);

INSERT INTO Tiene VALUES
(1,1);
