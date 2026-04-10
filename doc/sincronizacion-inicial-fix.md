# Sincronización Inicial de Estado de Asientos

## Contexto del Proyecto

Sistema de venta de entradas de cine con:
- **Backend**: Laravel (API REST)
- **Frontend**: Nuxt/Vue
- **Gateway**: Node.js + Socket.io + Redis
- **Tiempo real**: Redis pub/sub para notificar selección de asientos

## Problema Identificado

**Estado inicial desincronizado** en `frontend/pages/butaques.vue`

### Flujo actual problemático:

1. Usuario entra en `/butaques?sessio=X`
2. Se carga `seients` desde API REST (`/sesiones/{sessioId}/asientos`)
3. La API retorna asientos con campos básicos: `id`, `fila`, `numero`, `categoria`, `color`, `reservat`
4. **PERO** el campo `seleccionat_per_altre` depende exclusivamente de eventos Socket.io
5. Si el usuario se conecta tarde, no recibe eventos de asientos que ya están reservados por otros

### Código problemático (butaques.vue):

```javascript
// Líneas 28-32
const { data: seients, ... } = await useFetch(sessioId ? `/sesiones/${sessioId}/asientos` : null, {
  baseURL,
  immediate: !!sessioId
})

// El campo 'seleccionat_per_altre' NO viene del API, se añade via socket:
// Líneas 37-48
function onSeientSeleccionat(data) {
  // ...
  patchSeient(data.seient_id, { seleccionat_per_altre: true })
}
```

### Resultado:
- Usuario ve asiento como "libre" cuando otro usuario ya lo tiene reservado
- Dos usuarios pueden intentar reservar el mismo asiento simultáneamente
- El primero en hacer clic gagne, el segundo recibe error 409 después de esperar

## Solución Necesaria

La API debe retornar los asientos **con el estado de reservas temporales activas incluidas**.

### 1. Modificar el Controlador/Endpoint de Asientos (Laravel)

Buscar el endpoint que sirve los asientos de una sesión:

```php
// En el controller que devuelve los asientos (probablemente SessioController o similar)
public function getAsientosPorSessio(int $sessioId)
{
    $asientos = Seient::where('sala_id', $sessio->sala_id)->get();
    
    // Añadir estado de reservas temporales activas
    $reservasActivas = ReservaTemporal::where('sessio_id', $sessioId)
        ->where('expires_at', '>', now())
        ->get()
        ->keyBy('seient_id');
    
    $asientos = $asientos->map(function ($seient) use ($reservasActivas, $usuariId) {
        $reserva = $reservasActivas->get($seient->id);
        
        $seient->reservat = $seient->estaReservat(); // Ya existente
        
        // NUEVO: Campo para el frontend
        $seient->seleccionat_per_altre = false;
        if ($reserva && $reserva->usuari_id != $usuariId) {
            $seient->seleccionat_per_altre = true;
        }
        
        // Mi propia reserva (para mantener la selección en mi UI)
        $seient->la_meva_reserva = false;
        if ($reserva && $reserva->usuari_id == $usuariId) {
            $seient->la_meva_reserva = true;
        }
        
        return $seient;
    });
    
    return response()->json($asientos);
}
```

### 2. Ajustar el Frontend (butaques.vue)

El frontend ya tiene la lógica para procesar `seleccionat_per_altre`, solo necesita que el campo venga en la respuesta inicial:

```javascript
// Ahora los asientos ya vendrán con:
// - seleccionat_per_altre: true/false (reservado por otro)
// - la_meva_reserva: true/false (mi propia reserva activa)

// El código actual ya lo procesa correctamente:
// Línea 225-228 en el template
:class="{
  selected: selectedSeients.some(s => s.id === seient.id),
  reservat: seient.reservat,
  'other-selected': seient.seleccionat_per_altre
}"
```

### 3. (Opcional) Optimización con Redis

Para no hacer consulta SQL en cada request, caché en Redis el estado de reservas por sesión:

```php
// En el método getAsientosPorSessio:

$cacheKey = "sessio:{$sessioId}:reservas";
$reservasCache = Redis::get($cacheKey);

if ($reservasCache) {
    $reservasActivas = collect(json_decode($reservasCache));
} else {
    $reservasActivas = ReservaTemporal::where('sessio_id', $sessioId)
        ->where('expires_at', '>', now())
        ->get()
        ->keyBy('seient_id');
    
    Redis::setex($cacheKey, 30, $reservasActivas->toJson()); // Cache 30s
}

// Invalidar cache cuando cambia una reserva:
// En el método reservarTemporal() después de crear/reservar:
Redis::del("sessio:{$sessioId}:reservas");
```

## Archivos a Modificar

1. **`backend-laravel/app/Http/Controllers/...`** - Controller que devuelve los asientos de una sesión (buscar endpoint `/sesiones/{id}/asientos`)
2. **`backend-laravel/routes/api.php`** - Si hay que añadir/modificar endpoint

## Verificación Post-Fix

1. Abrir dos navegadores
2. Usuario A selecciona asiento (se marca en naranja en pantalla de B instantáneamente)
3. Recargar página de B → el asiento debe aparecer como seleccionado por otro desde el inicio
4. El evento Socket.io sigue funcionando para actualizaciones en tiempo real

## Notas Adicionales

- Esta solución complementa (no sustituye) el fix de race condition
- El tiempo de caché de Redis (30s) debe ser menor que el tiempo de reserva (10 min)
- El campo `la_meva_reserva` es útil para restaurar la selección del usuario si recarga la página