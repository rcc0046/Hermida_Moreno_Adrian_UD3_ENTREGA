<?php
/**
 * =====================================================================
 *  expedicion.php · Forja de Héroes II · La party y la mazmorra
 * =====================================================================
 *  0613 DWES · UD3 · Tarea de refuerzo
 *
 *  @author   Adrián Hermida Moreno   (R4 · expedición e inventario)
 *  @author   Alejandro Segundo Ganoza (R2/R3 · party · R5 · jefe)
 *  @author   Raúl Crespo             (R6 · mapa · R7 · presentación)
 *  @variante B · «Las Minas de Hierro Negro»
 *  @version  UD3
 *
 *  Estructura (como en el paso 09 del caso guiado):
 *   1) strict_types y carga de inc/reglas.php y del fichero de datos
 *   2) CÁLCULOS: todo arriba, sin nada de HTML
 *   3) PRESENTACIÓN: HTML con sintaxis alternativa
 *      (foreach (...): ... endforeach;   if (...): ... else: ... endif;)
 *
 *  Todos los resultados salen de los datos: si se cambia algo en
 *  inc/datos-B.php y se recarga, el informe se recalcula entero.
 */

declare(strict_types=1);

// Primero las reglas (constantes) y luego los datos de la variante,
// porque el nombre del fichero de datos depende de la constante VARIANTE.
require_once __DIR__ . '/inc/reglas.php';
require_once __DIR__ . '/inc/datos-' . VARIANTE . '.php';
// =====================================================================
// R2 + R3 · LA PARTY
// =====================================================================
/*
 * Recorremos la party con clave ($i) para poder guardar los datos
 * calculados DENTRO de la propia matriz ($party[$i][...]). Si solo
 * usáramos $heroe, estaríamos modificando una copia y se perdería.
 */
foreach ($party as $i => $heroe) {
    $nivel   = intdiv($heroe['xp'], XP_POR_NIVEL) + 1;
    $vidaMax = VIDA_BASE + $heroe['constitucion'] * $nivel * MULT_VIDA[$heroe['clase']];
    $pctVida = $heroe['vida'] / $vidaMax * 100;

    // R3 · Daño con match SIN default: si llega una clase que no está
    // contemplada, PHP lanza UnhandledMatchError y nos enteramos del fallo
    // en vez de calcular un daño inventado.
    $danio = match ($heroe['clase']) {
        'Guerrera'  => $heroe['fuerza'] ** 2 / 10 + $nivel * 2,
        'Hechicero' => $heroe['inteligencia'] * 3 + $nivel ** 2 / 4,
        'Pícara'    => $heroe['destreza'] * 2 + $nivel ** 1.5,
        'Clérigo'   => ($heroe['inteligencia'] + $heroe['constitucion']) * 1.5 + $nivel,
    };

    // R3 · Estado con if/elseif/else. El orden importa: primero el caso
    // más grave (K.O.), porque una vez que una condición se cumple ya no
    // se miran las siguientes.
    if ($heroe['vida'] <= 0) {
        $estado = 'K.O.';
    } elseif ($pctVida < 35) {
        $estado = 'En peligro';
    } elseif ($pctVida < 70) {
        $estado = 'Herido';
    } else {
        $estado = 'En pie';
    }

    # R3 · Posición con switch: Guerrera y Clérigo comparten resultado,
    # así que se agrupan los dos case sin break entre ellos.
    switch ($heroe['clase']) {
        case 'Guerrera':
        case 'Clérigo':
            $posicion = 'Primera línea';
            break;
        case 'Pícara':
            $posicion = 'Flanco';
            break;
        case 'Hechicero':
            $posicion = 'Retaguardia';
            break;
        default:
            $posicion = 'Reserva';
    }

    // Barra de vida con for (sin str_repeat): en cada vuelta se añade un
    // bloque lleno o vacío según si todavía no hemos llegado a los llenos.
    $bloquesLlenos = (int) round($pctVida / 100 * BLOQUES_BARRA);
    $barra = '';
    for ($b = 0; $b < BLOQUES_BARRA; $b++) {
        $barra .= ($b < $bloquesLlenos) ? '█' : '░';
    }

    // Guardamos todo en la matriz para usarlo después en totales y en el HTML.
    $party[$i]['nivel']    = $nivel;
    $party[$i]['vidaMax']  = $vidaMax;
    $party[$i]['pctVida']  = $pctVida;
    $party[$i]['danio']    = $danio;
    $party[$i]['poder']    = (int) round($danio * $nivel);
    $party[$i]['estado']   = $estado;
    $party[$i]['posicion'] = $posicion;
    $party[$i]['barra']    = $barra;
}

