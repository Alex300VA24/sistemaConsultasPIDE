<div class="consulta-dni-container">
    <div class="content-wrapper">
        <!-- Formulario de búsqueda -->
        <div class="search-section">
            <div id="alertContainer"></div>
            <form method="POST" action="" class="search-form" id="searchFormDNI">
                <div class="form-group">
                    <label for="dni">Número de DNI:</label>
                    <input 
                        type="text" 
                        id="dniInput" 
                        name="dni" 
                        maxlength="8" 
                        pattern="[0-9]{8}" 
                        placeholder="Ingrese 8 dígitos"
                        value="<?php echo isset($_POST['dni']) ? htmlspecialchars($_POST['dni']) : ''; ?>"
                        required
                    >
                </div>
                <div class="btn-group">
                    <button type="submit" name="buscar" class="btn btn-search" id="btnBuscarDNI">
                        🔍 Buscar
                    </button>
                    <button type="button" class="btn btn-clear" onclick="limpiarFormularioDNI()">
                        📄 Limpiar
                    </button>
                </div>
            </form>
        </div>

        <?php if (isset($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Sección de resultados -->
        <div class="results-container">
            <!-- Foto -->
            <div class="photo-section">
                <div class="photo-frame" id="photoContainer">
                    <?php if (isset($persona['foto']) && !empty($persona['foto'])): ?>
                        <img src="<?php echo htmlspecialchars($persona['foto']); ?>" alt="Foto">
                    <?php else: ?>
                        <div class="photo-placeholder"></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Información personal -->
            <div class="info-section">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">DNI</span>
                        <div class="info-value" id="result-dni">
                            <?php echo isset($persona['dni']) ? htmlspecialchars($persona['dni']) : ''; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Nombres</span>
                        <div class="info-value" id="result-nombres">
                            <?php echo isset($persona['nombres']) ? htmlspecialchars($persona['nombres']) : ''; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Apellido Paterno</span>
                        <div class="info-value" id="result-paterno">
                            <?php echo isset($persona['apellido_paterno']) ? htmlspecialchars($persona['apellido_paterno']) : ''; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Apellido Materno</span>
                        <div class="info-value" id="result-materno">
                            <?php echo isset($persona['apellido_materno']) ? htmlspecialchars($persona['apellido_materno']) : ''; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Estado Civil</span>
                        <div class="info-value" id="result-estado-civil">
                            <?php echo isset($persona['estado_civil']) ? htmlspecialchars($persona['estado_civil']) : ''; ?>
                        </div>
                    </div>
                    <div class="info-item full-width">
                        <span class="info-label">Dirección</span>
                        <div class="info-value" id="result-direccion">
                            <?php echo isset($persona['direccion']) ? htmlspecialchars($persona['direccion']) : ''; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Restricción</span>
                        <div class="info-value" id="result-restriccion">
                            <?php echo isset($persona['restriccion']) ? htmlspecialchars($persona['restriccion']) : ''; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Ubigeo</span>
                        <div class="info-value" id="result-ubigeo">
                            <?php echo isset($persona['ubigeo']) ? htmlspecialchars($persona['ubigeo']) : ''; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>