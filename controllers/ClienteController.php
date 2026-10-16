<?php
require_once __DIR__ . '/../models/ClienteModel.php';
require_once __DIR__ . '/../helpers/PermisosHelper.php';

class ClienteController {
    private $model;
    
    public function __construct() {
        PermisosHelper::verificarAcceso('clientes');
        $this->model = new ClienteModel();
    }
    
    public function index() {
        $filtros = [
            'busqueda' => $_GET['busqueda'] ?? '',
            'tipo' => $_GET['tipo'] ?? '',
        ];
        
        $data = [
            'clientes' => $this->model->getAll($filtros),
            'filtros' => $filtros,
        ];
        
        $pageTitle = 'Clientes';
        $currentPage = 'clientes';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/clientes/lista_clientes.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->create($_POST);
            header('Location: ' . BASE_URL . '/clientes');
            exit;
        }
        
        $pageTitle = 'Nuevo cliente';
        $currentPage = 'clientes';
        $data = [
            'cat_departamentos' => dbQuery("SELECT * FROM cat_departamentos ORDER BY nombre"),
            'cat_provincias' => [],
            'cat_distritos' => [],
            'cat_cargos_cliente' => dbQuery("SELECT * FROM cat_cargos_cliente ORDER BY nombre")
        ];
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/clientes/crear_cliente.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function editar($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->update($id, $_POST);
            header('Location: ' . BASE_URL . '/clientes');
            exit;
        }
        
        $cliente = $this->model->getById($id);
        
        $provs = [];
        if (!empty($cliente['departamento_id'])) {
            $provs = dbQuery("SELECT * FROM cat_provincias WHERE departamento_id = ? ORDER BY nombre", [$cliente['departamento_id']]);
        }
        $dists = [];
        if (!empty($cliente['provincia_id'])) {
            $dists = dbQuery("SELECT * FROM cat_distritos WHERE provincia_id = ? ORDER BY nombre", [$cliente['provincia_id']]);
        }

        $data = [
            'cliente' => $cliente,
            'cat_departamentos' => dbQuery("SELECT * FROM cat_departamentos ORDER BY nombre"),
            'cat_provincias' => $provs,
            'cat_distritos' => $dists,
            'cat_cargos_cliente' => dbQuery("SELECT * FROM cat_cargos_cliente ORDER BY nombre")
        ];
        
        $pageTitle = 'Editar cliente';
        $currentPage = 'clientes';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/clientes/crear_cliente.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function eliminar($id) {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $userRol = $_SESSION['user']['rol'] ?? 'usuario';
        if (!in_array($userRol, ['superadmin', 'admin'])) {
            $_SESSION['flash_error'] = 'Acceso denegado: Se requieren permisos de Administrador.';
            header('Location: ' . BASE_URL . '/clientes');
            exit;
        }

        require_once __DIR__ . '/../helpers/CsrfHelper.php';
        if (!CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
            header('Location: ' . BASE_URL . '/clientes');
            exit;
        }

        $this->model->delete($id);
        header('Location: ' . BASE_URL . '/clientes');
        exit;
    }
    
    public function addCatalogItem() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tipo = $_POST['tipo'] ?? '';
            $nombre = $_POST['nombre'] ?? '';
            $rel_id = $_POST['rel_id'] ?? '';
            $table = '';
            
            if ($tipo === 'departamento') $table = 'cat_departamentos';
            elseif ($tipo === 'provincia') $table = 'cat_provincias';
            elseif ($tipo === 'distrito') $table = 'cat_distritos';
            elseif ($tipo === 'cargo_cliente') $table = 'cat_cargos_cliente';
            
            if ($table && $nombre) {
                try {
                    $id = uniqid(); // Use uniqid for text IDs if needed, but wait! We used VARCHAR. uniqid gives 13 chars.
                    // Wait, our ubigeo seeder made them VARCHAR(2), (4), (6). 
                    // To generate a short ID, let's just use substr of md5, or max(id) + 1.
                    // Instead of full logic, let's just do an INSERT without ID if it has auto-increment?
                    // Wait, our seeder DID NOT use auto-increment for ubigeo!
                    // Let's modify the tables to just auto-increment if we can, or just generate a random short ID.
                    $short_id = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
                    
                    if ($tipo === 'provincia') {
                        dbExecute("INSERT INTO $table (id, nombre, departamento_id) VALUES (?, ?, ?)", [$short_id, $nombre, $rel_id]);
                    } elseif ($tipo === 'distrito') {
                        dbExecute("INSERT INTO $table (id, nombre, provincia_id) VALUES (?, ?, ?)", [$short_id, $nombre, $rel_id]);
                    } elseif ($tipo === 'departamento') {
                        dbExecute("INSERT INTO $table (id, nombre) VALUES (?, ?)", [$short_id, $nombre]);
                    } else {
                        // cat_cargos_cliente might be auto_increment because we created it differently in db_seed_clientes.php
                        dbExecute("INSERT INTO $table (nombre) VALUES (?)", [$nombre]);
                        $short_id = dbLastId();
                    }
                    
                    echo json_encode(['success' => true, 'id' => $short_id, 'nombre' => $nombre]);
                } catch(Exception $e) {
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
            }
        }
        exit;
    }
    
    public function getProvincias() {
        $dep_id = $_GET['departamento_id'] ?? '';
        $provs = [];
        if ($dep_id) {
            $provs = dbQuery("SELECT id, nombre FROM cat_provincias WHERE departamento_id = ? ORDER BY nombre", [$dep_id]);
        }
        echo json_encode($provs);
        exit;
    }
    
    public function getDistritos() {
        $prov_id = $_GET['provincia_id'] ?? '';
        $dists = [];
        if ($prov_id) {
            $dists = dbQuery("SELECT id, nombre FROM cat_distritos WHERE provincia_id = ? ORDER BY nombre", [$prov_id]);
        }
        echo json_encode($dists);
        exit;
    }
}





