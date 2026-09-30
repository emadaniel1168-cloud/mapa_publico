USE colegio_santander;

CREATE TABLE IF NOT EXISTS lugares_360 (
  id_lugar INT NOT NULL AUTO_INCREMENT,
  panorama_id VARCHAR(100) NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  descripcion TEXT NULL,
  id_horario INT NULL,
  pitch DECIMAL(8,3) NOT NULL DEFAULT 0,
  yaw DECIMAL(8,3) NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_lugar),
  KEY idx_lugares_panorama (panorama_id),
  KEY idx_lugares_horario (id_horario),
  CONSTRAINT fk_lugares_horario
    FOREIGN KEY (id_horario) REFERENCES horarios (id_horario)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Opcional: comprobar la nueva tabla.
-- SELECT * FROM lugares_360;
