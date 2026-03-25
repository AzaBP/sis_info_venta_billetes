# 🚂 IoT Sensor Simulator - Documentación

## Descripción General

El sistema de simulación de sensores IoT genera automáticamente incidencias realistas detectadas por los sensores del tren. Las incidencias se crean en tiempo real y si no se confirman en 24 horas, se eliminan automáticamente.

## Características

### 📊 Sensores Simulados

1. **Sensor de Temperatura Motor** - Detecta sobrecalentamiento del motor
2. **Sensor de Presión de Frenos** - Monitorea la presión del sistema de frenos
3. **Sensor de Vibración en Ejes** - Detecta vibraciones anormales
4. **Sensor de Puerta** - Identifica fallas en mecanismos de puertas
5. **Sensor de Temperatura Rodamientos** - Monitorea el calentamiento de rodamientos
6. **Sensor de Nivel de Aceite** - Verifica niveles adecuados de lubricación
7. **Sensor Eléctrico** - Detecta anomalías en voltaje y corriente
8. **Sensor de Ocupación** - Monitorea ocupación de compartimentos
9. **Sensor de Desgaste de Ruedas** - Mide desgaste superficial
10. **Sensor de Colisión** - Sistema anti-colisión

### ⏱️ Plazos Automáticos

- **Limpieza automática**: Incidencias no confirmadas se eliminan después de 24 horas
- **Intervalo de generación**: Cada 30 segundos se intenta generar nuevas incidencias
- **Realismo**: Las probabilidades de fallo varían por tipo de sensor

## Configuración

### 1. Variable de Token IoT

El sistema requiere un token IoT para validar las solicitudes. Configurar en `.env`:

```env
TRAINWEB_IOT_TOKEN=tu_token_iot_secreto_aqui
```

O establecer como variable de entorno del sistema.

### 2. Carga del Script

El script se carga automáticamente en `mantenimiento.php`:

```html
<script src="js/iot_simulador.js?v=20260325a"></script>
```

### 3. Inicialización Automática

El simulador se inicializa automáticamente cuando se carga la página de mantenimiento. Para control manual:

```javascript
// Acceder al simulador global
window.iotSimulador

// Detener simulación
window.iotSimulador.detener();

// Cambiar intervalo (en milisegundos)
window.iotSimulador.cambiarIntervalo(60000); // 1 minuto

// Reanudar
window.iotSimulador.iniciar();
```

## Archivos Utilizados

### Backend
- `php/sensor_iot_simulador.php` - Script generador de incidencias
- `php/api_incidencias_reportar_iot.php` - API para registrar incidencias
- `php/api_incidencias_listar_mantenimiento.php` - API para listar incidencias

### Frontend
- `js/iot_simulador.js` - Gestor del simulador
- `mantenimiento.php` - Interfaz de usuario

## Lógica de Funcionamiento

### 1. Generación de Incidencias

```
① Cada 30 segundos se ejecuta sensor_iot_simulador.php
② Por cada viaje activo (en_transito, proximo):
   ③ Se itera sobre 10 tipos de sensores
   ④ Se valida probabilidad de fallo (2-3%)
   ⑤ Se evitan incidencias duplicadas
   ⑥ Se registra en BD si cumple criterios
⑦ Se actualiza el panel en tiempo real
```

### 2. Limpieza Automática

```
① Al ejecutar el simulador se ejecuta limpieza
② DELETE incidencias donde:
   ③ origen = 'iot'
   ④ estado = 'reportado' (no confirmadas)
   ⑤ fecha_reporte < (NOW() - 24 HORAS)
```

### 3. Actualización en Tiempo Real

```
① Se generan incidencias en BD
② JavaScript recibe respuesta
③ Panel se actualiza vía AJAX
④ Se muestran notificaciones (si permitidas)
⑤ Se re-adjuntan event listeners
```

## Ejemplos de Incidencias Generadas

### 🔴 Críticas (Afecta Pasajeros)
```
"Temperatura del motor: 95°C (umbral crítico 90°C)"
"Presión de frenos baja: 6.3 bar (mínimo requerido: 6.5 bar)"
"Puerta del coche 3 ciclo defectuoso - No cierra correctamente"
```

### 🟡 Moderadas (Mantenimiento)
```
"Nivel de aceite motor bajo - Recarga recomendada"
"Sensor ocupación coche 2 defectuoso - Lecturas inconsistentes"
"Voltaje auxiliar fuera de rango: 23.5V (rango: 24V ±2V)"
```

## Estadísticas

| Sensor | Probabilidad | Afecta Pasajero |
|--------|-------------|-----------------|
| Temp Motor | 2% | ✅ Sí |
| Presión Frenos | 3% | ✅ Sí |
| Vibración Ejes | 1.5% | ✅ Sí |
| Fallo Puerta | 2% | ✅ Sí |
| Temp Rodamientos | 1.8% | ✅ Sí |
| Nivel Aceite | 1% | ❌ No |
| Fallo Eléctrico | 1.2% | ❌ No |
| Sensor Ocupación | 0.5% | ❌ No |
| Desgaste Ruedas | 0.8% | ✅ Sí |
| Sensor Colisión | 0.6% | ✅ Sí |

## Notificaciones

Si el navegador tiene permisos de notificación habilitados:
- Se muestra una notificación cuando se generan incidencias
- Incluye detalles de los sensores afectados
- Permite seguimiento en tiempo real

## Posibles Mejoras Futuras

- [ ] Simulación basada en datos históricos reales
- [ ] Patrones de fallo por tipo de tren
- [ ] Correlación entre sensores
- [ ] Predicción predictiva de fallos
- [ ] Dashboard de estadísticas de sensores
- [ ] Exportación de reportes de confiabilidad
- [ ] Integración con sistema de mantenimiento predictivo

## Troubleshooting

### No se generan incidencias
- Verificar que hay viajes activos (estado: en_transito, proximo)
- Verificar token IoT en variables de entorno
- Revisar consola del navegador para errores
- Verificar que hay mantenedores registrados

### Las incidencias se generan muy lentamente
- Cambiar intervalo: `window.iotSimulador.cambiarIntervalo(10000)` (10 seg)
- Aumentar probabilidades en sensor_iot_simulador.php

### Las incidencias no se eliminan a los 24h
- Verificar que no están siendo confirmadas
- Revisar que estado es 'reportado' (no en_proceso ni resuelto)
- Forzar limpieza manual: Actualizar página

## API Reference

### POST php/sensor_iot_simulador.php

Genera nuevas incidencias de sensores IoT.

**Headers:**
```
X-IoT-Token: <token>
Content-Type: application/x-www-form-urlencoded
```

**Response:**
```json
{
  "ok": true,
  "incidencias_generadas": 2,
  "detalles": [
    {
      "viaje": 15,
      "sensor": "Sensor de Temperatura Motor",
      "tipo": "temperatura_motor",
      "descripcion": "Temperatura del motor: 95°C (umbral crítico 90°C)"
    }
  ],
  "timestamp": "2026-03-25 14:30:45"
}
```

---

**Creado:** 2026-03-25
**Versión:** 1.0
**Estado:** ✅ Producción
