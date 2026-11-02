-- ============================================================================
--  PARADOXA26 - Base de datos completa (instalación desde cero)
--  Importar en XAMPP: phpMyAdmin -> pestaña "Importar" -> elegir este archivo -> Continuar.
--  (No hace falta crear la base antes ni correr ninguna migración: ya está todo incluido.)
--
--  OJO: si la base `paradoxa26` ya existe, este script BORRA y recrea sus tablas.
--
--  Cuenta administradora (ya verificada, entra directo al panel admin):
--     correo:     victor.kazamarov@paradoxa.corp
--     contraseña: ver comentario oculto de login.php
-- ============================================================================

SET NAMES utf8mb4;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `paradoxa26` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `paradoxa26`;

DROP TABLE IF EXISTS `reacciones_comentarios`;
DROP TABLE IF EXISTS `reacciones_publicaciones`;
DROP TABLE IF EXISTS `comentarios`;
DROP TABLE IF EXISTS `publicaciones`;
DROP TABLE IF EXISTS `usuarios`;

-- ----------------------------------------------------------------------------
-- usuarios
--   rol                'usuario' | 'admin'
--   foto_perfil        solo el nombre del archivo (vive en app/public/uploads/avatars/)
--   token              token del link de verificación que se manda por correo
--   estado_verificado  0 = cuenta sin validar (no puede iniciar sesión), 1 = validada
-- ----------------------------------------------------------------------------
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_usuario_real` varchar(50) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `contrasenia` varchar(255) NOT NULL,
  `nombre_usuario_anonimo` varchar(50) NOT NULL,
  `fecha_registro` date NOT NULL,
  `rol` enum('usuario','admin') NOT NULL DEFAULT 'usuario',
  `biografia` varchar(255) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `estado_verificado` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`),
  UNIQUE KEY `nombre_usuario_anonimo` (`nombre_usuario_anonimo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- Foro: hilos, comentarios y reacciones
-- ----------------------------------------------------------------------------
CREATE TABLE `publicaciones` (
  `id_publicacion` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `contenido` text NOT NULL,
  `fecha_publicacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_edicion` timestamp NOT NULL DEFAULT current_timestamp(),
  `id` int(11) NOT NULL,
  PRIMARY KEY (`id_publicacion`),
  KEY `id` (`id`),
  CONSTRAINT `publicaciones_ibfk_1` FOREIGN KEY (`id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `comentarios` (
  `id_comentario` int(11) NOT NULL AUTO_INCREMENT,
  `id_publicacion` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `contenido` varchar(500) NOT NULL,
  `fecha_comentario` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_comentario`),
  KEY `id_publicacion` (`id_publicacion`),
  KEY `id` (`id`),
  CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`id_publicacion`) REFERENCES `publicaciones` (`id_publicacion`) ON DELETE CASCADE,
  CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `reacciones_publicaciones` (
  `id_reaccion` int(11) NOT NULL AUTO_INCREMENT,
  `id_publicacion` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `fecha_reaccion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_reaccion`),
  UNIQUE KEY `una_por_usuario` (`id_publicacion`,`id`),
  KEY `id` (`id`),
  CONSTRAINT `reacciones_publicaciones_ibfk_1` FOREIGN KEY (`id_publicacion`) REFERENCES `publicaciones` (`id_publicacion`) ON DELETE CASCADE,
  CONSTRAINT `reacciones_publicaciones_ibfk_2` FOREIGN KEY (`id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `reacciones_comentarios` (
  `id_reaccion` int(11) NOT NULL AUTO_INCREMENT,
  `id_comentario` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `fecha_reaccion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_reaccion`),
  UNIQUE KEY `una_por_usuario` (`id_comentario`,`id`),
  KEY `id` (`id`),
  CONSTRAINT `reacciones_comentarios_ibfk_1` FOREIGN KEY (`id_comentario`) REFERENCES `comentarios` (`id_comentario`) ON DELETE CASCADE,
  CONSTRAINT `reacciones_comentarios_ibfk_2` FOREIGN KEY (`id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- Datos iniciales: administrador del sistema (cuenta del puzzle).
-- Ya viene verificada (estado_verificado = 1): si no, el login la rechazaría.
-- El hash es bcrypt, el mismo formato que genera password_hash() en PHP.
-- ----------------------------------------------------------------------------
INSERT INTO `usuarios` (`nombre_usuario_real`, `correo`, `contrasenia`, `nombre_usuario_anonimo`, `fecha_registro`, `rol`, `estado_verificado`) VALUES
('Victor Kazamarov', 'victor.kazamarov@paradoxa.corp', '$2y$10$GKO0jvf3kveND0ueRPkji.J5HLJ79OAWNXTfUS2iXU1QNF94GwUOS', 'victor_k', CURDATE(), 'admin', 1);

-- ============================================================================
--  DATOS DE DEMOSTRACIÓN DEL FORO (opcional)
--  3 hilos + comentarios + reacciones para que el foro no arranque vacío.
--  Los 3 usuarios demo NO pueden iniciar sesión (contraseña inválida a propósito).
--  Para sacarlos: panel admin -> Gestión de usuarios -> Eliminar
--  (se borran también sus hilos, comentarios y reacciones), o borrá todo
--  lo que está debajo de esta línea antes de importar.
-- ============================================================================

INSERT INTO `usuarios` (`nombre_usuario_real`, `correo`, `contrasenia`, `nombre_usuario_anonimo`, `fecha_registro`, `rol`, `estado_verificado`) VALUES
('Demo 02', 'investigador_02@demo.paradoxa26', '!', 'investigador_02', CURDATE(), 'usuario', 1),
('Demo 07', 'investigador_07@demo.paradoxa26', '!', 'investigador_07', CURDATE(), 'usuario', 1),
('Demo 19', 'investigador_19@demo.paradoxa26', '!', 'investigador_19', CURDATE(), 'usuario', 1);

-- Hilos
INSERT INTO `publicaciones` (`titulo`, `contenido`, `fecha_publicacion`, `id`)
SELECT '¿Alguien más encontró el patrón en el segundo archivo?',
       'Las coordenadas del margen no coinciden con el mapa que compartieron ayer. Puede ser una capa distinta del cifrado...',
       NOW() - INTERVAL 2 HOUR, `id` FROM `usuarios` WHERE `nombre_usuario_anonimo` = 'investigador_07';

INSERT INTO `publicaciones` (`titulo`, `contenido`, `fecha_publicacion`, `id`)
SELECT 'Registro de acceso sospechoso en el nodo ARG-26',
       'El terminal muestra una sesión incompleta. No sé si es parte del caso o un error real del sitio.',
       NOW() - INTERVAL 5 HOUR, `id` FROM `usuarios` WHERE `nombre_usuario_anonimo` = 'investigador_19';

INSERT INTO `publicaciones` (`titulo`, `contenido`, `fecha_publicacion`, `id`)
SELECT 'Guía para nuevos: cómo empezar sin spoilers',
       'Antes de tocar los acertijos avanzados, empiecen por el módulo de bienvenida. Ahí está la primera pista real.',
       NOW() - INTERVAL 1 DAY, `id` FROM `usuarios` WHERE `nombre_usuario_anonimo` = 'investigador_02';

-- Comentarios
INSERT INTO `comentarios` (`id_publicacion`, `id`, `contenido`, `fecha_comentario`)
SELECT p.`id_publicacion`, u.`id`, 'Yo también noto el desfase. Revisá si el margen izquierdo usa otra proyección.', NOW() - INTERVAL 90 MINUTE
FROM `publicaciones` p JOIN `usuarios` u ON u.`nombre_usuario_anonimo` = 'investigador_19'
WHERE p.`titulo` = '¿Alguien más encontró el patrón en el segundo archivo?';

INSERT INTO `comentarios` (`id_publicacion`, `id`, `contenido`, `fecha_comentario`)
SELECT p.`id_publicacion`, u.`id`, 'Probá comparando con el segundo mapa, el de la capa inferior.', NOW() - INTERVAL 40 MINUTE
FROM `publicaciones` p JOIN `usuarios` u ON u.`nombre_usuario_anonimo` = 'investigador_02'
WHERE p.`titulo` = '¿Alguien más encontró el patrón en el segundo archivo?';

INSERT INTO `comentarios` (`id_publicacion`, `id`, `contenido`, `fecha_comentario`)
SELECT p.`id_publicacion`, u.`id`, 'A mí me pasó lo mismo: la sesión se reinicia sola a los pocos segundos.', NOW() - INTERVAL 3 HOUR
FROM `publicaciones` p JOIN `usuarios` u ON u.`nombre_usuario_anonimo` = 'investigador_07'
WHERE p.`titulo` = 'Registro de acceso sospechoso en el nodo ARG-26';

INSERT INTO `comentarios` (`id_publicacion`, `id`, `contenido`, `fecha_comentario`)
SELECT p.`id_publicacion`, u.`id`, 'Gracias por la guía, me ahorró un montón de vueltas.', NOW() - INTERVAL 20 HOUR
FROM `publicaciones` p JOIN `usuarios` u ON u.`nombre_usuario_anonimo` = 'investigador_19'
WHERE p.`titulo` = 'Guía para nuevos: cómo empezar sin spoilers';

-- Reacciones a hilos
INSERT INTO `reacciones_publicaciones` (`id_publicacion`, `id`, `tipo`)
SELECT p.`id_publicacion`, u.`id`, 'util'
FROM `publicaciones` p JOIN `usuarios` u ON u.`nombre_usuario_anonimo` IN ('investigador_19', 'investigador_02')
WHERE p.`titulo` = 'Guía para nuevos: cómo empezar sin spoilers';

INSERT INTO `reacciones_publicaciones` (`id_publicacion`, `id`, `tipo`)
SELECT p.`id_publicacion`, u.`id`, 'sospechoso'
FROM `publicaciones` p JOIN `usuarios` u ON u.`nombre_usuario_anonimo` = 'investigador_07'
WHERE p.`titulo` = 'Registro de acceso sospechoso en el nodo ARG-26';

-- Reacción a un comentario
INSERT INTO `reacciones_comentarios` (`id_comentario`, `id`, `tipo`)
SELECT c.`id_comentario`, u.`id`, 'me_gusta'
FROM `comentarios` c JOIN `usuarios` u ON u.`nombre_usuario_anonimo` = 'investigador_07'
WHERE c.`contenido` LIKE 'Probá comparando%';
