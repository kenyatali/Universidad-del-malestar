DROP DATABASE IF EXISTS gestion_escolar;
CREATE DATABASE gestion_escolar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestion_escolar;

/* ---------------- CATALOGOS BASE ---------------- */
CREATE TABLE carreras (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    nombre  VARCHAR(30) NOT NULL UNIQUE,
    apodo   VARCHAR(3)  NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE materias (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    nombre    VARCHAR(100) NOT NULL,
    clave     VARCHAR(10)  NOT NULL UNIQUE,
    creditos  INT NOT NULL,
    CONSTRAINT CK_materias_creditos CHECK (creditos BETWEEN 1 AND 12)
) ENGINE=InnoDB;

CREATE TABLE periodos_escolares (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL UNIQUE,
    fecha_inicio  DATE NOT NULL,
    fecha_fin     DATE NOT NULL,
    CONSTRAINT CK_periodos_fechas CHECK (fecha_fin > fecha_inicio)
) ENGINE=InnoDB;

/* ---------------- PERSONAS ---------------- */
CREATE TABLE alumnos (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    nombre                VARCHAR(100) NOT NULL,
    id_carrera            INT NOT NULL,
    credito               INT NOT NULL DEFAULT 0,
    correo_institucional  VARCHAR(150) NOT NULL UNIQUE,
    `contraseña`          VARCHAR(255) NOT NULL,   -- guardar hash
    estado                TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT FK_alumnos_carrera FOREIGN KEY (id_carrera) REFERENCES carreras(id),
    CONSTRAINT CK_alumnos_credito CHECK (credito >= 0),
    CONSTRAINT CK_alumnos_estado  CHECK (estado IN (0,1))
) ENGINE=InnoDB;

CREATE TABLE profesores (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    num_empleado          VARCHAR(20)  NOT NULL UNIQUE,
    nombre                VARCHAR(100) NOT NULL,
    curp                  CHAR(18)     NOT NULL UNIQUE,
    rfc                   VARCHAR(13)  NOT NULL UNIQUE,
    correo_institucional  VARCHAR(150) NOT NULL UNIQUE,
    telefono              VARCHAR(20)  NULL,
    especialidad          VARCHAR(120) NULL,
    fecha_contratacion    DATE         NOT NULL,
    tipo_contrato         VARCHAR(30)  NOT NULL,
    `contraseña`          VARCHAR(255) NOT NULL,   -- guardar hash
    estado                TINYINT(1)   NOT NULL DEFAULT 1,
    rol                   VARCHAR(10)  NOT NULL DEFAULT 'profesor',
    CONSTRAINT CK_profesores_rfc      CHECK (CHAR_LENGTH(rfc) IN (12,13)),
    CONSTRAINT CK_profesores_contrato CHECK (tipo_contrato IN ('Tiempo Completo','Medio Tiempo','Asignatura')),
    CONSTRAINT CK_profesores_estado   CHECK (estado IN (0,1)),
    CONSTRAINT CK_profesores_rol      CHECK (rol = 'profesor')
) ENGINE=InnoDB;

CREATE TABLE empleados (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    nombre                VARCHAR(100) NOT NULL,
    correo_institucional  VARCHAR(150) NOT NULL UNIQUE,
    `contraseña`          VARCHAR(255) NOT NULL,   -- guardar hash
    telefono              VARCHAR(20)  NULL,
    estado                TINYINT(1)   NOT NULL DEFAULT 1,
    CONSTRAINT CK_empleados_estado CHECK (estado IN (0,1))
) ENGINE=InnoDB;

/* ---------------- PLAN DE ESTUDIOS Y SERIACION ---------------- */
CREATE TABLE carrera_materia (
    carrera   INT NOT NULL,
    materias  INT NOT NULL,
    semestre  INT NOT NULL,
    PRIMARY KEY (carrera, materias),
    CONSTRAINT FK_cm_carrera FOREIGN KEY (carrera)  REFERENCES carreras(id),
    CONSTRAINT FK_cm_materia FOREIGN KEY (materias) REFERENCES materias(id),
    CONSTRAINT CK_cm_semestre CHECK (semestre BETWEEN 1 AND 12)
) ENGINE=InnoDB;

CREATE TABLE materia_seriacion (
    materia            INT NOT NULL,   -- materia que se quiere cursar
    materia_requisito  INT NOT NULL,   -- materia que debe estar aprobada
    PRIMARY KEY (materia, materia_requisito),
    CONSTRAINT FK_seriacion_materia   FOREIGN KEY (materia)           REFERENCES materias(id),
    CONSTRAINT FK_seriacion_requisito FOREIGN KEY (materia_requisito) REFERENCES materias(id),
    CONSTRAINT CK_seriacion_distintas CHECK (materia <> materia_requisito)
) ENGINE=InnoDB;

/* ---------------- GRUPOS, ASIGNACIONES Y HORARIOS ---------------- */
CREATE TABLE grupos (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(20) NOT NULL,
    id_periodo   INT NOT NULL,
    cupo_maximo  INT NOT NULL,
    estado       TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT UQ_grupos_nombre_periodo UNIQUE (nombre, id_periodo),
    CONSTRAINT FK_grupos_periodo FOREIGN KEY (id_periodo) REFERENCES periodos_escolares(id),
    CONSTRAINT CK_grupos_cupo   CHECK (cupo_maximo > 0),
    CONSTRAINT CK_grupos_estado CHECK (estado IN (0,1))
) ENGINE=InnoDB;

CREATE TABLE materia_profesor (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    profesor          INT NOT NULL,
    materia           INT NOT NULL,
    grupo             INT NOT NULL,
    fecha_asignacion  DATE NOT NULL DEFAULT (CURRENT_DATE),
    estado            TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT UQ_mp_materia_grupo UNIQUE (materia, grupo),   -- un profesor por materia en cada grupo
    CONSTRAINT FK_mp_profesor FOREIGN KEY (profesor) REFERENCES profesores(id),
    CONSTRAINT FK_mp_materia  FOREIGN KEY (materia)  REFERENCES materias(id),
    CONSTRAINT FK_mp_grupo    FOREIGN KEY (grupo)    REFERENCES grupos(id),
    CONSTRAINT CK_mp_estado CHECK (estado IN (0,1))
) ENGINE=InnoDB;

CREATE TABLE horarios (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    asignacion   INT NOT NULL,
    dia          VARCHAR(10) NOT NULL,
    hora_inicio  TIME NOT NULL,
    hora_fin     TIME NOT NULL,
    aula         VARCHAR(10) NOT NULL,
    CONSTRAINT FK_horarios_asignacion FOREIGN KEY (asignacion) REFERENCES materia_profesor(id),
    CONSTRAINT CK_horarios_dia   CHECK (dia IN ('lunes','martes','miercoles','jueves','viernes','sabado')),
    CONSTRAINT CK_horarios_horas CHECK (hora_fin > hora_inicio)
) ENGINE=InnoDB;

/* ---------------- INSCRIPCIONES, CALIFICACIONES, ASISTENCIAS ---------------- */
CREATE TABLE inscripciones (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    alumno      INT NOT NULL,
    asignacion  INT NOT NULL,   -- materia + profesor + grupo
    estatus     VARCHAR(30) NOT NULL DEFAULT 'cursando',
    CONSTRAINT UQ_insc_alumno_asignacion UNIQUE (alumno, asignacion),
    CONSTRAINT FK_insc_alumno     FOREIGN KEY (alumno)     REFERENCES alumnos(id),
    CONSTRAINT FK_insc_asignacion FOREIGN KEY (asignacion) REFERENCES materia_profesor(id),
    CONSTRAINT CK_insc_estatus CHECK (estatus IN ('cursando','aprobado','reprobado'))
) ENGINE=InnoDB;

CREATE TABLE calificaciones (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    inscripcion         INT NOT NULL UNIQUE,
    parcial1            VARCHAR(3) NULL,   -- 70-100 o 'N/A'
    parcial2            VARCHAR(3) NULL,
    parcial3            VARCHAR(3) NULL,
    calificacion_final  FLOAT NOT NULL DEFAULT 0,
    CONSTRAINT FK_calif_inscripcion FOREIGN KEY (inscripcion) REFERENCES inscripciones(id),
    CONSTRAINT CK_calif_p1 CHECK (parcial1 IS NULL OR parcial1 = 'N/A' OR parcial1 REGEXP '^(7[0-9]|8[0-9]|9[0-9]|100)$'),
    CONSTRAINT CK_calif_p2 CHECK (parcial2 IS NULL OR parcial2 = 'N/A' OR parcial2 REGEXP '^(7[0-9]|8[0-9]|9[0-9]|100)$'),
    CONSTRAINT CK_calif_p3 CHECK (parcial3 IS NULL OR parcial3 = 'N/A' OR parcial3 REGEXP '^(7[0-9]|8[0-9]|9[0-9]|100)$'),
    CONSTRAINT CK_calif_final CHECK (calificacion_final BETWEEN 0 AND 100)
) ENGINE=InnoDB;

CREATE TABLE asistencias (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    inscripcion    INT NOT NULL,
    fecha          DATE NOT NULL,
    estatus        VARCHAR(15) NOT NULL,
    observaciones  VARCHAR(200) NULL,
    CONSTRAINT UQ_asistencia_dia UNIQUE (inscripcion, fecha),
    CONSTRAINT FK_asistencias_insc FOREIGN KEY (inscripcion) REFERENCES inscripciones(id),
    CONSTRAINT CK_asistencias_estatus CHECK (estatus IN ('asistencia','falta','retardo','justificada'))
) ENGINE=InnoDB;

/* ---------------- PAGOS Y ADEUDOS ---------------- */
CREATE TABLE conceptos_pago (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(80) NOT NULL UNIQUE,   -- Colegiatura, Inscripción, Constancia...
    monto_base  DECIMAL(10,2) NOT NULL,
    activo      TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT CK_conceptos_monto  CHECK (monto_base >= 0),
    CONSTRAINT CK_conceptos_activo CHECK (activo IN (0,1))
) ENGINE=InnoDB;

CREATE TABLE adeudos (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    alumno             INT NOT NULL,
    id_periodo         INT NOT NULL,
    concepto           INT NOT NULL,
    monto              DECIMAL(10,2) NOT NULL,
    fecha_generacion   DATE NOT NULL DEFAULT (CURRENT_DATE),
    fecha_vencimiento  DATE NOT NULL,
    estatus            VARCHAR(15) NOT NULL DEFAULT 'pendiente',
    CONSTRAINT FK_adeudos_alumno   FOREIGN KEY (alumno)     REFERENCES alumnos(id),
    CONSTRAINT FK_adeudos_periodo  FOREIGN KEY (id_periodo) REFERENCES periodos_escolares(id),
    CONSTRAINT FK_adeudos_concepto FOREIGN KEY (concepto)   REFERENCES conceptos_pago(id),
    CONSTRAINT CK_adeudos_monto   CHECK (monto > 0),
    CONSTRAINT CK_adeudos_fechas  CHECK (fecha_vencimiento >= fecha_generacion),
    CONSTRAINT CK_adeudos_estatus CHECK (estatus IN ('pendiente','parcial','pagado','vencido','cancelado'))
) ENGINE=InnoDB;

CREATE TABLE pagos (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    adeudo       INT NOT NULL,
    monto        DECIMAL(10,2) NOT NULL,
    fecha_pago   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    metodo_pago  VARCHAR(20) NOT NULL,
    referencia   VARCHAR(50) NULL,
    id_empleado  INT NULL,     -- quien registró el pago
    CONSTRAINT FK_pagos_adeudo   FOREIGN KEY (adeudo)      REFERENCES adeudos(id),
    CONSTRAINT FK_pagos_empleado FOREIGN KEY (id_empleado) REFERENCES empleados(id),
    CONSTRAINT CK_pagos_monto  CHECK (monto > 0),
    CONSTRAINT CK_pagos_metodo CHECK (metodo_pago IN ('efectivo','transferencia','tarjeta','deposito'))
) ENGINE=InnoDB;

/* ---------------- BITACORA ---------------- */
CREATE TABLE bitacora (
    id                BIGINT AUTO_INCREMENT PRIMARY KEY,
    tabla             VARCHAR(60) NOT NULL,
    id_registro       INT NOT NULL,
    accion            VARCHAR(10) NOT NULL,
    datos_anteriores  JSON NULL,
    datos_nuevos      JSON NULL,
    usuario_bd        VARCHAR(100) NOT NULL DEFAULT (CURRENT_USER()),
    usuario_app       VARCHAR(150) NULL,    -- correo del usuario logueado en la app
    fecha             DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    CONSTRAINT CK_bitacora_accion CHECK (accion IN ('INSERT','UPDATE','DELETE'))
) ENGINE=InnoDB;

/* ---------------- TRIGGERS ---------------- */
DELIMITER $$

/* --- Validación de seriación al inscribir --- */
CREATE TRIGGER trg_inscripciones_seriacion
BEFORE INSERT ON inscripciones
FOR EACH ROW
BEGIN
    DECLARE v_materia INT;
    DECLARE v_faltan INT;

    SELECT materia INTO v_materia FROM materia_profesor WHERE id = NEW.asignacion;

    SELECT COUNT(*) INTO v_faltan
    FROM materia_seriacion s
    WHERE s.materia = v_materia
      AND NOT EXISTS (
          SELECT 1
          FROM inscripciones i2
          JOIN materia_profesor mp2 ON mp2.id = i2.asignacion
          WHERE i2.alumno = NEW.alumno
            AND mp2.materia = s.materia_requisito
            AND i2.estatus = 'aprobado'
      );

    IF v_faltan > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'El alumno no ha aprobado las materias requisito.';
    END IF;
END$$

/* --- Bitácora: calificaciones --- */
CREATE TRIGGER trg_calif_ai AFTER INSERT ON calificaciones FOR EACH ROW
BEGIN
    INSERT INTO bitacora (tabla, id_registro, accion, datos_anteriores, datos_nuevos, usuario_app)
    VALUES ('calificaciones', NEW.id, 'INSERT', NULL,
            JSON_OBJECT('id',NEW.id,'inscripcion',NEW.inscripcion,'parcial1',NEW.parcial1,'parcial2',NEW.parcial2,'parcial3',NEW.parcial3,'calificacion_final',NEW.calificacion_final),
            @usuario_app);
END$$

CREATE TRIGGER trg_calif_au AFTER UPDATE ON calificaciones FOR EACH ROW
BEGIN
    INSERT INTO bitacora (tabla, id_registro, accion, datos_anteriores, datos_nuevos, usuario_app)
    VALUES ('calificaciones', NEW.id, 'UPDATE',
            JSON_OBJECT('id',OLD.id,'inscripcion',OLD.inscripcion,'parcial1',OLD.parcial1,'parcial2',OLD.parcial2,'parcial3',OLD.parcial3,'calificacion_final',OLD.calificacion_final),
            JSON_OBJECT('id',NEW.id,'inscripcion',NEW.inscripcion,'parcial1',NEW.parcial1,'parcial2',NEW.parcial2,'parcial3',NEW.parcial3,'calificacion_final',NEW.calificacion_final),
            @usuario_app);
END$$

CREATE TRIGGER trg_calif_ad AFTER DELETE ON calificaciones FOR EACH ROW
BEGIN
    INSERT INTO bitacora (tabla, id_registro, accion, datos_anteriores, datos_nuevos, usuario_app)
    VALUES ('calificaciones', OLD.id, 'DELETE',
            JSON_OBJECT('id',OLD.id,'inscripcion',OLD.inscripcion,'parcial1',OLD.parcial1,'parcial2',OLD.parcial2,'parcial3',OLD.parcial3,'calificacion_final',OLD.calificacion_final),
            NULL, @usuario_app);
END$$

/* --- Bitácora: inscripciones --- */
CREATE TRIGGER trg_insc_ai AFTER INSERT ON inscripciones FOR EACH ROW
BEGIN
    INSERT INTO bitacora (tabla, id_registro, accion, datos_anteriores, datos_nuevos, usuario_app)
    VALUES ('inscripciones', NEW.id, 'INSERT', NULL,
            JSON_OBJECT('id',NEW.id,'alumno',NEW.alumno,'asignacion',NEW.asignacion,'estatus',NEW.estatus),
            @usuario_app);
END$$

CREATE TRIGGER trg_insc_au AFTER UPDATE ON inscripciones FOR EACH ROW
BEGIN
    INSERT INTO bitacora (tabla, id_registro, accion, datos_anteriores, datos_nuevos, usuario_app)
    VALUES ('inscripciones', NEW.id, 'UPDATE',
            JSON_OBJECT('id',OLD.id,'alumno',OLD.alumno,'asignacion',OLD.asignacion,'estatus',OLD.estatus),
            JSON_OBJECT('id',NEW.id,'alumno',NEW.alumno,'asignacion',NEW.asignacion,'estatus',NEW.estatus),
            @usuario_app);
END$$

CREATE TRIGGER trg_insc_ad AFTER DELETE ON inscripciones FOR EACH ROW
BEGIN
    INSERT INTO bitacora (tabla, id_registro, accion, datos_anteriores, datos_nuevos, usuario_app)
    VALUES ('inscripciones', OLD.id, 'DELETE',
            JSON_OBJECT('id',OLD.id,'alumno',OLD.alumno,'asignacion',OLD.asignacion,'estatus',OLD.estatus),
            NULL, @usuario_app);
END$$

/* --- Bitácora: pagos --- */
CREATE TRIGGER trg_pagos_ai AFTER INSERT ON pagos FOR EACH ROW
BEGIN
    INSERT INTO bitacora (tabla, id_registro, accion, datos_anteriores, datos_nuevos, usuario_app)
    VALUES ('pagos', NEW.id, 'INSERT', NULL,
            JSON_OBJECT('id',NEW.id,'adeudo',NEW.adeudo,'monto',NEW.monto,'metodo_pago',NEW.metodo_pago,'referencia',NEW.referencia,'id_empleado',NEW.id_empleado),
            @usuario_app);
END$$

CREATE TRIGGER trg_pagos_au AFTER UPDATE ON pagos FOR EACH ROW
BEGIN
    INSERT INTO bitacora (tabla, id_registro, accion, datos_anteriores, datos_nuevos, usuario_app)
    VALUES ('pagos', NEW.id, 'UPDATE',
            JSON_OBJECT('id',OLD.id,'adeudo',OLD.adeudo,'monto',OLD.monto,'metodo_pago',OLD.metodo_pago,'referencia',OLD.referencia,'id_empleado',OLD.id_empleado),
            JSON_OBJECT('id',NEW.id,'adeudo',NEW.adeudo,'monto',NEW.monto,'metodo_pago',NEW.metodo_pago,'referencia',NEW.referencia,'id_empleado',NEW.id_empleado),
            @usuario_app);
END$$

CREATE TRIGGER trg_pagos_ad AFTER DELETE ON pagos FOR EACH ROW
BEGIN
    INSERT INTO bitacora (tabla, id_registro, accion, datos_anteriores, datos_nuevos, usuario_app)
    VALUES ('pagos', OLD.id, 'DELETE',
            JSON_OBJECT('id',OLD.id,'adeudo',OLD.adeudo,'monto',OLD.monto,'metodo_pago',OLD.metodo_pago,'referencia',OLD.referencia,'id_empleado',OLD.id_empleado),
            NULL, @usuario_app);
END$$

DELIMITER ;

/* ---------------- VISTA: SALDO DE CADA ADEUDO ---------------- */
CREATE VIEW vw_saldo_adeudos AS
SELECT a.id AS adeudo,
       a.alumno,
       c.nombre AS concepto,
       a.monto,
       IFNULL(SUM(p.monto), 0) AS pagado,
       a.monto - IFNULL(SUM(p.monto), 0) AS saldo,
       a.fecha_vencimiento,
       a.estatus
FROM adeudos a
JOIN conceptos_pago c ON c.id = a.concepto
LEFT JOIN pagos p ON p.adeudo = a.id
GROUP BY a.id, a.alumno, c.nombre, a.monto, a.fecha_vencimiento, a.estatus;