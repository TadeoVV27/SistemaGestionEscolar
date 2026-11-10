-- =========================================================
-- Base de Datos: SistemaEscolarDB
-- Sistema de Gestión Escolar
-- =========================================================

CREATE DATABASE IF NOT EXISTS SistemaEscolarDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE SistemaEscolarDB;

-- 1. Tabla de Usuarios
CREATE TABLE IF NOT EXISTS Usuarios (
    IdUsuario INT AUTO_INCREMENT PRIMARY KEY,
    Correo VARCHAR(100) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Rol ENUM('Admin', 'Profesor', 'Alumno') NOT NULL
) ENGINE=InnoDB;

-- 2. Tabla de Alumnos
CREATE TABLE IF NOT EXISTS Alumnos (
    Matricula VARCHAR(20) PRIMARY KEY,
    Nombre VARCHAR(50) NOT NULL,
    Apellidos VARCHAR(50) NOT NULL,
    IdUsuario INT NOT NULL,
    FOREIGN KEY (IdUsuario) REFERENCES Usuarios(IdUsuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Tabla de Profesores
CREATE TABLE IF NOT EXISTS Profesores (
    IdProfesor INT AUTO_INCREMENT PRIMARY KEY,
    NumeroEmpleado VARCHAR(20) NOT NULL UNIQUE,
    Nombre VARCHAR(50) NOT NULL,
    Apellidos VARCHAR(50) NOT NULL,
    IdUsuario INT NOT NULL,
    FOREIGN KEY (IdUsuario) REFERENCES Usuarios(IdUsuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Tabla de Materias
CREATE TABLE IF NOT EXISTS Materias (
    IdMateria INT AUTO_INCREMENT PRIMARY KEY,
    NombreMateria VARCHAR(100) NOT NULL,
    Creditos INT NOT NULL
) ENGINE=InnoDB;

-- 5. Tabla de Asignaciones
CREATE TABLE IF NOT EXISTS Asignaciones (
    IdAsignacion INT AUTO_INCREMENT PRIMARY KEY,
    IdProfesor INT NOT NULL,
    IdMateria INT NOT NULL,
    FOREIGN KEY (IdProfesor) REFERENCES Profesores(IdProfesor) ON DELETE CASCADE,
    FOREIGN KEY (IdMateria) REFERENCES Materias(IdMateria) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Tabla de Calificaciones
CREATE TABLE IF NOT EXISTS Calificaciones (
    IdCalificacion INT AUTO_INCREMENT PRIMARY KEY,
    Matricula VARCHAR(20) NOT NULL,
    IdMateria INT NOT NULL,
    IdProfesor INT NOT NULL,
    Calificacion DECIMAL(5,2) NOT NULL,
    FechaRegistro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Matricula) REFERENCES Alumnos(Matricula) ON DELETE CASCADE,
    FOREIGN KEY (IdMateria) REFERENCES Materias(IdMateria) ON DELETE CASCADE,
    FOREIGN KEY (IdProfesor) REFERENCES Profesores(IdProfesor) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Usuario Administrador por Defecto (Password encriptada: 'admin123')
INSERT INTO Usuarios (Correo, Password, Rol) 
VALUES ('admin@escolar.edu', '$2y$10$w3pU0a64s5M/V6Jz0Q5VLe5yCgPfqnBqFz1b6L6uH0DkE1P2E8y8m', 'Admin')
ON DUPLICATE KEY UPDATE Correo=Correo;
