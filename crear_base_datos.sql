-- ============================================================
--  AAA-Web — Script de creación de base de datos desde cero
--  Academia Antioqueña de Árbitros
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. arbitro
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `arbitro` (
  `idArbitro`        INT          NOT NULL AUTO_INCREMENT,
  `nombre`           VARCHAR(100) NOT NULL,
  `apellido`         VARCHAR(100) NOT NULL,
  `cedula`           VARCHAR(20)      NULL,
  `fechaNacimiento`  DATE             NULL,
  `correo`           VARCHAR(100)     NULL,
  `telefono`         VARCHAR(15)      NULL,
  `categoriaArbitro` VARCHAR(100)     NULL,
  PRIMARY KEY (`idArbitro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 2. categoriaArbitro  (catálogo de categorías de árbitros)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categoriaArbitro` (
  `nombre` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 3. equipo
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `equipo` (
  `idEquipo`    INT          NOT NULL AUTO_INCREMENT,
  `nombreEquipo` VARCHAR(200) NOT NULL,
  PRIMARY KEY (`idEquipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 4. torneo
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `torneo` (
  `idTorneo`    INT          NOT NULL AUTO_INCREMENT,
  `nombreTorneo` VARCHAR(200) NOT NULL,
  PRIMARY KEY (`idTorneo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 5. categoriaPagoArbitro
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categoriaPagoArbitro` (
  `idCategoriaPagoArbitro` INT          NOT NULL AUTO_INCREMENT,
  `idTorneo`               INT          NOT NULL,
  `nombreCategoria`        VARCHAR(100) NOT NULL,
  `pagoArbitro1`           INT          NOT NULL DEFAULT 0,
  `pagoArbitro2`           INT          NOT NULL DEFAULT 0,
  `pagoArbitro3`           INT          NOT NULL DEFAULT 0,
  `pagoArbitro4`           INT          NOT NULL DEFAULT 0,
  `tipopago`               VARCHAR(20)  NOT NULL DEFAULT 'INMEDIATO',
  PRIMARY KEY (`idCategoriaPagoArbitro`),
  CONSTRAINT `fk_catpago_torneo`
    FOREIGN KEY (`idTorneo`) REFERENCES `torneo` (`idTorneo`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 6. partido
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `partido` (
  `idPartido`              INT          NOT NULL AUTO_INCREMENT,
  `idEquipo1`              INT          NOT NULL,
  `idEquipo2`              INT          NOT NULL,
  `fecha`                  DATE         NOT NULL,
  `hora`                   TIME         NOT NULL,
  `canchaLugar`            VARCHAR(200)     NULL,
  `categoriaText`          VARCHAR(100)     NULL,
  `idCategoriaPagoArbitro` INT              NULL,
  `idTorneoPartido`        INT          NOT NULL,
  `idArbitro1`             INT              NULL,
  `idArbitro2`             INT              NULL,
  `idArbitro3`             INT              NULL,
  `idArbitro4`             INT              NULL,
  `observaciones`          TEXT             NULL,
  PRIMARY KEY (`idPartido`),
  KEY `idx_partido_fecha`    (`fecha`),
  KEY `idx_partido_torneo`   (`idTorneoPartido`),
  CONSTRAINT `fk_partido_equipo1`
    FOREIGN KEY (`idEquipo1`) REFERENCES `equipo` (`idEquipo`),
  CONSTRAINT `fk_partido_equipo2`
    FOREIGN KEY (`idEquipo2`) REFERENCES `equipo` (`idEquipo`),
  CONSTRAINT `fk_partido_torneo`
    FOREIGN KEY (`idTorneoPartido`) REFERENCES `torneo` (`idTorneo`),
  CONSTRAINT `fk_partido_catpago`
    FOREIGN KEY (`idCategoriaPagoArbitro`) REFERENCES `categoriaPagoArbitro` (`idCategoriaPagoArbitro`),
  CONSTRAINT `fk_partido_arb1`
    FOREIGN KEY (`idArbitro1`) REFERENCES `arbitro` (`idArbitro`),
  CONSTRAINT `fk_partido_arb2`
    FOREIGN KEY (`idArbitro2`) REFERENCES `arbitro` (`idArbitro`),
  CONSTRAINT `fk_partido_arb3`
    FOREIGN KEY (`idArbitro3`) REFERENCES `arbitro` (`idArbitro`),
  CONSTRAINT `fk_partido_arb4`
    FOREIGN KEY (`idArbitro4`) REFERENCES `arbitro` (`idArbitro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 7. contador_impresion
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contador_impresion` (
  `idArbitro`       INT      NOT NULL,
  `totalImpresiones` INT     NOT NULL DEFAULT 0,
  `ultimaImpresion` DATETIME     NULL,
  PRIMARY KEY (`idArbitro`),
  CONSTRAINT `fk_contador_arbitro`
    FOREIGN KEY (`idArbitro`) REFERENCES `arbitro` (`idArbitro`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 8. notificaciones
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `idNotificacion` INT          NOT NULL AUTO_INCREMENT,
  `titulo`         VARCHAR(200) NOT NULL,
  `mensaje`        TEXT             NULL,
  `fecha`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `leida`          TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`idNotificacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
