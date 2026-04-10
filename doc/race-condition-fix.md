# Fix Race Condition en Reserva de Asientos

## Contexto del Proyecto

Sistema de venta de entradas de cine con:
- **Backend**: Laravel (API REST)
- **Frontend**: Nuxt/Vue
- **Gateway**: Node.js + Socket.io + Redis
- **Tiempo real**: Redis pub/sub para notificar selección de asientos

## Problema Identificado

**Race Condition crítico** en `backend-laravel/app/Http/Controllers/CompraController.php:101-117`

### Código problemático actual:

```php
// Líneas 103-117
$existent = \App\Models\ReservaTemporal::where('sessio_id', $sessioId)
    ->where('seient_id', $seientId)
    ->where('expires_at', '>', now())
    ->where('usuari_id', '!=', $usuari->id)
    ->exists();

if ($existent) {
    return response()->json(['error' => 'Seient ja reservat per un altre usuari'], 409);
}

// Crear o renovar reserva
\App\Models\ReservaTemporal::updateOrCreate(
    ['sessio_id' => $sessioId, 'seient_id' => $seientId, 'usuari_id' => $usuari->id],
    ['expires_at' => now()->addMinutes(10)]
);
```

### Por qué falla:

1. **No es atómico**: Entre el `exists()` y el `updateOrCreate()` otro usuario puede reservar el mismo asiento
2. **Dos usuarios pueden pasar el check simultáneamente** y ambos crean la reserva
3. **Resultado**: Dos usuarios creen tener el mismo asiento reservado

## Solución Necesaria

Implementar **locking atómico** usando Redis con patrón de "lock distribuido".

### Estrategia:

1. **Obtener lock** antes de verificar disponibilidad
2. **Verificar y crear** la reserva atómicamente dentro del lock
3. **Liberar lock** al terminar

### Implementación sugerida:

```php
use Illuminate\Support\Facades\Redis;

// En el método reservarTemporal(), envolver la lógica con Redis lock:

$lockKey = "lock:reserva:{$sessioId}:{$seientId}";
$lock = Redis::setnx($lockKey, $usuari->id);

// TTL de 10 segundos para evitar deadlocks
Redis::expire($lockKey, 10);

if (!$lock) {
    return response()->json(['error' => 'Seient sent procesat per altre usuari'], 409);
}

try {
    // Verificar y crear reserva (ahora sí es seguro)
    $existent = \App\Models\ReservaTemporal::where('sessio_id', $sessioId)
        ->where('seient_id', $seientId)
        ->where('expires_at', '>', now())
        ->where('usuari_id', '!=', $usuari->id)
        ->exists();

    if ($existent) {
        return response()->json(['error' => 'Seient ja reservat per un altre usuari'], 409);
    }

    \App\Models\ReservaTemporal::updateOrCreate(
        ['sessio_id' => $sessioId, 'seient_id' => $seientId, 'usuari_id' => $usuari->id],
        ['expires_at' => now()->addMinutes(10)]
    );
    
    // ... resto del código
    
} finally {
    Redis::del($lockKey);
}
```

### Alternativa con Redis WATCH (transacción optimista):

```php
Redis::watch("reserva:{$sessioId}:{$seientId}", function() use ($sessioId, $seientId, $usuari) {
    $existent = \App\Models\ReservaTemporal::where('sessio_id', $sessioId)
        ->where('seient_id', $seientId)
        ->where('expires_at', '>', now())
        ->where('usuari_id', '!=', $usuari->id)
        ->exists();
    
    if ($existent) {
        throw new \Exception('Seient ja reservat');
    }
    
    return \App\Models\ReservaTemporal::updateOrCreate(
        ['sessio_id' => $sessioId, 'seient_id' => $seientId, 'usuari_id' => $usuari->id],
        ['expires_at' => now()->addMinutes(10)]
    );
});
```

## Archivos a Modificar

1. **`backend-laravel/app/Http/Controllers/CompraController.php`** - Líneas 86-147 (método `reservarTemporal`)

## Verificación Post-Fix

1. Abrir dos navegadores/instancias
2. Seleccionar el mismo asiento desde ambos
3. Solo uno debe poder reservar, el otro debe recibir error 409

## Notas Adicionales

- El lock debe tener TTL corto (5-10s) para evitar deadlocks si algo falla
- Considerar también bloquear la operación de compra final (`comprar`) de forma similar
- El método `alliberarReservesSeleccionades()` en frontend debe seguir funcionando igual