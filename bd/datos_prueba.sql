USE gestion_escolar;
-- Datos opcionales para probar el portal. No borra tablas ni registros existentes.
INSERT IGNORE INTO carreras (nombre, apodo) VALUES ('Ingeniería de Prueba', 'IDP');
INSERT IGNORE INTO alumnos (nombre, id_carrera, credito, correo_institucional, `contraseña`, estado)
SELECT 'Alumno Demo', c.id, 24, 'alumno.demo@prueba.edu.mx', '$2y$12$XGICBf3zPO32kEmJyu29FOGb2r3CtaWeXS0ioR4cp8Ha8xE0vSs..', 1
FROM carreras c WHERE c.apodo='IDP';