// Totales de la party: array_column saca una "columna" de la matriz y
// array_sum la suma, así no hace falta otro bucle para cada total.
$vidaParty    = array_sum(array_column($party, 'vida'));
$vidaMaxParty = array_sum(array_column($party, 'vidaMax'));
$poderParty   = array_sum(array_column($party, 'poder'));
$nivelMedio   = array_sum(array_column($party, 'nivel')) / count($party);
$vidaEntrada  = $vidaParty; // la guardamos para el informe (vida al entrar)

// Segundo recorrido: solo cuentan los héroes que siguen en pie. Con
// continue saltamos a los K.O. y pasamos directamente al siguiente.
$danioParty     = 0;
$heroesActivos  = 0;
$lider          = null; // todavía no sabemos quién es: empieza vacío
foreach ($party as $heroe) {
    if ($heroe['vida'] <= 0) {
        continue;
    }
    $danioParty += $heroe['danio'];
    $heroesActivos++;
    if ($lider === null || $heroe['poder'] > $lider['poder']) {
        $lider = $heroe;
    }
}

// in_array con true (comparación estricta) para que no haya conversiones
// raras de tipos. Estas dos variables deciden trampas y descansos.
$clases     = array_column($party, 'clase');
$hayPicara  = in_array('Pícara', $clases, true);
$hayClerigo = in_array('Clérigo', $clases, true);

// =====================================================================
// R4 · LA EXPEDICIÓN (sala a sala)
// =====================================================================
// Valores de partida. PHP no recuerda nada entre peticiones, así que en
// cada recarga la expedición vuelve a empezar desde la primera sala.
$oro             = $oroInicial;
$xpGanada        = 0;
$diario          = [];
$salasExploradas = 0;

foreach ($salas as $n => $sala) {
    // Contamos la sala al entrar: así las salas vacías también cuentan
    // aunque luego salten directamente a la siguiente con continue 2.
    $salasExploradas++;
    $prefijo = 'Sala ' . ($n + 1) . ' · ' . $sala['nombre'] . ': ';

    switch ($sala['tipo']) {
        case 'vacía':
            $diario[] = $prefijo . 'vacía, la party sigue avanzando.';
            /* Dentro de un switch, "continue" a secas actúa como "break"
               (solo sale del switch). Con "continue 2" saltamos al
               foreach, que es el bucle que realmente queremos avanzar. */
            continue 2;

        case 'trampa':
            if ($hayPicara) {
                $diario[] = $prefijo . 'trampa desactivada por la Pícara.';
            } else {
                $vidaParty -= $sala['valor'];
                $diario[] = $prefijo . '¡trampa! −' . $sala['valor'] . ' de vida.';
            }
            break;

        case 'combate':
            $perdida   = intdiv($sala['valor'], 4);
            $vidaParty -= $perdida;
            $xpGanada  += $sala['valor'];
            $diario[] = $prefijo . 'combate, −' . $perdida . ' de vida y +' . $sala['valor'] . ' XP.';
            break;

        case 'tesoro':
            $oro += $sala['valor'];
            $linea = $prefijo . '+' . $sala['valor'] . ' mo';
            // Solo algunas salas de tesoro traen objeto: lo comprobamos con
            // isset para no provocar un Warning de clave inexistente.
            if (isset($sala['objeto'])) {
                $objeto = $sala['objeto'];
                $inventario[$objeto] = ($inventario[$objeto] ?? 0) + 1;
                $linea .= ' y «' . $objeto . '»';
            }
            $diario[] = $linea . '.';
            break;

        case 'descanso':
            $cura = $hayClerigo ? $sala['valor'] * 2 : $sala['valor'];
            // min() evita que la party se cure por encima de su vida máxima.
            $vidaParty = min($vidaParty + $cura, $vidaMaxParty);
            $diario[] = $prefijo . 'descanso, +' . $cura . ' de vida'
                . ($hayClerigo ? ' (bendición del Clérigo).' : '.');
            break;
    }

    // Después de cada sala comprobamos si la party ha caído. Si es así,
    // break corta el foreach y el resto de salas quedan sin explorar.
    if ($vidaParty <= 0) {
        $vidaParty = 0;
        $diario[] = 'La party cae en la sala ' . ($n + 1) . '. Fin de la expedición.';
        break;
    }
}

