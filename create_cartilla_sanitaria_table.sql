CREATE TABLE IF NOT EXISTS cartilla_sanitaria (
    id_cartilla INT AUTO_INCREMENT PRIMARY KEY,
    id_mascota INT NOT NULL,
    nombre_documento VARCHAR(255) NOT NULL,
    fecha_documento DATE NOT NULL,
    archivo_path VARCHAR(255) NOT NULL,
    tipo_archivo VARCHAR(50) NOT NULL, -- 'pdf' o 'jpeg'
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_mascota) REFERENCES mascotas(id_mascota) ON DELETE CASCADE
);
