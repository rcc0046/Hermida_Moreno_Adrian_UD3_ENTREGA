<?php
/**
 * =====================================================================
 *  inc/datos-C.php · Datos de la VARIANTE C · «El Laberinto de Espejos»
 * =====================================================================
 *  Fichero SOLO PHP (sin etiqueta de cierre). Los datos ya vienen
 *  escritos: el reto consiste en RECORRERLOS y DECIDIR con ellos.
 *
 *  Estructuras que contiene:
 *   - $party      matriz: array indexado de arrays asociativos (4 héroes)
 *   - $inventario array asociativo objeto => cantidad
 *   - $precios    array asociativo objeto => precio en monedas de oro
 *   - $salas      matriz: array indexado de salas (arrays asociativos)
 *   - $jefe       array asociativo
 *   - $mapa       matriz bidimensional de caracteres (filas × columnas)
 */

declare(strict_types=1);

$nombreMazmorra = 'El Laberinto de Espejos';
$oroInicial     = 2045.60;

// --- La party: el primer héroe es el de tu variante en la UD2 ----------
$party = [
    ['nombre' => 'Vex <Sin Nombre>',               'clase' => 'Pícara',
     'fuerza' => 11, 'destreza' => 18, 'inteligencia' => 12, 'constitucion' => 12,
     'xp' => 2100, 'vida' => 140],
    ['nombre' => 'Aldric <el Arcano>',             'clase' => 'Hechicero',
     'fuerza' => 8,  'destreza' => 11, 'inteligencia' => 19, 'constitucion' => 10,
     'xp' => 2900, 'vida' => 0],
    ['nombre' => 'Brunhilda <Martillo de Hierro>', 'clase' => 'Guerrera',
     'fuerza' => 17, 'destreza' => 9,  'inteligencia' => 6,  'constitucion' => 15,
     'xp' => 2450, 'vida' => 260],
    ['nombre' => 'Ilse <la Sombra>',               'clase' => 'Pícara',
     'fuerza' => 9,  'destreza' => 17, 'inteligencia' => 13, 'constitucion' => 11,
     'xp' => 1650, 'vida' => 70],
];

// --- Inventario compartido y precios del mercado ------------------------
$inventario = [
    'Ganzúa'         => 3,
    'Poción de vida' => 1,
    'Antorcha'       => 4,
    'Espejo de mano' => 2,
];

$precios = [
    'Ganzúa'          => 6.0,
    'Poción de vida'  => 25.0,
    'Antorcha'        => 1.5,
    'Espejo de mano'  => 9.0,
    'Llave de cristal' => 70.0,
    'Prisma arcano'   => 150.0,
];

// --- Salas de la mazmorra, en el orden en que se recorren ---------------
// tipo: 'vacía' | 'trampa' | 'combate' | 'tesoro' | 'descanso'
// Solo las salas de tesoro pueden traer la clave 'objeto'.
$salas = [
    ['nombre' => 'Galería de reflejos',      'tipo' => 'trampa',   'valor' => 80],
    ['nombre' => 'Sala de los dobles',       'tipo' => 'combate',  'valor' => 280],
    ['nombre' => 'Pasillo sin salida',       'tipo' => 'vacía',    'valor' => 0],
    ['nombre' => 'Vitrina rota',             'tipo' => 'tesoro',   'valor' => 130, 'objeto' => 'Prisma arcano'],
    ['nombre' => 'Fuente de plata',          'tipo' => 'descanso', 'valor' => 50],
    ['nombre' => 'Espejo devorador',         'tipo' => 'combate',  'valor' => 300],
    ['nombre' => 'Cámara del eco',           'tipo' => 'vacía',    'valor' => 0],
    ['nombre' => 'Cofre de cristal',         'tipo' => 'tesoro',   'valor' => 95,  'objeto' => 'Llave de cristal'],
];

// --- El jefe final --------------------------------------------------------
$jefe = [
    'nombre'     => 'Reina de los Espejos',
    'vida'       => 650,
    'ataque'     => 90,
    'recompensa' => 450,
    'llave'      => 'Llave de cristal', // objeto necesario para entrar
];

// --- Mapa de la mazmorra (matriz de caracteres) ---------------------------
$mapa = [
    ['#', '#', '#', '#', '#', '#', '#', '#', '#', '#'],
    ['#', 'E', 'S', '.', 'S', '#', 'S', '.', 'S', '#'],
    ['#', '#', '.', '#', '.', '~', '.', '#', '.', '#'],
    ['#', 'S', '.', '#', 'S', '~', 'S', '.', '.', '#'],
    ['#', '.', '#', '#', '#', '~', '#', '#', 'S', '#'],
    ['#', '.', '.', '.', '.', '.', '.', '.', 'J', '#'],
    ['#', '#', '#', '#', '#', '#', '#', '#', '#', '#'],
];
