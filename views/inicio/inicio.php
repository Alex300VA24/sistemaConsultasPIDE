<div class="inicio-container">
    <!-- Header Principal -->
    <div class="header-section">
        <div class="logo-section">
            <i class="fas fa-search-location"></i>
            <div class="header-text">
                <h1>Sistema de Consultas PIDE</h1>
                <p>Plataforma de Interoperabilidad del Estado Peruano</p>
            </div>
        </div>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span id="usuarioNombre">Usuario <?= htmlspecialchars($_SESSION['ROL_nombre'] ?? '') ?></span>
        </div>
    </div>

    <!-- Cards de Consulta -->
    <div class="consultas-grid">
        <!-- RENIEC -->
        <div class="consulta-card reniec-card">
            <div class="card-header">
                <i class="fas fa-id-card"></i>
                <h2>RENIEC</h2>
            </div>
            <div class="card-body">
                <p>Registro Nacional de Identificación y Estado Civil</p>
                <ul class="features-list">
                    <li><i class="fas fa-check-circle"></i> Consulta por DNI</li>
                    <li><i class="fas fa-check-circle"></i> Datos personales</li>
                    <li><i class="fas fa-check-circle"></i> Estado del documento</li>
                    <li><i class="fas fa-check-circle"></i> Foto y firma digital</li>
                </ul>
            </div>
            <div class="card-footer">
                <button class="btn btn-reniec" onclick="irConsultaReniec()">
                    <i class="fas fa-search"></i> Consultar RENIEC
                </button>
            </div>
            <div class="card-badge">
                <i class="fas fa-shield-alt"></i>
            </div>
        </div>

        <!-- SUNAT -->
        <div class="consulta-card sunat-card">
            <div class="card-header">
                <i class="fas fa-file-invoice"></i>
                <h2>SUNAT</h2>
            </div>
            <div class="card-body">
                <p>Superintendencia Nacional de Aduanas y de Administración Tributaria</p>
                <ul class="features-list">
                    <li><i class="fas fa-check-circle"></i> Consulta por RUC</li>
                    <li><i class="fas fa-check-circle"></i> Razón social</li>
                    <li><i class="fas fa-check-circle"></i> Estado del contribuyente</li>
                    <li><i class="fas fa-check-circle"></i> Domicilio fiscal</li>
                </ul>
            </div>
            <div class="card-footer">
                <button class="btn btn-sunat" onclick="irConsultaSunat()">
                    <i class="fas fa-search"></i> Consultar SUNAT
                </button>
            </div>
            <div class="card-badge">
                <i class="fas fa-file-alt"></i>
            </div>
        </div>

        <!-- SUNARP -->
        <div class="consulta-card sunarp-card">
            <div class="card-header">
                <i class="fas fa-home"></i>
                <h2>SUNARP</h2>
            </div>
            <div class="card-body">
                <p>Superintendencia Nacional de los Registros Públicos</p>
                <ul class="features-list">
                    <li><i class="fas fa-check-circle"></i> Consulta registral</li>
                    <li><i class="fas fa-check-circle"></i> Propiedades inmuebles</li>
                    <li><i class="fas fa-check-circle"></i> Vehículos registrados</li>
                    <li><i class="fas fa-check-circle"></i> Personas jurídicas</li>
                </ul>
            </div>
            <div class="card-footer">
                <button class="btn btn-sunarp" onclick="irConsultaSunarp()">
                    <i class="fas fa-search"></i> Consultar SUNARP
                </button>
            </div>
            <div class="card-badge">
                <i class="fas fa-building"></i>
            </div>
        </div>
    </div>


    <!-- Sección de Actividad Reciente -->
    <!-- <div class="activity-section">
        <div class="section-title">
            <i class="fas fa-history"></i>
            <h3>Actividad Reciente</h3>
        </div>
        <div class="activity-list">
            <div class="activity-item">
                <div class="activity-icon reniec">
                    <i class="fas fa-id-card"></i>
                </div>
                <div class="activity-content">
                    <p class="activity-description">Consulta RENIEC - DNI: 12345678</p>
                    <span class="activity-time"><i class="far fa-clock"></i> Hace 5 minutos</span>
                </div>
                <span class="activity-status success">Exitosa</span>
            </div>
            <div class="activity-item">
                <div class="activity-icon sunat">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div class="activity-content">
                    <p class="activity-description">Consulta SUNAT - RUC: 20123456789</p>
                    <span class="activity-time"><i class="far fa-clock"></i> Hace 12 minutos</span>
                </div>
                <span class="activity-status success">Exitosa</span>
            </div>
            <div class="activity-item">
                <div class="activity-icon sunarp">
                    <i class="fas fa-home"></i>
                </div>
                <div class="activity-content">
                    <p class="activity-description">Consulta SUNARP - Partida: 11234567</p>
                    <span class="activity-time"><i class="far fa-clock"></i> Hace 28 minutos</span>
                </div>
                <span class="activity-status success">Exitosa</span>
            </div>
            <div class="activity-item">
                <div class="activity-icon reniec">
                    <i class="fas fa-id-card"></i>
                </div>
                <div class="activity-content">
                    <p class="activity-description">Consulta RENIEC - DNI: 87654321</p>
                    <span class="activity-time"><i class="far fa-clock"></i> Hace 45 minutos</span>
                </div>
                <span class="activity-status error">Fallida</span>
            </div>
        </div>
    </div> -->

    <!-- Footer -->
    <div class="footer-section">
        <div class="footer-content">
            <p><i class="fas fa-info-circle"></i> Sistema de Consultas PIDE v1.0 - Plataforma de Interoperabilidad del Estado Peruano</p>
            <p class="footer-note">Acceso autorizado únicamente para entidades del Estado</p>
        </div>
    </div>
