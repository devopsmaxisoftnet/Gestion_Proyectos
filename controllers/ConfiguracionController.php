<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/GoogleDriveHelper.php';
require_once __DIR__ . '/../helpers/PermisosHelper.php';

class ConfiguracionController {

    public function __construct() {
        PermisosHelper::verificarAcceso('configuracion');
    }

    public function index() {
        // Obtener datos de la empresa administradora del software
        $empresaSoftware = dbQueryOne("SELECT * FROM empresa_software ORDER BY id ASC LIMIT 1");
        if (!$empresaSoftware) {
            // Valores por defecto si la tabla está vacía
            $empresaSoftware = [
                'id' => 1,
                'razon_social' => 'Empresa Principal',
                'ruc' => '',
                'representante' => '',
                'contacto_administrativo' => '',
                'telefono' => '',
                'email' => '',
                'direccion' => '',
                'drive_folder_id' => '16Gne89yf9ckJFPJ0tABD31q2kZPlxHFr',
                'drive_email' => 'devops.maxisoftnet@gmail.com',
                'drive_vinculado' => 1
            ];
        }

        $pageTitle = 'Configuración del Sistema';
        $currentPage = 'configuracion';

        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/configuracion/panel.php';
        include __DIR__ . '/../views/layout/footer.php';
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/configuracion');
            exit;
        }

        $razon_social = trim($_POST['razon_social'] ?? '');
        $ruc          = trim($_POST['ruc'] ?? '');
        $representante = trim($_POST['representante'] ?? '');
        $contacto_admin = trim($_POST['contacto_administrativo'] ?? '');
        $telefono     = trim($_POST['telefono'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $direccion    = trim($_POST['direccion'] ?? '');
        $drive_input  = trim($_POST['drive_folder_id'] ?? '');
        $drive_email  = trim($_POST['drive_email'] ?? '');

        $drive_folder_id = GoogleDriveHelper::extractFolderId($drive_input);

        dbExecute("
            INSERT INTO empresa_software (
                id, razon_social, ruc, representante, contacto_administrativo,
                telefono, email, direccion, drive_folder_id, drive_email, drive_vinculado
            ) VALUES (
                1, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, 1
            ) ON DUPLICATE KEY UPDATE
                razon_social = VALUES(razon_social),
                ruc = VALUES(ruc),
                representante = VALUES(representante),
                contacto_administrativo = VALUES(contacto_administrativo),
                telefono = VALUES(telefono),
                email = VALUES(email),
                direccion = VALUES(direccion),
                drive_folder_id = VALUES(drive_folder_id),
                drive_email = VALUES(drive_email),
                drive_vinculado = 1
        ", [
            $razon_social, $ruc, $representante, $contacto_admin,
            $telefono, $email, $direccion, $drive_folder_id, $drive_email
        ]);

        // Sincronizar parámetros globales en configuracion
        dbExecute("INSERT INTO configuracion (clave, valor) VALUES ('software_empresa_id', '1') ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
        dbExecute("INSERT INTO configuracion (clave, valor) VALUES ('drive_root_folder_id', ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)", [$drive_folder_id]);
        dbExecute("INSERT INTO configuracion (clave, valor) VALUES ('drive_folder_id', ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)", [$drive_folder_id]);
        dbExecute("INSERT INTO configuracion (clave, valor) VALUES ('drive_email', ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)", [$drive_email]);
        dbExecute("INSERT INTO configuracion (clave, valor) VALUES ('drive_vinculado', '1') ON DUPLICATE KEY UPDATE valor = VALUES(valor)");

        // Si fue mediante modal iframe
        if (!empty($_GET['modal_iframe'])) {
            header('Location: ' . BASE_URL . '/configuracion?modal_iframe=1&success=1');
            exit;
        }

        header('Location: ' . BASE_URL . '/configuracion?success=1');
        exit;
    }
}
