# Forja de Héroes II · Kit de partida (UD3)

Continuación del reto de la UD2. Este kit contiene **solo la base del proyecto**: el entorno es el mismo Docker LAMP de la práctica inicial (`docker-lamp`). No hace falta tocar `docker-compose.yml`.

## Contenido

```
forja-heroes-ii/
├── src/
│   ├── inc/reglas.php      ← constantes comunes (solo cambias VARIANTE)
│   ├── inc/datos-A.php     ← datos de la variante A (ya escritos)
│   ├── inc/datos-B.php     ← datos de la variante B (ya escritos)
│   ├── inc/datos-C.php     ← datos de la variante C (ya escritos)
│   ├── expedicion.php      ← R1-R8 · AQUÍ trabajas: cálculos + página
│   └── css/estilo.css      ← ya hecho
├── capturas/               ← tus capturas de pantalla
└── MEMORIA.md              ← R9 · memoria
```

Los ficheros de datos **no se modifican** (salvo en los experimentos de la memoria, y después se dejan como estaban).

## Integrarlo en tu Docker LAMP

1. Copia la carpeta `forja-heroes-ii` dentro de `docker-lamp/www/`, al lado de `forja-heroes` (la de la UD2).
2. Arranca el LAMP desde `docker-lamp`: `docker compose up -d`.
3. Abre `http://localhost/forja-heroes-ii/src/expedicion.php`.

Las directivas de tu `forja.ini` de la UD2 siguen activas (están montadas en `conf.d`), así que `display_errors` y `error_reporting = E_ALL` te mostrarán los avisos. **No los ignores**: un `Warning` es un error de tu código.

## Si Docker te da problemas

Desde la carpeta `forja-heroes-ii`:

```bash
php -d display_errors=1 -d error_reporting=-1 -S localhost:8080 -t src
```

y abre `http://localhost:8080/expedicion.php`.

## Entrega

Un zip `apellido1_apellido2_nombre_UD3.zip` con **solo** la carpeta `forja-heroes-ii` (incluidas `capturas/` y `MEMORIA.md`).
