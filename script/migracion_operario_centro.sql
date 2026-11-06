-- Para bases ya creadas: tabla que asocia cada operario con un centro de acopio.
CREATE TABLE IF NOT EXISTS operario_centro (
  CI char(8) NOT NULL,
  ID int(11) NOT NULL,
  PRIMARY KEY (CI),
  FOREIGN KEY (CI) REFERENCES operario (CI) ON DELETE CASCADE,
  FOREIGN KEY (ID) REFERENCES centro (ID) ON DELETE CASCADE
);

-- Operario de ejemplo asociado al centro 1 (sin centro, un operario no puede operar).
INSERT IGNORE INTO operario_centro (CI, ID) VALUES ('55555555', 1);