$vidaTrasSalas   = $vidaParty;
$salasPendientes = count($salas) - $salasExploradas;

// =====================================================================
// R5 · EL JEFE
// =====================================================================
// El golpe de la party es su daño total redondeado a entero, y la llave
// se busca como CLAVE del inventario (objeto => cantidad).
$tieneLlave = array_key_exists($jefe['llave'], $inventario);
$golpe      = (int) round($danioParty);
$vidaJefe   = $jefe['vida'];
$rondas     = [];
$ronda      = 0;

if ($vidaParty <= 0) {
    $resultado = 'Derrota';
    $motivo    = 'la party no llegó a la guarida del jefe.';
} elseif (!$tieneLlave) {
    $resultado = 'Puerta sellada';
    $motivo    = 'sin «' . $jefe['llave'] . '» no se puede entrar.';
} else {
    /* Combate por rondas con while porque no sabemos cuántas vueltas va a
       dar: depende de la vida de los dos. MAX_RONDAS es el tope para que
       el bucle nunca sea infinito (por ejemplo, si el golpe fuera 0). */
    while ($vidaJefe > 0 && $vidaParty > 0 && $ronda < MAX_RONDAS) {
        $ronda++;
        $vidaJefe = max(0, $vidaJefe - $golpe);
        // Si el jefe ha caído en esta ronda ya no contraataca.
        if ($vidaJefe > 0) {
            $vidaParty = max(0, $vidaParty - $jefe['ataque']);
        }
        $rondas[] = ['ronda' => $ronda, 'vidaJefe' => $vidaJefe, 'vidaParty' => $vidaParty];
    }

    # match(true): se queda con el primer brazo cuya condición sea cierta.
    $resultado = match (true) {
        $vidaJefe <= 0  => 'Victoria',
        $vidaParty <= 0 => 'Derrota',
        default         => 'Retirada',
    };

    $motivo = match ($resultado) {
        'Victoria' => 'en la ronda ' . $ronda . ', +' . $jefe['recompensa'] . ' mo de recompensa.',
        'Derrota'  => 'la party cae en la ronda ' . $ronda . '.',
        'Retirada' => 'tras ' . $ronda . ' rondas el jefe sigue en pie con ' . $vidaJefe . ' de vida.',
    };

    if ($resultado === 'Victoria') {
        $oro += $jefe['recompensa'];
    }
}

// =====================================================================
// R4 (cont.) · INVENTARIO FINAL
// =====================================================================
// Se calcula al final para que incluya los objetos encontrados en las salas.
$valorInventario = 0.0;
foreach ($inventario as $objeto => $cantidad) {
    $valorInventario += $cantidad * $precios[$objeto];
}

$totalObjetos = array_sum($inventario);

// arsort ordena de mayor a menor CONSERVANDO las claves (sort las perdería
// y nos quedaríamos sin saber qué objeto es cada cantidad). Lo hacemos
// sobre una copia para no tocar el inventario original.
$inventarioOrdenado = $inventario;
arsort($inventarioOrdenado);
$objetoMasAbundante = array_key_first($inventarioOrdenado);

