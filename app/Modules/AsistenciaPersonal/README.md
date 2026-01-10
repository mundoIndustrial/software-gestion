# Módulo de Asistencia Personal - Documentación DDD

## Estructura del Módulo

```
app/Modules/AsistenciaPersonal/
├── Domain/                          # Capa de Dominio
│   ├── Models/                     # Entidades del dominio
│   ├── Repositories/               # Interfaces de repositorios
│   ├── Services/                   # Servicios de dominio
│   └── ValueObjects/               # Objetos de valor
├── Application/                     # Capa de Aplicación
│   ├── DTOs/                       # Data Transfer Objects
│   ├── Services/                   # Servicios de aplicación
│   └── UseCases/                   # Casos de uso
├── Infrastructure/                  # Capa de Infraestructura
│   └── Persistence/                # Implementación de persistencia
└── Presentation/                    # Capa de Presentación
    ├── Controllers/                # Controladores
    ├── Requests/                   # Form Requests
    └── Resources/                  # API Resources
```

## Capas

### 1. Domain (Dominio)
- **Models**: Entidades principales del dominio (Reporte de Asistencia, Registro de Personal, etc.)
- **Repositories**: Interfaces para acceso a datos
- **Services**: Lógica de negocio pura
- **ValueObjects**: Objetos inmutables sin identidad (Fecha, Hora, etc.)

### 2. Application (Aplicación)
- **DTOs**: Objetos para transferencia de datos
- **Services**: Orquestación de la lógica
- **UseCases**: Implementación de casos de uso específicos

### 3. Infrastructure (Infraestructura)
- **Persistence**: Implementación concreta de repositorios
- **Providers**: Service providers del módulo

### 4. Presentation (Presentación)
- **Controllers**: Controladores HTTP
- **Requests**: Validación de inputs
- **Resources**: Transformación de datos para respuestas

## Principios DDD Aplicados

1. **Ubiquitous Language**: El código habla el lenguaje del negocio
2. **Entities**: Objetos con identidad única
3. **Value Objects**: Objetos sin identidad
4. **Repositories**: Abstracción para acceso a datos
5. **Services**: Lógica que no pertenece a entidades
6. **Aggregates**: Grupos de objetos tratados como unidad

## Flujo de Datos

```
Request → Controller → UseCase → Service → Repository → Database
                         ↓
Response ← Resource ← Service ← Repository
```

## Próximas Implementaciones

- [ ] Modelos del dominio
- [ ] Interfaces de repositorios
- [ ] Servicios de aplicación
- [ ] Casos de uso
- [ ] Validaciones
- [ ] APIs REST
- [ ] Eventos del dominio
