# Sistema de Gestión Escolar

Plataforma web integral para la administración académica, control de calificaciones y emisión de boletas oficiales con soporte de Inteligencia Artificial (Google Gemini).

## 🚀 Características Principales

- **Arquitectura Basada en Roles (RBAC):**
  - **Administrador:** Gestión integral de alumnos, profesores, catálogo de materias y asignación de cátedras con filtros dinámicos.
  - **Docente:** Consulta de materias asignadas, captura y actualización de calificaciones en tiempo real.
  - **Estudiante:** Acceso a kardex académico, estadísticas de promedio y generación/descarga de boleta oficial en formato PDF.
- **Asistente Virtual con IA (Gemini):**
  - Módulo integrado para resolver dudas frecuentes sobre accesos, recuperación de credenciales y navegación.
- **Seguridad Robusta:**
  - Cifrado de contraseñas con algoritmo seguro `bcrypt` (`PASSWORD_BCRYPT`).
  - Protección de credenciales y API Keys mediante variables de entorno (`.env` protegido con `.gitignore` y `.htaccess`).
  - Consultas parametrizadas con sentencias preparadas (`mysqli`) para mitigar inyecciones SQL.

---

## 🛠️ Requisitos del Sistema

- Servidor Web Apache (ej. XAMPP).
- PHP 7.4 o superior (con extensiones `curl` y `mysqli` habilitadas).
- Base de datos MySQL / MariaDB.
- API Key de Google Gemini (Google AI Studio).

---

## ⚙️ Instalación y Puesta en Marcha

1. **Clonar o descargar el proyecto** dentro del directorio público del servidor web:
   ```bash
   git clone https://github.com/TadeoVV27/SistemaGestionEscolar.git
   ```
2. **Configurar la Base de Datos:**
   - Crear una base de datos llamada `SistemaEscolarDB` en MySQL.
   - Ejecutar el script SQL de creación de tablas incluido en la documentación técnica.
3. **Configurar las Variables de Entorno:**
   - Duplicar el archivo `.env.example` y renombrarlo como `.env`:
     ```bash
     cp .env.example .env
     ```
   - Editar `.env` con las credenciales de tu base de datos y tu API Key de Gemini:
     ```env
     GEMINI_API_KEY=tu_api_key_aqui
     DB_HOST=localhost
     DB_USER=root
     DB_PASS=
     DB_NAME=SistemaEscolarDB
     ```
4. **Abrir en el Navegador:**
   - Iniciar Apache y MySQL en XAMPP.
   - Ingresar a: `http://localhost/SistemaEscolar/`

---

## 👥 Integrantes del Proyecto

- **Joel Guadalupe Tamez Olmos**
- **Tadeo Alejandro Velázquez Vega**
- **Francisco Alejandro Pérez Heredia**
- **Angela Jaquelyn Pecina Loera**

**Docente:** Ing. Daniela Natali García Carreño  
