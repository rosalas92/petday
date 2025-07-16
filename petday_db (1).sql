-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-07-2025 a las 13:59:16
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `petday_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuraciones_usuario`
--

CREATE TABLE `configuraciones_usuario` (
  `id_configuracion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `notificaciones_email` tinyint(1) DEFAULT 1,
  `notificaciones_push` tinyint(1) DEFAULT 1,
  `recordatorios_anticipacion` int(11) DEFAULT 30,
  `tema_interfaz` enum('claro','oscuro','auto') DEFAULT 'claro',
  `idioma` varchar(5) DEFAULT 'es',
  `zona_horaria` varchar(50) DEFAULT 'Europe/Madrid',
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contactos_veterinarios`
--

CREATE TABLE `contactos_veterinarios` (
  `id_contacto` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `clinica` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `es_principal` tinyint(1) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

CREATE TABLE `eventos` (
  `id_evento` int(11) NOT NULL,
  `id_mascota` int(11) NOT NULL,
  `tipo_evento` enum('vacuna','veterinario','baño','corte_uñas','desparasitacion','revision','otro') NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_evento` datetime NOT NULL,
  `recordatorio_enviado` tinyint(1) DEFAULT 0,
  `completado` tinyint(1) DEFAULT 0,
  `notas_resultado` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_medico`
--

CREATE TABLE `historial_medico` (
  `id_historial` int(11) NOT NULL,
  `id_mascota` int(11) NOT NULL,
  `tipo_registro` enum('vacuna','enfermedad','tratamiento','cirugia','revision','medicacion','alergia','otro') NOT NULL,
  `fecha_registro` date NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `veterinario` varchar(100) DEFAULT NULL,
  `clinica` varchar(100) DEFAULT NULL,
  `medicamentos` text DEFAULT NULL,
  `dosis` varchar(100) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `archivo_adjunto` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_sistema`
--

CREATE TABLE `logs_sistema` (
  `id_log` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `id_registro` int(11) DEFAULT NULL,
  `datos_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_anteriores`)),
  `datos_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_nuevos`)),
  `ip_usuario` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `fecha_accion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mascotas`
--

CREATE TABLE `mascotas` (
  `id_mascota` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `especie` enum('perro','gato','pajaro','otro') NOT NULL,
  `raza` varchar(50) DEFAULT NULL,
  `edad` int(11) DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `genero` enum('macho','hembra') NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `historial_medico` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medicaciones`
--

CREATE TABLE `medicaciones` (
  `id_medicacion` int(11) NOT NULL,
  `id_mascota` int(11) NOT NULL,
  `nombre_medicamento` varchar(100) NOT NULL,
  `dosis` varchar(50) DEFAULT NULL,
  `frecuencia_horas` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `hora_primera_dosis` time NOT NULL,
  `instrucciones` text DEFAULT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medidas_mascota`
--

CREATE TABLE `medidas_mascota` (
  `id_medida` int(11) NOT NULL,
  `id_mascota` int(11) NOT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `altura` decimal(5,2) DEFAULT NULL,
  `longitud` decimal(5,2) DEFAULT NULL,
  `circunferencia_cuello` decimal(5,2) DEFAULT NULL,
  `fecha_medicion` date NOT NULL,
  `notas` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id_notificacion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo` enum('rutina','evento','recordatorio','sistema') NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensaje` text NOT NULL,
  `fecha_envio` datetime NOT NULL,
  `enviada` tinyint(1) DEFAULT 0,
  `leida` tinyint(1) DEFAULT 0,
  `fecha_lectura` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rutinas`
--

CREATE TABLE `rutinas` (
  `id_rutina` int(11) NOT NULL,
  `id_mascota` int(11) NOT NULL,
  `tipo_actividad` enum('comida','paseo','juego','medicacion','higiene','entrenamiento') NOT NULL,
  `nombre_actividad` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `hora_programada` time NOT NULL,
  `dias_semana` set('lunes','martes','miercoles','jueves','viernes','sabado','domingo') NOT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seguimiento_actividades`
--

CREATE TABLE `seguimiento_actividades` (
  `id_seguimiento` int(11) NOT NULL,
  `id_rutina` int(11) NOT NULL,
  `fecha_realizada` date NOT NULL,
  `hora_realizada` time DEFAULT NULL,
  `completada` tinyint(1) DEFAULT 1,
  `notas` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `rol` enum('usuario','veterinario','adiestrador') DEFAULT 'usuario',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `configuraciones_usuario`
--
ALTER TABLE `configuraciones_usuario`
  ADD PRIMARY KEY (`id_configuracion`),
  ADD UNIQUE KEY `unique_user_config` (`id_usuario`);

--
-- Indices de la tabla `contactos_veterinarios`
--
ALTER TABLE `contactos_veterinarios`
  ADD PRIMARY KEY (`id_contacto`),
  ADD KEY `idx_usuario` (`id_usuario`),
  ADD KEY `idx_es_principal` (`es_principal`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id_evento`),
  ADD KEY `idx_mascota` (`id_mascota`),
  ADD KEY `idx_tipo_evento` (`tipo_evento`),
  ADD KEY `idx_fecha_evento` (`fecha_evento`),
  ADD KEY `idx_completado` (`completado`),
  ADD KEY `idx_recordatorio_enviado` (`recordatorio_enviado`);

--
-- Indices de la tabla `historial_medico`
--
ALTER TABLE `historial_medico`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `idx_mascota` (`id_mascota`),
  ADD KEY `idx_tipo_registro` (`tipo_registro`),
  ADD KEY `idx_fecha_registro` (`fecha_registro`);

--
-- Indices de la tabla `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `idx_usuario` (`id_usuario`),
  ADD KEY `idx_accion` (`accion`),
  ADD KEY `idx_tabla_afectada` (`tabla_afectada`),
  ADD KEY `idx_fecha_accion` (`fecha_accion`);

--
-- Indices de la tabla `mascotas`
--
ALTER TABLE `mascotas`
  ADD PRIMARY KEY (`id_mascota`),
  ADD KEY `idx_usuario` (`id_usuario`),
  ADD KEY `idx_especie` (`especie`),
  ADD KEY `idx_fecha_creacion` (`fecha_creacion`);

--
-- Indices de la tabla `medicaciones`
--
ALTER TABLE `medicaciones`
  ADD PRIMARY KEY (`id_medicacion`),
  ADD KEY `idx_mascota` (`id_mascota`),
  ADD KEY `idx_activa` (`activa`),
  ADD KEY `idx_fecha_inicio` (`fecha_inicio`),
  ADD KEY `idx_fecha_fin` (`fecha_fin`);

--
-- Indices de la tabla `medidas_mascota`
--
ALTER TABLE `medidas_mascota`
  ADD PRIMARY KEY (`id_medida`),
  ADD KEY `idx_mascota` (`id_mascota`),
  ADD KEY `idx_fecha_medicion` (`fecha_medicion`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `idx_usuario` (`id_usuario`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_fecha_envio` (`fecha_envio`),
  ADD KEY `idx_enviada` (`enviada`),
  ADD KEY `idx_leida` (`leida`);

--
-- Indices de la tabla `rutinas`
--
ALTER TABLE `rutinas`
  ADD PRIMARY KEY (`id_rutina`),
  ADD KEY `idx_mascota` (`id_mascota`),
  ADD KEY `idx_tipo_actividad` (`tipo_actividad`),
  ADD KEY `idx_activa` (`activa`),
  ADD KEY `idx_hora_programada` (`hora_programada`);

--
-- Indices de la tabla `seguimiento_actividades`
--
ALTER TABLE `seguimiento_actividades`
  ADD PRIMARY KEY (`id_seguimiento`),
  ADD UNIQUE KEY `unique_routine_date` (`id_rutina`,`fecha_realizada`),
  ADD KEY `idx_rutina` (`id_rutina`),
  ADD KEY `idx_fecha_realizada` (`fecha_realizada`),
  ADD KEY `idx_completada` (`completada`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_fecha_registro` (`fecha_registro`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `configuraciones_usuario`
--
ALTER TABLE `configuraciones_usuario`
  MODIFY `id_configuracion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contactos_veterinarios`
--
ALTER TABLE `contactos_veterinarios`
  MODIFY `id_contacto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id_evento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_medico`
--
ALTER TABLE `historial_medico`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `logs_sistema`
--
ALTER TABLE `logs_sistema`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mascotas`
--
ALTER TABLE `mascotas`
  MODIFY `id_mascota` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `medicaciones`
--
ALTER TABLE `medicaciones`
  MODIFY `id_medicacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `medidas_mascota`
--
ALTER TABLE `medidas_mascota`
  MODIFY `id_medida` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rutinas`
--
ALTER TABLE `rutinas`
  MODIFY `id_rutina` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `seguimiento_actividades`
--
ALTER TABLE `seguimiento_actividades`
  MODIFY `id_seguimiento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `configuraciones_usuario`
--
ALTER TABLE `configuraciones_usuario`
  ADD CONSTRAINT `configuraciones_usuario_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `contactos_veterinarios`
--
ALTER TABLE `contactos_veterinarios`
  ADD CONSTRAINT `contactos_veterinarios_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD CONSTRAINT `eventos_ibfk_1` FOREIGN KEY (`id_mascota`) REFERENCES `mascotas` (`id_mascota`) ON DELETE CASCADE;

--
-- Filtros para la tabla `historial_medico`
--
ALTER TABLE `historial_medico`
  ADD CONSTRAINT `historial_medico_ibfk_1` FOREIGN KEY (`id_mascota`) REFERENCES `mascotas` (`id_mascota`) ON DELETE CASCADE;

--
-- Filtros para la tabla `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD CONSTRAINT `logs_sistema_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL;

--
-- Filtros para la tabla `mascotas`
--
ALTER TABLE `mascotas`
  ADD CONSTRAINT `mascotas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `medicaciones`
--
ALTER TABLE `medicaciones`
  ADD CONSTRAINT `medicaciones_ibfk_1` FOREIGN KEY (`id_mascota`) REFERENCES `mascotas` (`id_mascota`) ON DELETE CASCADE;

--
-- Filtros para la tabla `medidas_mascota`
--
ALTER TABLE `medidas_mascota`
  ADD CONSTRAINT `medidas_mascota_ibfk_1` FOREIGN KEY (`id_mascota`) REFERENCES `mascotas` (`id_mascota`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `rutinas`
--
ALTER TABLE `rutinas`
  ADD CONSTRAINT `rutinas_ibfk_1` FOREIGN KEY (`id_mascota`) REFERENCES `mascotas` (`id_mascota`) ON DELETE CASCADE;

--
-- Filtros para la tabla `seguimiento_actividades`
--
ALTER TABLE `seguimiento_actividades`
  ADD CONSTRAINT `seguimiento_actividades_ibfk_1` FOREIGN KEY (`id_rutina`) REFERENCES `rutinas` (`id_rutina`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
