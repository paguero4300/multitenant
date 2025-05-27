# 🧪 Guía de Pruebas de Permisos y Seguridad

## 📋 Descripción General

Este documento describe el conjunto completo de pruebas automatizadas para verificar el sistema de permisos y asignación de tenants en la aplicación Laravel multitenant.

## 🏗️ Estructura de Pruebas

### **1. Factories y Seeders**
- `TenantFactory.php` - Creación de tenants de prueba
- `UserFactory.php` - Creación de usuarios con diferentes roles
- `PowerBiDashboardFactory.php` - Creación de dashboards de prueba
- `TestPermissionsSeeder.php` - Datos de prueba consistentes

### **2. Suites de Pruebas**

#### **UserPermissionsTest.php**
Verifica el comportamiento de los diferentes tipos de usuario:
- ✅ Administrador global
- ✅ Administrador de tenant
- ✅ Usuario regular
- ✅ Usuario sin tenant
- ✅ Usuario multi-tenant
- ✅ Validaciones de modelo

#### **DashboardAccessTest.php**
Prueba el acceso a dashboards de Power BI:
- ✅ Acceso por tipo de usuario
- ✅ Filtrado por tenant
- ✅ Creación y gestión
- ✅ Casos edge con datos nulos

#### **MiddlewareTest.php**
Verifica el funcionamiento de los middleware:
- ✅ `AdminMiddleware`
- ✅ `EnsureUserBelongsToTenant`
- ✅ Redirecciones apropiadas
- ✅ Manejo de usuarios no autenticados

#### **SecurityTest.php**
Pruebas de seguridad y vulnerabilidades:
- ✅ Prevención de escalación de privilegios
- ✅ Aislamiento entre tenants
- ✅ Protección contra inyección SQL
- ✅ Validaciones de entrada

## 🚀 Comandos de Ejecución

### **Ejecutar todas las pruebas de permisos:**
```bash
php artisan test:permissions
```

### **Con reporte de cobertura:**
```bash
php artisan test:permissions --coverage
```

### **Filtrar pruebas específicas:**
```bash
php artisan test:permissions --filter=global_admin
```

### **Ejecutar suite específica:**
```bash
vendor/bin/phpunit --testsuite=Permissions
```

### **Ejecutar archivo específico:**
```bash
vendor/bin/phpunit tests/Feature/UserPermissionsTest.php
```

## 📊 Casos de Prueba Cubiertos

### **1. Perfiles de Usuario**

| Tipo de Usuario | Permisos Esperados | Pruebas |
|----------------|-------------------|---------|
| Admin Global | Acceso total | ✅ Acceso a todos los tenants<br>✅ Panel de administración<br>✅ Gestión completa |
| Admin Tenant | Solo su tenant | ✅ Acceso limitado<br>✅ No puede ver otros tenants<br>✅ Redirección apropiada |
| Usuario Regular | Solo su tenant | ✅ Acceso de solo lectura<br>✅ Sin acceso admin<br>✅ Filtrado correcto |
| Sin Tenant | Sin acceso | ✅ Bloqueo total<br>✅ Mensajes de error<br>✅ Redirección al login |

### **2. Acceso a Dashboards**

| Escenario | Resultado Esperado | Estado |
|-----------|-------------------|--------|
| Admin global ve todos | ✅ Permitido | ✅ Probado |
| Admin tenant ve solo suyos | ✅ Permitido | ✅ Probado |
| Usuario regular ve solo suyos | ✅ Permitido | ✅ Probado |
| Acceso cruzado entre tenants | ❌ Bloqueado | ✅ Probado |
| Dashboards con datos nulos | ✅ Manejado | ✅ Probado |

### **3. Middleware de Seguridad**

| Middleware | Función | Casos Probados |
|------------|---------|----------------|
| `AdminMiddleware` | Protege panel admin | ✅ Admin global pasa<br>✅ Otros son redirigidos<br>✅ Sin tenant = error |
| `EnsureUserBelongsToTenant` | Aislamiento de tenants | ✅ Acceso propio permitido<br>✅ Acceso ajeno bloqueado<br>✅ Admin global pasa |

