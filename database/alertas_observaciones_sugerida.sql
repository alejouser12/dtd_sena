CREATE TABLE alertas_observaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aprendiz_id INT NOT NULL,
    instructor_id INT NULL,
    tipo ENUM('alerta', 'observacion') NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    descripcion TEXT NOT NULL,
    nivel_riesgo VARCHAR(20) NOT NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'Activa',
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ao_aprendiz_fecha (aprendiz_id, fecha),
    INDEX idx_ao_tipo_estado (tipo, estado),
    CONSTRAINT fk_ao_aprendiz
        FOREIGN KEY (aprendiz_id) REFERENCES aprendiz(APRENDIZ_ID),
    CONSTRAINT fk_ao_instructor
        FOREIGN KEY (instructor_id) REFERENCES instructor(INSTRUCTOR_ID)
);