</div>

<!-- Modal de cambio de password obligatorio -->
<div id="modalPasswordObligatorio" class="modal-password-overlay">
    <div class="modal-password-content">
        <div class="modal-password-header">
            <div class="modal-password-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h2 class="modal-password-title">Cambio de Contraseña Requerido</h2>
            <p class="modal-password-subtitle">
                Tu contraseña ha expirado. Por seguridad, debes cambiarla para continuar.
            </p>
        </div>

        <div class="modal-password-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Importante:</strong> Si no cambias tu contraseña, perderás acceso a los servicios de RENIEC y otros módulos del sistema.
        </div>

        <form id="formCambioPassword" class="modal-password-form">
            <div class="form-group-password">
                <label for="passwordActual">Contraseña Actual</label>
                <div class="password-input-wrapper">
                    <input 
                        type="password" 
                        id="passwordActual" 
                        name="passwordActual"
                        required
                        placeholder="Ingresa tu contraseña actual"
                    >
                    <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility('passwordActual')"></i>
                </div>
            </div>

            <div class="form-group-password">
                <label for="passwordNueva">Nueva Contraseña</label>
                <div class="password-input-wrapper">
                    <input 
                        type="password" 
                        id="passwordNueva" 
                        name="passwordNueva"
                        required
                        placeholder="Ingresa tu nueva contraseña"
                        oninput="verificarFortalezaPassword(this.value)"
                    >
                    <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility('passwordNueva')"></i>
                </div>
                <div id="passwordStrength" class="password-strength">
                    <div class="strength-bar">
                        <div class="strength-bar-fill"></div>
                    </div>
                    <span class="strength-text"></span>
                </div>
            </div>

            <div class="form-group-password">
                <label for="passwordConfirmar">Confirmar Nueva Contraseña</label>
                <div class="password-input-wrapper">
                    <input 
                        type="password" 
                        id="passwordConfirmar" 
                        name="passwordConfirmar"
                        required
                        placeholder="Confirma tu nueva contraseña"
                    >
                    <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility('passwordConfirmar')"></i>
                </div>
            </div>

            <div class="password-requirements">
                <div class="requirement" id="req-length">
                    <i class="fas fa-circle"></i>
                    <span>Mínimo 8 caracteres</span>
                </div>
                <div class="requirement" id="req-uppercase">
                    <i class="fas fa-circle"></i>
                    <span>Al menos una letra mayúscula</span>
                </div>
                <div class="requirement" id="req-lowercase">
                    <i class="fas fa-circle"></i>
                    <span>Al menos una letra minúscula</span>
                </div>
                <div class="requirement" id="req-number">
                    <i class="fas fa-circle"></i>
                    <span>Al menos un número</span>
                </div>
                <div class="requirement" id="req-special">
                    <i class="fas fa-circle"></i>
                    <span>Al menos un carácter especial (@$!%*?&#)</span>
                </div>
            </div>

            <div id="errorMessage" class="error-message"></div>
            <div id="successMessage" class="success-message"></div>

            <div class="modal-password-actions">
                <button type="button" class="btn-password btn-password-secondary" onclick="recordarMasTarde()">
                    Recordar más tarde
                </button>
                <button type="submit" class="btn-password btn-password-primary" id="btnCambiarPass">
                    Cambiar Contraseña
                </button>
            </div>
        </form>
    </div>
</div>