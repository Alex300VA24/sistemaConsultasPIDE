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
    <div class="activity-section">
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
    </div>

    <!-- Footer -->
    <div class="footer-section">
        <div class="footer-content">
            <p><i class="fas fa-info-circle"></i> Sistema de Consultas PIDE v1.0 - Plataforma de Interoperabilidad del Estado Peruano</p>
            <p class="footer-note">Acceso autorizado únicamente para entidades del Estado</p>
        </div>
    </div>
</div>