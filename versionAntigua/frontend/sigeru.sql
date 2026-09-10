CREATE TABLE Usuario (
    CI CHAR(8) PRIMARY KEY,
    Nombre1 VARCHAR(50) NOT NULL,
    Nombre2 VARCHAR(50),
    Apellido1 VARCHAR(50) NOT NULL,
    Apellido2 VARCHAR(50),
    Contrasena VARCHAR(255) NOT NULL,
    Rol VARCHAR(30) NOT NULL,
    CorreoE VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE Vecino (
    CI CHAR(8) PRIMARY KEY,
    FOREIGN KEY (CI) REFERENCES Usuario(CI)
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

CREATE TABLE Vecino_Tel (
    CI CHAR(8),
    Tel VARCHAR(20),
    PRIMARY KEY (CI, Tel),
    FOREIGN KEY (CI) REFERENCES Vecino(CI)
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
    Ubicacion VARCHAR(255)
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
    Vertice VARCHAR(100),
    PRIMARY KEY (Id_Poligono, Vertice),
    FOREIGN KEY (Id_Poligono) REFERENCES Poligono(Id_Poligono)
);

CREATE TABLE Ruta (
    Id_Contenedor INT,
    Id_Poligono INT,
    Trayecto TEXT,
    Cantidad_Contenedores INT,
    PRIMARY KEY (Id_Contenedor),
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