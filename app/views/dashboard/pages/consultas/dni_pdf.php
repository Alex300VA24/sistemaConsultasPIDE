<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta DNI - <?= htmlspecialchars($dni) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #1f2937; padding: 30px; }

        .header { text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 3px solid #2563eb; }
        .header h1 { font-size: 22px; color: #1f2937; margin: 0; }
        .header p { font-size: 12px; color: #6b7280; margin-top: 5px; }

        .photo-row { text-align: center; margin-bottom: 20px; }
        .photo-row img { max-width: 150px; border-radius: 8px; border: 2px solid #e5e7eb; }

        .info-grid { width: 100%; border-collapse: collapse; }
        .info-grid td { padding: 10px 14px; border: 1px solid #e5e7eb; vertical-align: top; }
        .info-grid .label { font-size: 10px; font-weight: 600; color: #6b7280; text-transform: uppercase; display: block; margin-bottom: 4px; }
        .info-grid .value { font-size: 14px; font-weight: 600; color: #1f2937; }
        .info-grid .full { grid-column: 1 / -1; }

        .footer { text-align: center; margin-top: 25px; padding-top: 15px; border-top: 1px solid #e5e7eb; font-size: 10px; color: #9ca3af; }

        .no-photo { padding: 30px; text-align: center; color: #9ca3af; font-size: 12px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Consulta DNI - RENIEC</h1>
        <p>Fecha: <?= date('d/m/Y H:i') ?></p>
    </div>

    <?php if (!empty($foto)): ?>
        <div class="photo-row">
            <img src="<?= $foto ?>" alt="Foto del DNI">
        </div>
    <?php else: ?>
        <div class="photo-row no-photo">
            <p>Sin fotografia disponible</p>
        </div>
    <?php endif; ?>

    <table class="info-grid">
        <tr>
            <td>
                <span class="label">DNI</span>
                <span class="value"><?= htmlspecialchars($dni) ?></span>
            </td>
            <td>
                <span class="label">Nombres</span>
                <span class="value"><?= htmlspecialchars($nombres) ?></span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Apellido Paterno</span>
                <span class="value"><?= htmlspecialchars($apellido_paterno) ?></span>
            </td>
            <td>
                <span class="label">Apellido Materno</span>
                <span class="value"><?= htmlspecialchars($apellido_materno) ?></span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Estado Civil</span>
                <span class="value"><?= htmlspecialchars($estado_civil) ?></span>
            </td>
            <td>
                <span class="label">Ubigeo</span>
                <span class="value"><?= htmlspecialchars($ubigeo) ?></span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="label">Direccion</span>
                <span class="value"><?= htmlspecialchars($direccion) ?></span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="label">Restriccion</span>
                <span class="value"><?= htmlspecialchars($restriccion) ?></span>
            </td>
        </tr>
    </table>

    <div class="footer">
        Sistema de Consultas PIDE - Documento generado electronicamente
    </div>

</body>
</html>