// =====================================================================
// R6 · EL MAPA
// =====================================================================
// Dos for anidados: el de fuera recorre filas y el de dentro las columnas
// de cada fila. El ?? 0 evita el Warning la primera vez que sale un carácter.
$conteoCeldas = [];
for ($f = 0; $f < count($mapa); $f++) {
    for ($c = 0; $c < count($mapa[$f]); $c++) {
        $celda = $mapa[$f][$c];
        $conteoCeldas[$celda] = ($conteoCeldas[$celda] ?? 0) + 1;
    }
}

// El mapa es coherente si tiene tantas 'S' como salas hay en los datos.
$salasEnMapa   = $conteoCeldas['S'] ?? 0;
$mapaCoherente = ($salasEnMapa === count($salas));

// Barra de vida final de la party (después de las salas y del jefe).
$pctVidaParty   = $vidaParty / $vidaMaxParty * 100;
$bloquesParty   = (int) round($pctVidaParty / 100 * BLOQUES_BARRA);
$barraParty     = '';
for ($b = 0; $b < BLOQUES_BARRA; $b++) {
    $barraParty .= ($b < $bloquesParty) ? '█' : '░';
}

// =====================================================================
// A partir de aquí solo se PINTA: nada de cálculos, solo recorrer y mostrar.
// =====================================================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Expedición · <?= htmlspecialchars($nombreMazmorra) ?> · Forja de Héroes II</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<main>
    <span class="insignia">VARIANTE <?= VARIANTE ?></span>
    <h1><?= htmlspecialchars($nombreMazmorra) ?></h1>
    <p class="subtitulo">Informe de expedición · líder: <?= $lider !== null ? htmlspecialchars($lider['nombre']) : 'ninguno (todos K.O.)' ?></p>

    <h2>La party</h2>
    <table>
        <tr><th>Héroe</th><th>Clase</th><th>Nivel</th><th>Vida</th><th>Daño</th><th>Poder</th><th>Posición</th><th>Estado</th></tr>
        <?php foreach ($party as $heroe): ?>
        <tr><td><?= htmlspecialchars($heroe['nombre']) ?></td><td><?= $heroe['clase'] ?></td><td><?= $heroe['nivel'] ?></td>
            <td><span class="barra mini"><?= $heroe['barra'] ?></span> <?= $heroe['vida'] ?>/<?= $heroe['vidaMax'] ?></td>
            <td><?= number_format($heroe['danio'], 1, ',', '.') ?></td><td><?= $heroe['poder'] ?></td><td><?= $heroe['posicion'] ?></td>
            <?php if ($heroe['estado'] === 'K.O.' || $heroe['estado'] === 'En peligro'): ?>
            <td class="peligro"><?= $heroe['estado'] ?></td>
            <?php else: ?>
            <td class="vida"><?= $heroe['estado'] ?></td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="rejilla">
        <div class="stat"><div class="etq">Poder total</div><div class="val"><?= $poderParty ?></div></div>
        <div class="stat"><div class="etq">Nivel medio</div><div class="val"><?= number_format($nivelMedio, 1, ',', '.') ?></div></div>
        <div class="stat"><div class="etq">Daño por ronda</div><div class="val"><?= number_format($danioParty, 1, ',', '.') ?></div></div>
        <div class="stat"><div class="etq">Héroes activos</div><div class="val"><?= $heroesActivos ?> / <?= count($party) ?></div></div>
    </div>

    <h2>Diario de la expedición</h2>
    <p>Vida de la party al entrar: <?= $vidaEntrada ?> · tras las salas: <?= $vidaTrasSalas ?> (de <?= $vidaMaxParty ?>)</p>
    <ol class="diario">
        <?php foreach ($diario as $linea): ?>
        <li><?= htmlspecialchars($linea) ?></li>
        <?php endforeach; ?>
    </ol>
    <p>Salas exploradas: <?= $salasExploradas ?> de <?= count($salas) ?>
        <?php if ($salasPendientes > 0): ?>
        <span class="peligro">(<?= $salasPendientes ?> sin explorar)</span>
        <?php endif; ?>
        · XP ganada: <?= $xpGanada ?></p>

    <h2>El jefe: <?= htmlspecialchars($jefe['nombre']) ?></h2>
    <p>Vida <?= $jefe['vida'] ?> · ataque <?= $jefe['ataque'] ?> · golpe de la party: <?= $golpe ?> por ronda</p>
    <?php if (count($rondas) > 0): ?>
    <table>
        <tr><th>Ronda</th><th>Vida del jefe</th><th>Vida de la party</th></tr>
        <?php foreach ($rondas as $fila): ?>
        <tr><td><?= $fila['ronda'] ?></td><td><?= $fila['vidaJefe'] ?></td><td><?= $fila['vidaParty'] ?></td></tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
    <?php if ($resultado === 'Victoria'): ?>
    <p class="resultado vida"><strong><?= $resultado ?></strong> · <?= htmlspecialchars($motivo) ?></p>
    <?php else: ?>
    <p class="resultado peligro"><strong><?= $resultado ?></strong> · <?= htmlspecialchars($motivo) ?></p>
    <?php endif; ?>
    <p class="barra vida">PARTY <?= $barraParty ?> <?= $vidaParty ?>/<?= $vidaMaxParty ?></p>

    <h2>Inventario</h2>
    <table>
        <tr><th>Objeto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr>
        <?php foreach ($inventarioOrdenado as $objeto => $cantidad): ?>
        <tr><td><?= htmlspecialchars($objeto) ?></td><td><?= $cantidad ?></td>
            <td><?= number_format($precios[$objeto], 2, ',', '.') ?> mo</td>
            <td><?= number_format($cantidad * $precios[$objeto], 2, ',', '.') ?> mo</td></tr>
        <?php endforeach; ?>
    </table>
    <div class="rejilla">
        <div class="stat"><div class="etq">Objetos</div><div class="val"><?= $totalObjetos ?></div></div>
        <div class="stat"><div class="etq">Valor del inventario</div><div class="val"><?= number_format($valorInventario, 2, ',', '.') ?> mo</div></div>
        <div class="stat"><div class="etq">Oro final</div><div class="val"><?= number_format($oro, 2, ',', '.') ?> mo</div></div>
        <div class="stat"><div class="etq">Más abundante</div><div class="val peq"><?= htmlspecialchars((string) $objetoMasAbundante) ?></div></div>
    </div>

    <h2>Mapa de la mazmorra</h2>
    <table class="mapa">
        <?php foreach ($mapa as $fila): ?>
        <tr>
            <?php foreach ($fila as $celda): ?>
            <td class="<?= LEYENDA_MAPA[$celda][0] ?? 'desconocida' ?>"><?= htmlspecialchars($celda) ?></td>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
    </table>
    <p>
        <?php foreach ($conteoCeldas as $caracter => $veces): ?>
        <code><?= htmlspecialchars((string) $caracter) ?></code> <?= LEYENDA_MAPA[$caracter][1] ?? 'Desconocida' ?>: <?= $veces ?> ·
        <?php endforeach; ?>
    </p>
    <?php if ($mapaCoherente): ?>
    <p class="vida">El mapa es coherente: tiene <?= $salasEnMapa ?> salas, las mismas que la expedición.</p>
    <?php else: ?>
    <p class="peligro">¡Ojo! El mapa tiene <?= $salasEnMapa ?> salas pero la expedición tiene <?= count($salas) ?>.</p>
    <?php endif; ?>

    <footer>Informe generado el <?= date('d/m/Y H:i:s') ?> con PHP <?= PHP_VERSION ?> · cada recarga lo recalcula todo desde cero.</footer>
</main>
</body>
</html>
