-- =========================================================
-- COPIA COMPLETA DE BASE DE DATOS: SistemaEscolarDB
-- Incluye tablas y todos los datos registrados
-- =========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------
-- Estructura para la tabla `usuarios`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `IdUsuario` int NOT NULL AUTO_INCREMENT,
  `Correo` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Rol` varchar(50) NOT NULL,
  PRIMARY KEY (`IdUsuario`),
  UNIQUE KEY `Correo` (`Correo`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcado de datos para la tabla `usuarios`
INSERT INTO `usuarios` (`IdUsuario`, `Correo`, `Password`, `Rol`) VALUES ('1', 'admin@escolar.edu', '$2y$10$xtZYxO7bSk4Mowz1IIPnDeOTuB4vUC1Cy17Z6NsqGyq/Cnm1FDs4O', 'Admin');
INSERT INTO `usuarios` (`IdUsuario`, `Correo`, `Password`, `Rol`) VALUES ('2', '12345@escolar.edu', '$2y$10$DHG8rZBhm.B6iZf/y3NjyOltIvMUp1Gohe5d0657fddtw.wCSY7y.', 'Alumno');
INSERT INTO `usuarios` (`IdUsuario`, `Correo`, `Password`, `Rol`) VALUES ('3', '2226980@escolar.edu', '$2y$10$NkK5xJ6TlbEvyONZitEYpuDBEChm71Murwp6pfR67s40djvB5.v7u', 'Alumno');
INSERT INTO `usuarios` (`IdUsuario`, `Correo`, `Password`, `Rol`) VALUES ('4', 'docente112@escolar.edu', '$2y$10$FyJ.7bTR1CAzSSyf.DEALu/ZmQLPSRLicgcofYWNhjW09fFTdMhqm', 'Profesor');
INSERT INTO `usuarios` (`IdUsuario`, `Correo`, `Password`, `Rol`) VALUES ('5', 'docente999@escolar.edu', '$2y$10$7lEHTXYTUM2bKvSekQ406uVHkor9EAHk5espG76.fpwn0G2Z640Bi', 'Profesor');
INSERT INTO `usuarios` (`IdUsuario`, `Correo`, `Password`, `Rol`) VALUES ('6', 'docente1001@escolar.edu', '$2y$10$LDER0ErZG/B/eg0BGFhL8.euyHW1E26qoSDz1DNAYYIWROzV37H3i', 'Profesor');
INSERT INTO `usuarios` (`IdUsuario`, `Correo`, `Password`, `Rol`) VALUES ('7', 'docente1002@escolar.edu', '$2y$10$m.5LVb7x/JONQmqL6/ln9ehhj1g.NOu8FfBNiRr6miKWc89fJCkse', 'Profesor');
INSERT INTO `usuarios` (`IdUsuario`, `Correo`, `Password`, `Rol`) VALUES ('8', 'docente1003@escolar.edu', '$2y$10$V8xiSFuoRFvzd/b/9oiT6OP0JIo30DSS/KGkxt2GW3C7XaBrQW/Ye', 'Profesor');
INSERT INTO `usuarios` (`IdUsuario`, `Correo`, `Password`, `Rol`) VALUES ('9', 'docente1004@escolar.edu', '$2y$10$NyYk.sWreiHU.PkbXIzy0.75gvpEEpVhN8ewWuPAcExh3UBJgWZ6O', 'Profesor');
INSERT INTO `usuarios` (`IdUsuario`, `Correo`, `Password`, `Rol`) VALUES ('10', 'docente1005@escolar.edu', '$2y$10$i3CGCNiECO8PiVRLyg.RCuVzebIrhajw7QOwBoM7DS/JjK5skS3LC', 'Profesor');

-- ---------------------------------------------------------
-- Estructura para la tabla `alumnos`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `alumnos`;
CREATE TABLE `alumnos` (
  `Matricula` int NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Apellidos` varchar(100) NOT NULL,
  `IdUsuario` int DEFAULT NULL,
  PRIMARY KEY (`Matricula`),
  KEY `IdUsuario` (`IdUsuario`),
  CONSTRAINT `alumnos_ibfk_1` FOREIGN KEY (`IdUsuario`) REFERENCES `usuarios` (`IdUsuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcado de datos para la tabla `alumnos`
INSERT INTO `alumnos` (`Matricula`, `Nombre`, `Apellidos`, `IdUsuario`) VALUES ('12345', 'Juan', 'Pérez', '2');
INSERT INTO `alumnos` (`Matricula`, `Nombre`, `Apellidos`, `IdUsuario`) VALUES ('2226980', 'Tadeo Alejandro', 'Velázquez Vega', '3');

-- ---------------------------------------------------------
-- Estructura para la tabla `profesores`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `profesores`;
CREATE TABLE `profesores` (
  `IdProfesor` int NOT NULL AUTO_INCREMENT,
  `NumeroEmpleado` int NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Apellidos` varchar(100) NOT NULL,
  `IdUsuario` int DEFAULT NULL,
  PRIMARY KEY (`IdProfesor`),
  UNIQUE KEY `NumeroEmpleado` (`NumeroEmpleado`),
  KEY `IdUsuario` (`IdUsuario`),
  CONSTRAINT `profesores_ibfk_1` FOREIGN KEY (`IdUsuario`) REFERENCES `usuarios` (`IdUsuario`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcado de datos para la tabla `profesores`
INSERT INTO `profesores` (`IdProfesor`, `NumeroEmpleado`, `Nombre`, `Apellidos`, `IdUsuario`) VALUES ('1', '111', 'Roberto', 'Gómez', NULL);
INSERT INTO `profesores` (`IdProfesor`, `NumeroEmpleado`, `Nombre`, `Apellidos`, `IdUsuario`) VALUES ('2', '112', 'Juan Alberto', 'Torres', '4');
INSERT INTO `profesores` (`IdProfesor`, `NumeroEmpleado`, `Nombre`, `Apellidos`, `IdUsuario`) VALUES ('8', '999', 'Prueba', 'Sistema', '5');
INSERT INTO `profesores` (`IdProfesor`, `NumeroEmpleado`, `Nombre`, `Apellidos`, `IdUsuario`) VALUES ('9', '1001', 'Roberto', 'Gómez Bolaños', '6');
INSERT INTO `profesores` (`IdProfesor`, `NumeroEmpleado`, `Nombre`, `Apellidos`, `IdUsuario`) VALUES ('10', '1002', 'Ana María', 'Treviño Garza', '7');
INSERT INTO `profesores` (`IdProfesor`, `NumeroEmpleado`, `Nombre`, `Apellidos`, `IdUsuario`) VALUES ('11', '1003', 'Carlos Alberto', 'Martínez Salinas', '8');
INSERT INTO `profesores` (`IdProfesor`, `NumeroEmpleado`, `Nombre`, `Apellidos`, `IdUsuario`) VALUES ('12', '1004', 'Laura Elena', 'Villarreal Cantú', '9');
INSERT INTO `profesores` (`IdProfesor`, `NumeroEmpleado`, `Nombre`, `Apellidos`, `IdUsuario`) VALUES ('13', '1005', 'Héctor Manuel', 'Cavazos Leal', '10');

-- ---------------------------------------------------------
-- Estructura para la tabla `materias`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `materias`;
CREATE TABLE `materias` (
  `IdMateria` int NOT NULL AUTO_INCREMENT,
  `NombreMateria` varchar(100) NOT NULL,
  `Creditos` int NOT NULL,
  PRIMARY KEY (`IdMateria`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcado de datos para la tabla `materias`
INSERT INTO `materias` (`IdMateria`, `NombreMateria`, `Creditos`) VALUES ('1', 'Programación Web', '2');
INSERT INTO `materias` (`IdMateria`, `NombreMateria`, `Creditos`) VALUES ('2', 'Sistemas Digitales', '2');
INSERT INTO `materias` (`IdMateria`, `NombreMateria`, `Creditos`) VALUES ('3', 'Metodología de la programación', '4');
INSERT INTO `materias` (`IdMateria`, `NombreMateria`, `Creditos`) VALUES ('4', 'Cálculo diferencial', '4');
INSERT INTO `materias` (`IdMateria`, `NombreMateria`, `Creditos`) VALUES ('5', 'Matemáticas discretas', '4');
INSERT INTO `materias` (`IdMateria`, `NombreMateria`, `Creditos`) VALUES ('6', 'Bases de datos', '4');
INSERT INTO `materias` (`IdMateria`, `NombreMateria`, `Creditos`) VALUES ('7', 'Programación orientada a objetos', '4');
INSERT INTO `materias` (`IdMateria`, `NombreMateria`, `Creditos`) VALUES ('8', 'Estructura de datos y diseño de algoritmos', '4');
INSERT INTO `materias` (`IdMateria`, `NombreMateria`, `Creditos`) VALUES ('9', 'Programación web', '4');
INSERT INTO `materias` (`IdMateria`, `NombreMateria`, `Creditos`) VALUES ('10', 'Ingeniería de software', '4');
INSERT INTO `materias` (`IdMateria`, `NombreMateria`, `Creditos`) VALUES ('11', 'Redes computacionales', '4');
INSERT INTO `materias` (`IdMateria`, `NombreMateria`, `Creditos`) VALUES ('12', 'Inteligencia en negocios', '4');
INSERT INTO `materias` (`IdMateria`, `NombreMateria`, `Creditos`) VALUES ('13', 'Fundamentos de inteligencia artificial', '4');
INSERT INTO `materias` (`IdMateria`, `NombreMateria`, `Creditos`) VALUES ('14', 'Desarrollo de aplicaciones móviles', '4');

-- ---------------------------------------------------------
-- Estructura para la tabla `asignaciones`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `asignaciones`;
CREATE TABLE `asignaciones` (
  `IdAsignacion` int NOT NULL AUTO_INCREMENT,
  `IdProfesor` int DEFAULT NULL,
  `IdMateria` int DEFAULT NULL,
  PRIMARY KEY (`IdAsignacion`),
  KEY `IdProfesor` (`IdProfesor`),
  KEY `IdMateria` (`IdMateria`),
  CONSTRAINT `asignaciones_ibfk_1` FOREIGN KEY (`IdProfesor`) REFERENCES `profesores` (`IdProfesor`),
  CONSTRAINT `asignaciones_ibfk_2` FOREIGN KEY (`IdMateria`) REFERENCES `materias` (`IdMateria`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcado de datos para la tabla `asignaciones`
INSERT INTO `asignaciones` (`IdAsignacion`, `IdProfesor`, `IdMateria`) VALUES ('1', '1', '1');
INSERT INTO `asignaciones` (`IdAsignacion`, `IdProfesor`, `IdMateria`) VALUES ('2', '2', '1');
INSERT INTO `asignaciones` (`IdAsignacion`, `IdProfesor`, `IdMateria`) VALUES ('3', '2', '2');
INSERT INTO `asignaciones` (`IdAsignacion`, `IdProfesor`, `IdMateria`) VALUES ('4', '1', '1');
INSERT INTO `asignaciones` (`IdAsignacion`, `IdProfesor`, `IdMateria`) VALUES ('5', '1', '8');
INSERT INTO `asignaciones` (`IdAsignacion`, `IdProfesor`, `IdMateria`) VALUES ('6', '2', '4');
INSERT INTO `asignaciones` (`IdAsignacion`, `IdProfesor`, `IdMateria`) VALUES ('7', '2', '5');
INSERT INTO `asignaciones` (`IdAsignacion`, `IdProfesor`, `IdMateria`) VALUES ('14', '1', '12');
INSERT INTO `asignaciones` (`IdAsignacion`, `IdProfesor`, `IdMateria`) VALUES ('26', '9', '3');
INSERT INTO `asignaciones` (`IdAsignacion`, `IdProfesor`, `IdMateria`) VALUES ('27', '9', '10');
INSERT INTO `asignaciones` (`IdAsignacion`, `IdProfesor`, `IdMateria`) VALUES ('28', '9', '14');
INSERT INTO `asignaciones` (`IdAsignacion`, `IdProfesor`, `IdMateria`) VALUES ('29', '10', '6');
INSERT INTO `asignaciones` (`IdAsignacion`, `IdProfesor`, `IdMateria`) VALUES ('30', '10', '7');
INSERT INTO `asignaciones` (`IdAsignacion`, `IdProfesor`, `IdMateria`) VALUES ('31', '11', '4');
INSERT INTO `asignaciones` (`IdAsignacion`, `IdProfesor`, `IdMateria`) VALUES ('32', '11', '5');
INSERT INTO `asignaciones` (`IdAsignacion`, `IdProfesor`, `IdMateria`) VALUES ('33', '12', '8');
INSERT INTO `asignaciones` (`IdAsignacion`, `IdProfesor`, `IdMateria`) VALUES ('34', '12', '1');
INSERT INTO `asignaciones` (`IdAsignacion`, `IdProfesor`, `IdMateria`) VALUES ('35', '13', '11');
INSERT INTO `asignaciones` (`IdAsignacion`, `IdProfesor`, `IdMateria`) VALUES ('36', '13', '13');

-- ---------------------------------------------------------
-- Estructura para la tabla `calificaciones`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `calificaciones`;
CREATE TABLE `calificaciones` (
  `IdCalificacion` int NOT NULL AUTO_INCREMENT,
  `Matricula` int DEFAULT NULL,
  `IdMateria` int DEFAULT NULL,
  `IdProfesor` int DEFAULT NULL,
  `Calificacion` decimal(5,2) NOT NULL,
  `FechaRegistro` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`IdCalificacion`),
  KEY `Matricula` (`Matricula`),
  KEY `IdMateria` (`IdMateria`),
  KEY `IdProfesor` (`IdProfesor`),
  CONSTRAINT `calificaciones_ibfk_1` FOREIGN KEY (`Matricula`) REFERENCES `alumnos` (`Matricula`),
  CONSTRAINT `calificaciones_ibfk_2` FOREIGN KEY (`IdMateria`) REFERENCES `materias` (`IdMateria`),
  CONSTRAINT `calificaciones_ibfk_3` FOREIGN KEY (`IdProfesor`) REFERENCES `profesores` (`IdProfesor`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcado de datos para la tabla `calificaciones`
INSERT INTO `calificaciones` (`IdCalificacion`, `Matricula`, `IdMateria`, `IdProfesor`, `Calificacion`, `FechaRegistro`) VALUES ('1', '2226980', '2', '2', '100.00', '2026-09-26 17:58:56');
INSERT INTO `calificaciones` (`IdCalificacion`, `Matricula`, `IdMateria`, `IdProfesor`, `Calificacion`, `FechaRegistro`) VALUES ('2', '12345', '1', '2', '70.00', '2026-09-26 23:30:06');
INSERT INTO `calificaciones` (`IdCalificacion`, `Matricula`, `IdMateria`, `IdProfesor`, `Calificacion`, `FechaRegistro`) VALUES ('3', '2226980', '3', '9', '0.00', '2026-11-05 13:31:13');

SET FOREIGN_KEY_CHECKS = 1;
