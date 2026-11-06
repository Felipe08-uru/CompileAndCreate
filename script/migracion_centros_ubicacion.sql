-- Para bases ya creadas: agrega la ubicación a los centros y carga los de ejemplo.
ALTER TABLE centro ADD COLUMN Latitud decimal(10,7) DEFAULT NULL, ADD COLUMN Longitud decimal(10,7) DEFAULT NULL;
UPDATE centro SET Latitud = -34.8475000, Longitud = -56.0963000 WHERE ID = 1;
INSERT INTO centro (ID, Servicio, ContAlmacenados, Capacidad, CamAlmacenados, Latitud, Longitud)
VALUES (2, 'Cent. de acopio Zona Este', 80, 400, 5, -34.8800000, -56.1250000);
INSERT INTO centrodeacopio (ID) VALUES (2);
