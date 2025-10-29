<div class="consulta-ruc-container">
    <div class="page-title">
        <h1><i class="fas fa-magnifying-glass"></i> Consulta RUC - SUNAT</h1>
    </div>
    
    <div class="content-wrapper">
        <div class="search-section">
            <form method="POST" action="" class="search-form" id="searchFormRUC">
                <div>
                    <label for="ruc">Nro. RUC:</label>
                    <input 
                        type="text" 
                        id="ruc" 
                        name="ruc" 
                        maxlength="11" 
                        pattern="[0-9]{11}" 
                        placeholder="Ingrese 11 dígitos"
                        value="<?php echo isset($_POST['ruc']) ? htmlspecialchars($_POST['ruc']) : ''; ?>"
                        required
                    >
                </div>
                <div class="btn-group">
                    <button type="submit" name="buscar" class="btn btn-icon" title="Buscar">
                        <i class="fas fa-magnifying-glass"></i>
                        <span>Buscar</span>
                    </button>
                    <button type="button" class="btn btn-icon" onclick="limpiarFormularioRUC()" title="Limpiar">
                        <i class="fas fa-eraser"></i>
                        <span>Limpiar</span>
                    </button>
                </div>
            </form>
        </div>

        <?php if (isset($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : ($tipo_mensaje === 'danger' ? 'exclamation-circle' : ($tipo_mensaje === 'warning' ? 'exclamation-triangle' : 'info-circle')); ?>"></i>
                <span><?php echo $mensaje; ?></span>
            </div>
        <?php endif; ?>

        <div class="results-section">
            <div class="info-grid">
                <!-- Fila 1: Ubigeo completo -->
                <div class="info-item full-width">
                    <span class="info-label">Código de Ubigeo</span>
                    <div class="info-value white-bg">
                        <?php echo isset($contribuyente['codigo_ubigeo']) ? htmlspecialchars($contribuyente['codigo_ubigeo']) : ''; ?>
                    </div>
                </div>

                <!-- Fila 2: Departamento, Provincia, Distrito -->
                <div class="info-item">
                    <span class="info-label">Departamento</span>
                    <div class="info-value white-bg">
                        <?php echo isset($contribuyente['departamento']) ? htmlspecialchars($contribuyente['departamento']) : ''; ?>
                    </div>
                </div>
                <div class="info-item">
                    <span class="info-label">Provincia</span>
                    <div class="info-value white-bg">
                        <?php echo isset($contribuyente['provincia']) ? htmlspecialchars($contribuyente['provincia']) : ''; ?>
                    </div>
                </div>
                <div class="info-item">
                    <span class="info-label">Distrito</span>
                    <div class="info-value white-bg">
                        <?php echo isset($contribuyente['distrito']) ? htmlspecialchars($contribuyente['distrito']) : ''; ?>
                    </div>
                </div>

                <!-- Fila 3: Actividad Económica -->
                <div class="info-item full-width">
                    <span class="info-label">Actividad Económica</span>
                    <div class="info-value white-bg">
                        <?php echo isset($contribuyente['actividad_economica']) ? htmlspecialchars($contribuyente['actividad_economica']) : ''; ?>
                    </div>
                </div>

                <!-- Fila 4: Estado del Contribuyente -->
                <div class="info-item full-width">
                    <span class="info-label">Estado del Contribuyente</span>
                    <div class="info-value">
                        <?php echo isset($contribuyente['estado_contribuyente']) ? htmlspecialchars($contribuyente['estado_contribuyente']) : ''; ?>
                    </div>
                </div>

                <!-- Fila 5: Fechas -->
                <div class="info-item">
                    <span class="info-label">Fecha de Actualización</span>
                    <div class="info-value white-bg">
                        <?php echo isset($contribuyente['fecha_actualizacion']) ? htmlspecialchars($contribuyente['fecha_actualizacion']) : ''; ?>
                    </div>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha de Alta</span>
                    <div class="info-value white-bg">
                        <?php echo isset($contribuyente['fecha_alta']) ? htmlspecialchars($contribuyente['fecha_alta']) : ''; ?>
                    </div>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha de Baja</span>
                    <div class="info-value white-bg">
                        <?php echo isset($contribuyente['fecha_baja']) ? htmlspecialchars($contribuyente['fecha_baja']) : ''; ?>
                    </div>
                </div>

                <!-- Fila 6: Tipo de Persona -->
                <div class="info-item full-width">
                    <span class="info-label">Tipo de Persona</span>
                    <div class="info-value">
                        <?php echo isset($contribuyente['tipo_persona']) ? htmlspecialchars($contribuyente['tipo_persona']) : ''; ?>
                    </div>
                </div>

                <!-- Fila 7: Tipo de Contribuyente -->
                <div class="info-item full-width">
                    <span class="info-label">Tipo de Contribuyente</span>
                    <div class="info-value">
                        <?php echo isset($contribuyente['tipo_contribuyente']) ? htmlspecialchars($contribuyente['tipo_contribuyente']) : ''; ?>
                    </div>
                </div>

                <!-- Fila 8: RUC -->
                <div class="info-item full-width">
                    <span class="info-label">RUC</span>
                    <div class="info-value">
                        <?php echo isset($contribuyente['ruc']) ? htmlspecialchars($contribuyente['ruc']) : ''; ?>
                    </div>
                </div>

                <!-- Fila 9: Razón Social -->
                <div class="info-item full-width">
                    <span class="info-label">Nombre y/o Razón Social</span>
                    <div class="info-value">
                        <?php echo isset($contribuyente['razon_social']) ? htmlspecialchars($contribuyente['razon_social']) : ''; ?>
                    </div>
                </div>

                <!-- Fila 10: Tipo de Zona -->
                <div class="info-item full-width">
                    <span class="info-label">Tipo de Zona</span>
                    <div class="info-value white-bg">
                        <?php echo isset($contribuyente['tipo_zona']) ? htmlspecialchars($contribuyente['tipo_zona']) : ''; ?>
                    </div>
                </div>

                <!-- Fila 11: Tipo de Vía -->
                <div class="info-item full-width">
                    <span class="info-label">Tipo de Vía</span>
                    <div class="info-value white-bg">
                        <?php echo isset($contribuyente['tipo_via']) ? htmlspecialchars($contribuyente['tipo_via']) : ''; ?>
                    </div>
                </div>

                <!-- Fila 12: Nombre de Vía, Número e Interior -->
                <div class="info-item half-width">
                    <span class="info-label">Nombre de Vía</span>
                    <div class="info-value white-bg">
                        <?php echo isset($contribuyente['nombre_via']) ? htmlspecialchars($contribuyente['nombre_via']) : ''; ?>
                    </div>
                </div>
                <div class="info-item">
                    <span class="info-label">Número</span>
                    <div class="info-value white-bg">
                        <?php echo isset($contribuyente['numero']) ? htmlspecialchars($contribuyente['numero']) : ''; ?>
                    </div>
                </div>
                <div class="info-item">
                    <span class="info-label">Interior</span>
                    <div class="info-value white-bg">
                        <?php echo isset($contribuyente['interior']) ? htmlspecialchars($contribuyente['interior']) : ''; ?>
                    </div>
                </div>

                <!-- Fila 13: Nombre de la Zona -->
                <div class="info-item full-width">
                    <span class="info-label">Nombre de la Zona</span>
                    <div class="info-value white-bg">
                        <?php echo isset($contribuyente['nombre_zona']) ? htmlspecialchars($contribuyente['nombre_zona']) : ''; ?>
                    </div>
                </div>

                <!-- Fila 14: Referencia -->
                <div class="info-item full-width">
                    <span class="info-label">Referencia</span>
                    <div class="info-value white-bg">
                        <?php echo isset($contribuyente['referencia']) ? htmlspecialchars($contribuyente['referencia']) : ''; ?>
                    </div>
                </div>

                <!-- Fila 15: Condición del Domicilio -->
                <div class="info-item full-width">
                    <span class="info-label">Condición del Domicilio</span>
                    <div class="info-value">
                        <?php echo isset($contribuyente['condicion_domicilio']) ? htmlspecialchars($contribuyente['condicion_domicilio']) : ''; ?>
                    </div>
                </div>

                <!-- Fila 16: Dependencia -->
                <div class="info-item full-width">
                    <span class="info-label">Dependencia</span>
                    <div class="info-value white-bg">
                        <?php echo isset($contribuyente['dependencia']) ? htmlspecialchars($contribuyente['dependencia']) : ''; ?>
                    </div>
                </div>

                <!-- Fila 17: Código Secuencia -->
                <div class="info-item full-width">
                    <span class="info-label">Código Secuencia</span>
                    <div class="info-value white-bg">
                        <?php echo isset($contribuyente['codigo_secuencia']) ? htmlspecialchars($contribuyente['codigo_secuencia']) : ''; ?>
                    </div>
                </div>

                <!-- Fila 18: Estados -->
                <div class="info-item">
                    <span class="info-label">Estado Activo</span>
                    <div class="info-value">
                        <?php echo isset($contribuyente['estado_activo']) ? htmlspecialchars($contribuyente['estado_activo']) : ''; ?>
                    </div>
                </div>
                <div class="info-item">
                    <span class="info-label">Estado Habido</span>
                    <div class="info-value">
                        <?php echo isset($contribuyente['estado_habido']) ? htmlspecialchars($contribuyente['estado_habido']) : ''; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>