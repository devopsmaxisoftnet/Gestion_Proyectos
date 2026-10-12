<?php
require_once __DIR__ . '/../config/database.php';

$profesionales = [
    [
        'nombre' => 'Ing. Carlos Mendoza Benavides',
        'tipo_profesional' => 'Ingeniero Civil',
        'especialidad' => 'Ingeniería Residente de Obra',
        'telefono' => '987 654 321',
        'email' => 'carlos.mendoza@constructora.pe',
        'empresa_id' => 3
    ],
    [
        'nombre' => 'Arq. Mariana Flores Alarcón',
        'tipo_profesional' => 'Arquitecto',
        'especialidad' => 'Edificaciones',
        'telefono' => '991 234 567',
        'email' => 'mflores@techbuild.pe',
        'empresa_id' => 6
    ],
    [
        'nombre' => 'Ing. Roberto Sánchez Paredes',
        'tipo_profesional' => 'Especialista en Suelos',
        'especialidad' => 'Especialista en Mecánica de Suelos, Geotecnia y Cimentaciones',
        'telefono' => '975 889 123',
        'email' => 'roberto.sanchez@gpc.pe',
        'empresa_id' => 5
    ],
    [
        'nombre' => 'Ing. Lucía Ramírez Valdivia',
        'tipo_profesional' => 'Especialista en Costos',
        'especialidad' => 'Especialista en Costos, Presupuestos y Valorizaciones',
        'telefono' => '944 321 987',
        'email' => 'lucia.ramirez@constructora.pe',
        'empresa_id' => 3
    ],
    [
        'nombre' => 'Ing. Jorge Quispe Huamán',
        'tipo_profesional' => 'Prevencionista',
        'especialidad' => 'Especialista en Seguridad, Salud en el Trabajo y Medio Ambiente (SSOMA)',
        'telefono' => '963 852 741',
        'email' => 'jorge.quispe@consorcio.pe',
        'empresa_id' => 7
    ],
    [
        'nombre' => 'Ing. Andrea Castillo Salazar',
        'tipo_profesional' => 'Ingeniero Civil',
        'especialidad' => 'Especialista en Estructuras y Obras de Concreto Armado',
        'telefono' => '955 412 369',
        'email' => 'acastillo@ingenieros.pe',
        'empresa_id' => 4
    ],
    [
        'nombre' => 'Ing. David Thorne Palacios',
        'tipo_profesional' => 'Ingeniero Sanitario',
        'especialidad' => 'Especialista en Redes de Agua Potable, Alcantarillado y Saneamiento',
        'telefono' => '981 147 258',
        'email' => 'david.thorne@ital.pe',
        'empresa_id' => 8
    ],
    [
        'nombre' => 'Ing. Sofía Morales Ruiz',
        'tipo_profesional' => 'Ingeniero Civil',
        'especialidad' => 'Coordinador y Modelador BIM / VDC',
        'telefono' => '992 369 852',
        'email' => 'sofia.morales@techbuild.pe',
        'empresa_id' => 6
    ],
    [
        'nombre' => 'Abg. Fernando Paz Soldán',
        'tipo_profesional' => 'Abogado',
        'especialidad' => 'Asesor Legal en Derecho Administrativo y Arbitrajes de Obra',
        'telefono' => '971 456 789',
        'email' => 'fpaz@consultores.pe',
        'empresa_id' => 5
    ]
];

foreach ($profesionales as $p) {
    // Buscar id de especialidad
    $espRow = dbQueryOne("SELECT id FROM cat_especialidades WHERE nombre = ?", [$p['especialidad']]);
    $espId = $espRow ? $espRow['id'] : null;

    // Buscar id de tipo
    $tipoRow = dbQueryOne("SELECT id FROM cat_tipos_profesional WHERE nombre = ?", [$p['tipo_profesional']]);
    $tipoId = $tipoRow ? $tipoRow['id'] : null;

    dbExecute("
        INSERT INTO profesionales (nombre, tipo_profesional, tipo_profesional_id, especialidad, especialidad_id, telefono, email, empresa_id, activo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ", [
        $p['nombre'], $p['tipo_profesional'], $tipoId,
        $p['especialidad'], $espId, $p['telefono'], $p['email'], $p['empresa_id']
    ]);
}

echo "✓ Profesionales iniciales insertados con éxito!\n";
