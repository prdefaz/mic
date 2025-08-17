# 🏦 Sistema Bancario - Aplicación Web PHP

## Descripción

Sistema de gestión bancaria completo desarrollado en PHP puro, compatible con XAMPP (Apache, MySQL, PHP). La aplicación incluye control de acceso por roles, operaciones CRUD seguras y una interfaz moderna y responsiva.

## Características Principales

- ✅ **Autenticación Segura**: Login con password hashing y sesiones
- ✅ **Control de Acceso por Roles**: Administrador, Desarrollador, Supervisor
- ✅ **CRUD Completo**: Gestión de todas las entidades bancarias
- ✅ **Seguridad**: Declaraciones preparadas para prevenir inyección SQL
- ✅ **Interfaz Moderna**: Diseño responsivo y amigable
- ✅ **Búsquedas Avanzadas**: Filtros por múltiples criterios
- ✅ **Base de Datos Relacional**: Modelo ER completo con integridad referencial

## Estructura del Proyecto

```
SistemaBancario/
├── config.php                 # Configuración de base de datos
├── login.php                  # Página de autenticación
├── logout.php                 # Cierre de sesión
├── index.php                  # Dashboard principal
├── script_base_datos.sql      # Script completo de la base de datos
├── crud_sucursales.php        # Gestión de sucursales
├── crud_empleados.php         # Gestión de empleados
├── crud_clientes.php          # Gestión de clientes
├── crud_cuentas.php           # Gestión de cuentas bancarias
├── crud_transacciones.php     # Gestión de transacciones
├── crud_prestamos.php         # Gestión de préstamos
├── crud_tarjetas_credito.php  # Gestión de tarjetas de crédito
├── crud_usuarios.php          # Gestión de usuarios del sistema
└── README.md                  # Este archivo
```

## Requisitos del Sistema

- **Servidor Web**: Apache (incluido en XAMPP)
- **Base de Datos**: MySQL 5.7+ (incluido en XAMPP)
- **PHP**: 7.4+ (incluido en XAMPP)
- **Navegador**: Chrome, Firefox, Safari, Edge (moderno)

## Instalación

### 1. Configurar XAMPP