### **4. Validaciones de Seguridad**

| Vulnerabilidad | Protección | Estado |
|----------------|------------|--------|
| Escalación de privilegios | Validaciones de modelo | ✅ Bloqueado |
| Acceso cruzado entre tenants | Middleware + lógica | ✅ Bloqueado |
| Inyección SQL | Eloquent ORM | ✅ Protegido |
| CSRF | Laravel CSRF | ✅ Activo |
| Mass Assignment | Validaciones | ✅ Protegido |

## 🔧 Configuración de Entorno de Pruebas

### **Variables de Entorno (.env.testing):**
```env
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
CACHE_DRIVER=array
SESSION_DRIVER=array
QUEUE_DRIVER=sync
```

### **Preparación de Datos:**
```bash
# Crear datos de prueba
php artisan db:seed --class=TestPermissionsSeeder --env=testing

# Refrescar base de datos
php artisan migrate:fresh --env=testing
```

## 📈 Métricas de Cobertura

### **Objetivos de Cobertura:**
- **Modelos:** 95%+ (User, Tenant, PowerBiDashboard)
- **Middleware:** 100% (AdminMiddleware, EnsureUserBelongsToTenant)
- **Controladores:** 80%+ (Recursos de Filament)
- **Rutas:** 90%+ (Admin y Tenant)

### **Generar Reporte:**
```bash
php artisan test:permissions --coverage
# Ver en: storage/app/test-coverage/index.html
```

## 🚨 Casos Edge Importantes

### **1. Usuario sin Tenant:**
```php
$orphanUser = User::factory()->withoutTenant()->create();
// Debe ser bloqueado en todas las rutas de tenant
```

### **2. Permisos Inconsistentes:**
```php
// Esto debe fallar:
User::create([
    'is_admin' => true,
    'is_tenant_admin' => true, // ❌ Inconsistente
]);
```

### **3. Admin de Tenant sin Tenant:**
```php
// Esto debe fallar:
User::create([
    'is_tenant_admin' => true,
    'tenant_id' => null, // ❌ Inconsistente
]);
```

## 🔍 Debugging de Pruebas

### **Ejecutar con debug:**
```bash
vendor/bin/phpunit tests/Feature/UserPermissionsTest.php --debug
```

### **Ver logs durante pruebas:**
```bash
tail -f storage/logs/laravel.log
```

### **Inspeccionar base de datos:**
```php
// En las pruebas:
dd(User::all()->toArray());
dd(Tenant::all()->toArray());
```

## 📝 Mantenimiento

### **Agregar Nuevas Pruebas:**
1. Crear archivo en `tests/Feature/`
2. Extender `TestCase`
3. Usar `RefreshDatabase` trait
4. Agregar al suite `Permissions` en `phpunit.xml`

### **Actualizar Factories:**
1. Modificar factories en `database/factories/`
2. Actualizar seeder si es necesario
3. Ejecutar pruebas para verificar

### **Monitoreo Continuo:**
```bash
# Ejecutar antes de cada commit
php artisan test:permissions

# Ejecutar en CI/CD
vendor/bin/phpunit --testsuite=Permissions --coverage-clover=coverage.xml
```

## 🎯 Checklist de Verificación

Antes de desplegar a producción, verificar:

- [ ] ✅ Todas las pruebas de permisos pasan
- [ ] ✅ Cobertura de código > 90%
- [ ] ✅ No hay vulnerabilidades de seguridad
- [ ] ✅ Middleware funcionando correctamente
- [ ] ✅ Validaciones de modelo activas
- [ ] ✅ Aislamiento entre tenants verificado
- [ ] ✅ Casos edge manejados apropiadamente

## 📞 Soporte

Para problemas con las pruebas:
1. Verificar configuración de entorno de testing
2. Revisar logs de Laravel
3. Ejecutar pruebas individuales para aislar problemas
4. Verificar que las migraciones estén actualizadas
