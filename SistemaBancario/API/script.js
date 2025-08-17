
        document.addEventListener('DOMContentLoaded', function() {
            const apiUrl = 'api_clientes.php';
            const form = document.getElementById('cliente-form');
            const clienteIdInput = document.getElementById('cliente_id');
            const submitBtn = document.getElementById('submit-btn');
            const cancelBtn = document.getElementById('cancel-btn');
            const formTitle = document.getElementById('form-title');
            const messageDiv = document.getElementById('message');
            const clientesBody = document.getElementById('clientes-body');
            const searchBtn = document.getElementById('search-btn');
            const clearSearchBtn = document.getElementById('clear-search');
            
            let isEditing = false;
            
            // Cargar clientes al iniciar
            loadClientes();
            
            // Manejar envío del formulario
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const cliente = {
                    nombre: document.getElementById('nombre').value,
                    apellido: document.getElementById('apellido').value,
                    direccion: document.getElementById('direccion').value,
                    telefono: document.getElementById('telefono').value,
                    email: document.getElementById('email').value,
                    fecha_registro: document.getElementById('fecha_registro').value
                };
                
                if (isEditing) {
                    updateCliente(clienteIdInput.value, cliente);
                } else {
                    createCliente(cliente);
                }
            });
            
            // Manejar cancelar edición
            cancelBtn.addEventListener('click', function() {
                resetForm();
            });
            
            // Manejar búsqueda
           // En formulario_clientes.html, actualiza la función del botón buscar:
                // Manejar búsqueda
            searchBtn.addEventListener('click', function() {
                    const nombre = document.getElementById('search-nombre').value.trim();
                    const apellido = document.getElementById('search-apellido').value.trim();
                    const email = document.getElementById('search-email').value.trim();
                    
                    // Construir URL con parámetros de búsqueda
                    let url = apiUrl;
                    const params = [];
                    
                    if (nombre) params.push(`nombre=${encodeURIComponent(nombre)}`);
                    if (apellido) params.push(`apellido=${encodeURIComponent(apellido)}`);
                    if (email) params.push(`email=${encodeURIComponent(email)}`);
                    
                    if (params.length > 0) {
                        url += `?${params.join('&')}`;
                    }
                    
                    fetch(url)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Error en la búsqueda');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (!Array.isArray(data)) {
                                throw new Error('Formato de respuesta inválido');
                            }
                            
                            renderClientes(data);
                            
                            if (data.length === 0) {
                                showMessage('No se encontraron clientes con esos criterios', 'info');
                            } else {
                                showMessage(`Encontrados ${data.length} clientes`, 'success');
                            }
                        })
                        .catch(error => {
                            showMessage(error.message, 'error');
                            console.error('Error en búsqueda:', error);
                        });
                });
            
            // Limpiar búsqueda
            clearSearchBtn.addEventListener('click', function() {
                document.getElementById('search-nombre').value = '';
                document.getElementById('search-apellido').value = '';
                document.getElementById('search-email').value = '';
                loadClientes();
            });
            
            // Función para cargar todos los clientes
            function loadClientes() {
                fetch(apiUrl)
                    .then(response => response.json())
                    .then(data => {
                        renderClientes(data);
                    })
                    .catch(error => {
                        showMessage('Error al cargar clientes: ' + error, 'error');
                    });
            }
            
            // Función para crear un nuevo cliente
            function createCliente(cliente) {
                fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(cliente)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        showMessage(data.error, 'error');
                    } else {
                        showMessage('Cliente creado exitosamente', 'success');
                        resetForm();
                        loadClientes();
                    }
                })
                .catch(error => {
                    showMessage('Error al crear cliente: ' + error, 'error');
                });
            }
            
            // Función para actualizar un cliente
            function updateCliente(id, cliente) {
                fetch(`${apiUrl}/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(cliente)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        showMessage(data.error, 'error');
                    } else {
                        showMessage('Cliente actualizado exitosamente', 'success');
                        resetForm();
                        loadClientes();
                    }
                })
                .catch(error => {
                    showMessage('Error al actualizar cliente: ' + error, 'error');
                });
            }
            
            // Función para eliminar un cliente
            function deleteCliente(id) {
                if (confirm('¿Eliminar este cliente?')) {
                    fetch(`${apiUrl}/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ id: id }) // Envía el ID en el body
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw new Error(err.error) });
                        }
                        return response.json();
                    })
                    .then(data => {
                        showMessage(data.message || 'Cliente eliminado', 'success');
                        loadClientes();
                    })
                    .catch(error => {
                        showMessage(error.message, 'error');
                    });
                }
            }
            
            // Función para cargar datos en el formulario para edición
            function editCliente(id) {
                fetch(`${apiUrl}/${id}`)
                    .then(response => response.json())
                    .then(cliente => {
                        if (cliente.error) {
                            showMessage(cliente.error, 'error');
                        } else {
                            isEditing = true;
                            clienteIdInput.value = cliente.cliente_id;
                            document.getElementById('nombre').value = cliente.nombre;
                            document.getElementById('apellido').value = cliente.apellido;
                            document.getElementById('direccion').value = cliente.direccion;
                            document.getElementById('telefono').value = cliente.telefono;
                            document.getElementById('email').value = cliente.email;
                            document.getElementById('fecha_registro').value = cliente.fecha_registro;
                            
                            submitBtn.textContent = 'Actualizar';
                            formTitle.textContent = 'Editar Cliente';
                            cancelBtn.style.display = 'inline-block';
                        }
                    })
                    .catch(error => {
                        showMessage('Error al cargar cliente: ' + error, 'error');
                    });
            }
            
            // Función para resetear el formulario
            function resetForm() {
                form.reset();
                isEditing = false;
                clienteIdInput.value = '';
                submitBtn.textContent = 'Guardar';
                formTitle.textContent = 'Agregar Nuevo Cliente';
                cancelBtn.style.display = 'none';
            }
            
            // Función para mostrar mensajes
            function showMessage(message, type) {
                messageDiv.textContent = message;
                messageDiv.className = 'message ' + type;
                messageDiv.style.display = 'block';
                
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 5000);
            }
            
            // Función para renderizar la lista de clientes
            function renderClientes(clientes) {
                clientesBody.innerHTML = '';
                
                if (clientes.length === 0) {
                    const row = document.createElement('tr');
                    row.innerHTML = '<td colspan="8" style="text-align: center;">No hay clientes registrados</td>';
                    clientesBody.appendChild(row);
                    return;
                }
                
                clientes.forEach(cliente => {
                    const row = document.createElement('tr');
                    
                    row.innerHTML = `
                        <td>${cliente.cliente_id}</td>
                        <td>${cliente.nombre}</td>
                        <td>${cliente.apellido}</td>
                        <td>${cliente.direccion || ''}</td>
                        <td>${cliente.telefono || ''}</td>
                        <td>${cliente.email || ''}</td>
                        <td>${cliente.fecha_registro}</td>
                        <td class="actions">
                            <button class="btn-edit" onclick="editCliente(${cliente.cliente_id})">Editar</button>
                            <button class="btn-delete" onclick="deleteCliente(${cliente.cliente_id})">Eliminar</button>
                        </td>
                    `;
                    
                    clientesBody.appendChild(row);
                });
            }
            
            // Hacer funciones accesibles globalmente para los botones en las filas
            window.editCliente = editCliente;
            window.deleteCliente = deleteCliente;
        });