1. Descargar e instalar XAMPP desde [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Iniciar Apache y MySQL desde el panel de control de XAMPP
3. Verificar que ambos servicios estén ejecutándose correctamente

### 2. Configurar el Proyecto

1. Copiar la carpeta `SistemaBancario` al directorio `htdocs` de XAMPP:
   ```
   C:\xampp\htdocs\SistemaBancario\
   ```

2. Abrir phpMyAdmin en el navegador:
   ```
   http://localhost/phpmyadmin
   ```

3. Crear la base de datos ejecutando el script SQL:
   - Ir a la pestaña "SQL"
   - Copiar y pegar el contenido del archivo `script_base_datos.sql`
   - Ejecutar el script

### 3. Configurar la Conexión

1. Editar el archivo `config.php` si es necesario:
   ```php
   $servername = "localhost";
   $username = "root";        // Usuario por defecto de XAMPP
   $password = "";           // Contraseña por defecto de XAMPP
   $dbname = "BancoDB";
   ```

### 4. Acceder a la Aplicación

1. Abrir el navegador y navegar a:
   ```
   http://localhost/SistemaBancario/
   ```

2. Usar las credenciales de prueba:
   - **Administrador**: `admin` / `password`
   - **Desarrollador**: `desarrollador` / `password`
   - **Supervisor**: `supervisor` / `password`

## Estructura de la Base de Datos

### Tablas Principales

- **Sucursal**: Información de sucursales bancarias
- **Empleado**: Datos de empleados y puestos
- **Cliente**: Información de clientes del banco
- **Cuenta**: Cuentas bancarias (Ahorros, Corriente, Plazo Fijo)
- **Transaccion**: Movimientos bancarios (Depósitos, Retiros, Transferencias)
- **Prestamo**: Préstamos bancarios con intereses y plazos
- **TarjetaCredito**: Tarjetas de crédito con límites y estados
- **Usuario**: Usuarios del sistema con roles y permisos

### Relaciones

- Un empleado pertenece a una sucursal
- Un cliente puede tener múltiples cuentas
- Las transacciones están asociadas a cuentas y empleados
- Los préstamos están vinculados a clientes, empleados y sucursales
- Las tarjetas de crédito están asociadas a cuentas y clientes

## Matriz de Permisos

| Rol | Sucursal | Empleado | Cliente | Cuenta | Transacción | Préstamo | Tarjeta | Usuario |
|-----|----------|----------|---------|--------|-------------|----------|---------|---------|
| **Administrador** | C,R,U,D | C,R,U,D | C,R,U,D | C,R,U,D | C,R,U,D | C,R,U,D | C,R,U,D | C,R,U,D |
| **Desarrollador** | R | R | R | C,R,U,D | C,R,U,D | - | C,R,U,D | - |
| **Supervisor** | R | R | R | R | R | R | R | - |

**Leyenda**: C=Crear, R=Leer, U=Actualizar, D=Eliminar

## Funcionalidades por Módulo

### 🔐 Autenticación
- Login seguro con password hashing
- Control de sesiones
- Logout automático
- Protección contra acceso no autorizado

### 🏢 Gestión de Sucursales
- CRUD completo de sucursales
- Búsqueda por nombre y dirección
- Validación de integridad referencial

### 👥 Gestión de Empleados
- CRUD completo de empleados
- Asignación a sucursales
- Búsqueda avanzada por múltiples criterios

### 👤 Gestión de Clientes
- CRUD completo (solo Administrador)
- Solo lectura para otros roles
- Búsqueda por nombre, apellido, email
- Filtros por fecha de registro

### 💳 Gestión de Cuentas
- CRUD completo de cuentas bancarias
- Diferentes tipos: Ahorros, Corriente, Plazo Fijo
- Estados: Activa, Inactiva, Bloqueada
- Asociación con clientes, sucursales y empleados

### 💰 Gestión de Transacciones
- CRUD completo de transacciones
- Tipos: Depósito, Retiro, Transferencia
- Registro de fechas y empleados responsables
- Búsqueda por múltiples criterios

### 🏦 Gestión de Préstamos
- CRUD completo de préstamos
- Cálculo automático de cuotas mensuales
- Estados: Aprobado, Pagado, Moroso, Cancelado
- Asociación con clientes, empleados y sucursales

### 💳 Gestión de Tarjetas de Crédito
- CRUD completo de tarjetas
- Tipos: Clásica, Oro, Platino, Negra
- Enmascaramiento de números de tarjeta
- Estados: Activa, Bloqueada, Cancelada

### 🔐 Gestión de Usuarios
- CRUD completo de usuarios del sistema
- Asignación de roles y permisos
- Cambio de contraseñas
- Protección contra auto-eliminación

## Características de Seguridad

### 🔒 Protección contra Inyección SQL
- Uso de declaraciones preparadas (mysqli_prepare)
- Parámetros vinculados (bind_param)
- Escape de caracteres especiales

### 🛡️ Protección XSS
- Uso de htmlspecialchars() en toda la salida
- Validación de entrada de datos
- Sanitización de parámetros

### 🔐 Autenticación Segura
- Password hashing con password_hash()
- Verificación con password_verify()
- Sesiones seguras con session_start()

### 👥 Control de Acceso
- Verificación de roles en cada página
- Matriz de permisos granular
- Redirección automática para acceso no autorizado

## Personalización

### Modificar Estilos
Los estilos CSS están incluidos en cada archivo PHP. Para personalizar:

1. Editar las secciones `<style>` en cada archivo
2. O crear archivos CSS separados y enlazarlos

### Agregar Nuevas Funcionalidades
1. Crear nuevos archivos PHP siguiendo la estructura existente
2. Agregar las nuevas tablas a la base de datos
3. Actualizar la matriz de permisos en `config.php`
4. Agregar enlaces en el menú principal (`index.php`)

### Configurar Nuevos Roles
1. Modificar la función `tienePermiso()` en `config.php`
2. Actualizar la matriz de permisos
3. Agregar el nuevo rol en la tabla `Usuario`

## Solución de Problemas

### Error de Conexión a la Base de Datos
1. Verificar que MySQL esté ejecutándose en XAMPP
2. Confirmar credenciales en `config.php`
3. Verificar que la base de datos `BancoDB` exista

### Error de Permisos
1. Verificar que el usuario tenga el rol correcto
2. Confirmar que la matriz de permisos esté actualizada
3. Verificar la sesión del usuario

### Página en Blanco
1. Verificar errores en el log de PHP
2. Confirmar que todos los archivos estén en la ubicación correcta
3. Verificar permisos de archivos

### Problemas de Rendimiento
1. Optimizar consultas SQL
2. Implementar paginación en tablas grandes
3. Considerar índices en la base de datos

## Mantenimiento

### Respaldo de Base de Datos
```sql
-- Exportar base de datos completa
mysqldump -u root -p BancoDB > backup_banco_$(date +%Y%m%d).sql
```

### Limpieza de Logs
- Revisar logs de Apache en `C:\xampp\apache\logs\`
- Revisar logs de MySQL en `C:\xampp\mysql\data\`

### Actualizaciones
1. Hacer respaldo completo antes de actualizar
2. Probar cambios en entorno de desarrollo
3. Documentar todas las modificaciones

## Soporte Técnico

Para soporte técnico o reportar problemas:

1. Verificar que todos los requisitos estén cumplidos
2. Revisar los logs de error
3. Documentar el problema con capturas de pantalla
4. Incluir información del sistema y versión de PHP

## Licencia

Este proyecto es de uso educativo y demostrativo. Se permite su uso y modificación para fines de aprendizaje.
