<<<<<<< Updated upstream
-- Creación de la base de datos (opcional, si aún no existe)
CREATE DATABASE IF NOT EXISTS biblioteca_db;
USE biblioteca_db;

-- -----------------------------------------------------
-- Tabla `libros`
-- Almacena el inventario bibliográfico
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `libros` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titulo` VARCHAR(255) NOT NULL COMMENT 'Título del libro',
  `autor` VARCHAR(150) NOT NULL COMMENT 'Autor(es)',
  `isbn` VARCHAR(20) NOT NULL COMMENT 'ISBN único',
  `estado` ENUM('disponible', 'prestado') NOT NULL DEFAULT 'disponible' COMMENT 'Estado del ejemplar',
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de registro en el sistema',
  `fecha_actualizacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última modificación',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `isbn_UNIQUE` (`isbn` ASC),
  INDEX `idx_estado` (`estado` ASC)  -- Para filtrar rápidamente por disponibilidad
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de libros de la biblioteca';

-- -----------------------------------------------------
-- Tabla `contactos`
-- Registra solicitudes de libros no encontrados
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `contactos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre_lector` VARCHAR(100) NOT NULL COMMENT 'Nombre del solicitante',
  `correo_lector` VARCHAR(150) NOT NULL COMMENT 'Correo electrónico de contacto',
  `titulo_solicitado` VARCHAR(255) NOT NULL COMMENT 'Título del libro solicitado',
  `autor_solicitado` VARCHAR(150) DEFAULT NULL COMMENT 'Autor (si lo conoce)',
  `informacion_adicional` TEXT DEFAULT NULL COMMENT 'Observaciones adicionales',
  `fecha_solicitud` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de la solicitud',
  PRIMARY KEY (`id`),
  INDEX `idx_correo` (`correo_lector` ASC)
=======
-- Creación de la base de datos (opcional, si aún no existe)
CREATE DATABASE IF NOT EXISTS biblioteca_db;
USE biblioteca_db;

-- -----------------------------------------------------
-- Tabla `libros`
-- Almacena el inventario bibliográfico
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `libros` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titulo` VARCHAR(255) NOT NULL COMMENT 'Título del libro',
  `autor` VARCHAR(150) NOT NULL COMMENT 'Autor(es)',
  `isbn` VARCHAR(20) NOT NULL COMMENT 'ISBN único',
  `estado` ENUM('disponible', 'prestado') NOT NULL DEFAULT 'disponible' COMMENT 'Estado del ejemplar',
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de registro en el sistema',
  `fecha_actualizacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última modificación',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `isbn_UNIQUE` (`isbn` ASC),
  INDEX `idx_estado` (`estado` ASC)  -- Para filtrar rápidamente por disponibilidad
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de libros de la biblioteca';

-- -----------------------------------------------------
-- Tabla `contactos`
-- Registra solicitudes de libros no encontrados
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `contactos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre_lector` VARCHAR(100) NOT NULL COMMENT 'Nombre del solicitante',
  `correo_lector` VARCHAR(150) NOT NULL COMMENT 'Correo electrónico de contacto',
  `titulo_solicitado` VARCHAR(255) NOT NULL COMMENT 'Título del libro solicitado',
  `autor_solicitado` VARCHAR(150) DEFAULT NULL COMMENT 'Autor (si lo conoce)',
  `informacion_adicional` TEXT DEFAULT NULL COMMENT 'Observaciones adicionales',
  `fecha_solicitud` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de la solicitud',
  PRIMARY KEY (`id`),
  INDEX `idx_correo` (`correo_lector` ASC)
>>>>>>> Stashed changes
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Solicitudes de adquisición de lectores';