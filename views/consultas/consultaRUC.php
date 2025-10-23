<div class="consulta-ruc-container">
    <div class="container">
        <div class="window-header">
            <span class="window-title">□ SUNAT - CONSULTA RUC</span>
        </div>

        <div class="page-title">
            <h1>CONSULTA RUC</h1>
        </div>

        <div class="content-wrapper">
            <div class="search-section">
                <form method="POST" action="" class="search-form" id="searchFormRUC">
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
                    <div class="btn-group">
                        <button type="submit" name="buscar" class="btn btn-icon">🔍</button>
                        <button type="button" class="btn btn-icon">📄</button>
                        <button type="button" class="btn btn-icon">📌</button>
                    </div>
                </form>
            </div>

            <?php if (isset($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <div class="results-section">
                <div class="form-row">
                    <div class="form-group full-width">
                        <label class="form-label">Código de Ubigeo:</label>
                        <input type="text" class="form-input white-bg" readonly
                               value="<?php echo isset($contribuyente['codigo_ubigeo']) ? htmlspecialchars($contribuyente['codigo_ubigeo']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group third-width">
                        <label class="form-label">Departamento:</label>
                        <input type="text" class="form-input white-bg" readonly
                               value="<?php echo isset($contribuyente['departamento']) ? htmlspecialchars($contribuyente['departamento']) : ''; ?>">
                    </div>
                    <div class="form-group third-width">
                        <label class="form-label">Provincia:</label>
                        <input type="text" class="form-input white-bg" readonly
                               value="<?php echo isset($contribuyente['provincia']) ? htmlspecialchars($contribuyente['provincia']) : ''; ?>">
                    </div>
                    <div class="form-group third-width">
                        <label class="form-label">Distrito:</label>
                        <input type="text" class="form-input white-bg" readonly
                               value="<?php echo isset($contribuyente['distrito']) ? htmlspecialchars($contribuyente['distrito']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label class="form-label">Actividad Económica:</label>
                        <input type="text" class="form-input white-bg" readonly
                               value="<?php echo isset($contribuyente['actividad_economica']) ? htmlspecialchars($contribuyente['actividad_economica']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label class="form-label">Estado del Contribuyente:</label>
                        <input type="text" class="form-input" readonly
                               value="<?php echo isset($contribuyente['estado_contribuyente']) ? htmlspecialchars($contribuyente['estado_contribuyente']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group third-width">
                        <label class="form-label">Fecha de Actualización:</label>
                        <input type="text" class="form-input white-bg" readonly
                               value="<?php echo isset($contribuyente['fecha_actualizacion']) ? htmlspecialchars($contribuyente['fecha_actualizacion']) : ''; ?>">
                    </div>
                    <div class="form-group third-width">
                        <label class="form-label">Fecha de Alta:</label>
                        <input type="text" class="form-input white-bg" readonly
                               value="<?php echo isset($contribuyente['fecha_alta']) ? htmlspecialchars($contribuyente['fecha_alta']) : ''; ?>">
                    </div>
                    <div class="form-group third-width">
                        <label class="form-label">Fecha de Baja:</label>
                        <input type="text" class="form-input white-bg" readonly
                               value="<?php echo isset($contribuyente['fecha_baja']) ? htmlspecialchars($contribuyente['fecha_baja']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label class="form-label">Tipo de Persona:</label>
                        <input type="text" class="form-input" readonly
                               value="<?php echo isset($contribuyente['tipo_persona']) ? htmlspecialchars($contribuyente['tipo_persona']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label class="form-label">Tipo de Contribuyente:</label>
                        <input type="text" class="form-input" readonly
                               value="<?php echo isset($contribuyente['tipo_contribuyente']) ? htmlspecialchars($contribuyente['tipo_contribuyente']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label class="form-label">RUC:</label>
                        <input type="text" class="form-input" readonly
                               value="<?php echo isset($contribuyente['ruc']) ? htmlspecialchars($contribuyente['ruc']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label class="form-label">Nombre y/o Razón Social:</label>
                        <input type="text" class="form-input" readonly
                               value="<?php echo isset($contribuyente['razon_social']) ? htmlspecialchars($contribuyente['razon_social']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label class="form-label">Tipo de Zona:</label>
                        <input type="text" class="form-input white-bg" readonly
                               value="<?php echo isset($contribuyente['tipo_zona']) ? htmlspecialchars($contribuyente['tipo_zona']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label class="form-label">Tipo de Vía:</label>
                        <input type="text" class="form-input white-bg" readonly
                               value="<?php echo isset($contribuyente['tipo_via']) ? htmlspecialchars($contribuyente['tipo_via']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label class="form-label">Nombre de Vía:</label>
                        <input type="text" class="form-input white-bg" readonly
                               value="<?php echo isset($contribuyente['nombre_via']) ? htmlspecialchars($contribuyente['nombre_via']) : ''; ?>">
                    </div>
                    <label class="form-label" style="min-width: 50px;">Nro.:</label>
                    <input type="text" class="form-input white-bg small-input" readonly
                           value="<?php echo isset($contribuyente['numero']) ? htmlspecialchars($contribuyente['numero']) : ''; ?>">
                    <label class="form-label" style="min-width: 50px;">Int.:</label>
                    <input type="text" class="form-input white-bg small-input" readonly
                           value="<?php echo isset($contribuyente['interior']) ? htmlspecialchars($contribuyente['interior']) : ''; ?>">
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label class="form-label">Nombre de la Zona:</label>
                        <input type="text" class="form-input white-bg" readonly
                               value="<?php echo isset($contribuyente['nombre_zona']) ? htmlspecialchars($contribuyente['nombre_zona']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label class="form-label">Referencia:</label>
                        <input type="text" class="form-input white-bg" readonly
                               value="<?php echo isset($contribuyente['referencia']) ? htmlspecialchars($contribuyente['referencia']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label class="form-label">Condición del Domicilio:</label>
                        <input type="text" class="form-input" readonly
                               value="<?php echo isset($contribuyente['condicion_domicilio']) ? htmlspecialchars($contribuyente['condicion_domicilio']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label class="form-label">Dependencia:</label>
                        <input type="text" class="form-input white-bg" readonly
                               value="<?php echo isset($contribuyente['dependencia']) ? htmlspecialchars($contribuyente['dependencia']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label class="form-label">Código Secuencia:</label>
                        <input type="text" class="form-input white-bg" readonly
                               value="<?php echo isset($contribuyente['codigo_secuencia']) ? htmlspecialchars($contribuyente['codigo_secuencia']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group half-width">
                        <label class="form-label">Estado Activo:</label>
                        <input type="text" class="form-input" readonly
                               value="<?php echo isset($contribuyente['estado_activo']) ? htmlspecialchars($contribuyente['estado_activo']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group half-width">
                        <label class="form-label">Estado Habido:</label>
                        <input type="text" class="form-input" readonly
                               value="<?php echo isset($contribuyente['estado_habido']) ? htmlspecialchars($contribuyente['estado_habido']) : ''; ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>