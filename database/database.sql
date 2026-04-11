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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Solicitudes de adquisición de lectores';

-- =====================================================
-- INSERCIÓN DE DATOS DE PRUEBA PARA LA BIBLIOTECA
-- =====================================================
-- -----------------------------------------------------
-- Libros de ejemplo
-- -----------------------------------------------------
INSERT INTO libros (titulo, autor, isbn, estado) VALUES
('Cien años de soledad', 'Gabriel García Márquez', '978-0307474728', 'disponible'),
('1984', 'George Orwell', '978-0451524935', 'prestado'),
('El Principito', 'Antoine de Saint-Exupéry', '978-0156012195', 'disponible'),
('Don Quijote de la Mancha', 'Miguel de Cervantes', '978-8424116590', 'disponible'),
('Rayuela', 'Julio Cortázar', '978-8437604572', 'prestado'),
('Ficciones', 'Jorge Luis Borges', '978-0307950925', 'disponible'),
('La casa de los espíritus', 'Isabel Allende', '978-0060951306', 'disponible'),
('Pedro Páramo', 'Juan Rulfo', '978-9681902300', 'prestado'),
('Como agua para chocolate', 'Laura Esquivel', '978-0385420174', 'disponible'),
('El amor en los tiempos del cólera', 'Gabriel García Márquez', '978-0307389732', 'disponible'),
('La sombra del viento', 'Carlos Ruiz Zafón', '978-0143034902', 'prestado'),
('Los detectives salvajes', 'Roberto Bolaño', '978-0312427481', 'disponible'),
('El túnel', 'Ernesto Sabato', '978-8432216459', 'disponible'),
('Crónica de una muerte anunciada', 'Gabriel García Márquez', '978-0307389749', 'prestado'),
('La ciudad y los perros', 'Mario Vargas Llosa', '978-8420426458', 'disponible');

-- -----------------------------------------------------
-- Solicitudes de contacto de ejemplo
-- -----------------------------------------------------
INSERT INTO contactos (nombre_lector, correo_lector, titulo_solicitado, autor_solicitado, informacion_adicional) VALUES
('María González', 'maria.gonzalez@email.com', 'El código Da Vinci', 'Dan Brown', 'Me lo recomendaron mucho'),
('Carlos Rodríguez', 'carlos.rodriguez@email.com', 'Sapiens: De animales a dioses', 'Yuval Noah Harari', NULL),
('Laura Méndez', 'laura.mendez@email.com', 'Cien años de soledad', 'Gabriel García Márquez', 'Todos los ejemplares están prestados, ¿pueden adquirir más?'),
('Javier Fuentes', 'javier.fuentes@email.com', 'Dune', 'Frank Herbert', 'Edición ilustrada si es posible'),
('Ana Sofía Vargas', 'ana.vargas@email.com', 'La campana de cristal', 'Sylvia Plath', NULL),
('Pedro Infante', 'pedro.infante@email.com', 'Poesía completa', 'Alejandra Pizarnik', 'Cualquier edición me sirve');

-- Mostrar resumen de datos insertados
SELECT 'Libros insertados:' AS mensaje, COUNT(*) AS total FROM libros
UNION ALL
SELECT 'Solicitudes de contacto:', COUNT(*) FROM contactos;