document.addEventListener("DOMContentLoaded", () => {

    const btnCrear = document.getElementById("btnCrear");
    const alertContainer = document.getElementById("alertContainer");
    const reniecSection = document.getElementById("reniecSection");

    // Mostrar u ocultar la sección RENIEC según tipo de documento
    const perDocumentoTipo = document.getElementById("perDocumentoTipo");
    perDocumentoTipo.addEventListener("change", () => {
        if (perDocumentoTipo.value === "1") { // 1 = DNI
            reniecSection.style.display = "block";
        } else {
            reniecSection.style.display = "none";
        }
    });

    // Función principal
    window.crearUsuario = async function () {
        limpiarAlertas();

        // Recolectar datos
        const data = {
            perTipo: getValue("perTipo"),
            perDocumentoTipo: getValue("perDocumentoTipo"),
            perDocumentoNum: getValue("perDocumentoNum"),
            perNombre: getValue("perNombre"),
            perApellidoPat: getValue("perApellidoPat"),
            perApellidoMat: getValue("perApellidoMat"),
            perSexo: getValue("perSexo"),
            perEmail: getValue("perEmail"),

            usuLogin: getValue("usuLogin"),
            usuPass: getValue("usuPass"),
            usuPassConfirm: getValue("usuPassConfirm"),
            usuPermiso: getValue("usuPermiso"),
            usuEstado: getValue("usuEstado"),
            cui: getValue("cui"),

            reniecDni: getValue("reniecDni"),
            reniecRuc: getValue("reniecRuc"),
        };

        // Validaciones mínimas
        if (!data.perTipo || !data.perDocumentoTipo || !data.perDocumentoNum || !data.perNombre || !data.perApellidoPat) {
            mostrarAlerta("warning", "Completa todos los campos personales obligatorios.");
            return;
        }

        if (!data.usuLogin || !data.usuPass || !data.usuPassConfirm) {
            mostrarAlerta("warning", "Completa los campos de usuario y contraseña.");
            return;
        }

        if (data.usuPass !== data.usuPassConfirm) {
            mostrarAlerta("danger", "Las contraseñas no coinciden.");
            return;
        }

        // Bloquear botón y mostrar carga
        btnCrear.disabled = true;
        const loading = btnCrear.querySelector(".loading");
        loading.style.display = "inline-block";

        try {
            const response = await api.crearUsuario(data);

            const result = await response.json();

            if (response.ok && result.success) {
                mostrarAlerta("success", result.message || "✅ Usuario creado correctamente.");
                limpiarFormulario();
            } else {
                mostrarAlerta("danger", result.message || "⚠️ Error al crear el usuario.");
            }

        } catch (error) {
            console.error("Error en crearUsuario:", error);
            mostrarAlerta("danger", "❌ Error de conexión con el servidor.");
        } finally {
            btnCrear.disabled = false;
            loading.style.display = "none";
        }
    };

    // Función limpiar formulario
    window.limpiarFormulario = function () {
        document.querySelectorAll(".usuario-container input, .usuario-container select").forEach(el => {
            el.value = "";
        });
        reniecSection.style.display = "none";
    };

    // ===== Utilitarios =====
    function getValue(id) {
        const el = document.getElementById(id);
        return el ? el.value.trim() : "";
    }

    function mostrarAlerta(tipo, mensaje) {
        const div = document.createElement("div");
        div.className = `alert alert-${tipo}`;
        div.textContent = mensaje;
        alertContainer.innerHTML = "";
        alertContainer.appendChild(div);
        setTimeout(() => div.remove(), 5000);
    }

    function limpiarAlertas() {
        alertContainer.innerHTML = "";
    }
});
