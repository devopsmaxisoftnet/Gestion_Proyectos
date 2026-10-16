<?php
/**
 * Plantilla de credenciales de Base de Datos para Producción / Entorno Local.
 * Copie este archivo como 'config/db_credentials.php' y configure sus credenciales reales.
 * NUNCA agregue 'config/db_credentials.php' al repositorio Git (debe estar en .gitignore).
 */

// Si desea sobreescribir las credenciales por defecto:
define('DB_HOST_CUSTOM', 'localhost');
define('DB_USER_CUSTOM', 'mnxsrokm_user_GestComercial');
define('DB_PASS_CUSTOM', 'CAMBIE_ESTA_CONTRASENA_EN_CPANEL_Y_PONGA_AQUI_LA_NUEVA');
define('DB_NAME_CUSTOM', 'mnxsrokm_comercial');
define('BASE_URL_CUSTOM', ''); // En producción generalmente es vacío '' o el subdirectorio correspondiente
