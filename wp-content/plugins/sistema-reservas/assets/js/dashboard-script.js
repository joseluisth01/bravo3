// Variables globales
let currentDate = new Date();
let servicesData = {};
let bulkHorarios = [];

function loadCalendarSection() {
    document.body.innerHTML = `
        <div class="calendar-management">
            <div class="calendar-header">
                <h1>Gestión de Calendario</h1>
                <div class="calendar-actions">
                    <button class="btn-primary" onclick="showBulkAddModal()">➕ Añadir Múltiples Servicios</button>
                    <button class="btn-secondary" onclick="goBackToDashboard()">← Volver al Dashboard</button>
                </div>
            </div>
            
            <div class="calendar-controls">
                <button onclick="changeMonth(-1)">← Mes Anterior</button>
                <span id="currentMonth"></span>
                <button onclick="changeMonth(1)">Siguiente Mes →</button>
            </div>
            
            <div id="calendar-container">
                <div class="loading">Cargando calendario...</div>
            </div>
        </div>
    `;

    // Inicializar el calendario
    initCalendar();
}

function initCalendar() {
    updateCalendarDisplay();
    loadCalendarData();
}

function updateCalendarDisplay() {
    const monthNames = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];

    document.getElementById('currentMonth').textContent =
        monthNames[currentDate.getMonth()] + ' ' + currentDate.getFullYear();
}

function changeMonth(direction) {
    currentDate.setMonth(currentDate.getMonth() + direction);
    updateCalendarDisplay();
    loadCalendarData();
}

function loadCalendarData() {
    console.log('Iniciando carga de calendario');

    const formData = new FormData();
    formData.append('action', 'get_calendar_data');
    formData.append('month', currentDate.getMonth() + 1);
    formData.append('year', currentDate.getFullYear());
    formData.append('nonce', reservasAjax.nonce);

    fetch(reservasAjax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text);

            try {
                const data = JSON.parse(text);
                console.log('Parsed JSON:', data);

                if (data.success) {
                    servicesData = data.data;
                    renderCalendar();
                } else {
                    alert('Error del servidor: ' + data.data);
                }
            } catch (e) {
                console.error('Error parsing JSON:', e);
                console.error('Raw text that failed to parse:', text);
                alert('Error: respuesta no es JSON válido. Ver consola para detalles.');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Error de conexión: ' + error.message);
        });
}

function renderCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    let firstDayOfWeek = firstDay.getDay();
    firstDayOfWeek = (firstDayOfWeek + 6) % 7;

    const daysInMonth = lastDay.getDate();
    const dayNames = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

    let calendarHTML = '<div class="calendar-grid">';

    // Encabezados de días
    dayNames.forEach(day => {
        calendarHTML += `<div class="calendar-header-day">${day}</div>`;
    });

    for (let i = 0; i < firstDayOfWeek; i++) {
        const dayNum = new Date(year, month, -firstDayOfWeek + i + 1).getDate();
        calendarHTML += `<div class="calendar-day other-month">
            <div class="day-number">${dayNum}</div>
        </div>`;
    }

    // Días del mes actual
    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const isToday = dateStr === new Date().toISOString().split('T')[0];
        const todayClass = isToday ? ' today' : '';

        // ACTUALIZADO: Verificar si algún servicio tiene descuento
        let hasDiscount = false;
        if (servicesData[dateStr]) {
            hasDiscount = servicesData[dateStr].some(service => 
                service.tiene_descuento && parseFloat(service.porcentaje_descuento) > 0
            );
        }

        let servicesHTML = '';
        if (servicesData[dateStr]) {
            servicesData[dateStr].forEach(service => {
                let serviceClass = 'service-item';
                let discountText = '';
                
                if (service.tiene_descuento && parseFloat(service.porcentaje_descuento) > 0) {
                    serviceClass += ' service-discount';
                    discountText = ` (${service.porcentaje_descuento}% OFF)`;
                }
                
                servicesHTML += `<div class="${serviceClass}" onclick="editService(${service.id})">${service.hora}${discountText}</div>`;
            });
        }

        let dayClass = `calendar-day${todayClass}`;
        if (hasDiscount) {
            dayClass += ' day-with-discount';
        }

        calendarHTML += `<div class="${dayClass}" onclick="addService('${dateStr}')">
            <div class="day-number">${day}</div>
            ${servicesHTML}
        </div>`;
    }

    calendarHTML += '</div>';

    // Modales
    calendarHTML += getModalHTML();

    document.getElementById('calendar-container').innerHTML = calendarHTML;

    // Inicializar eventos de los modales
    initModalEvents();
}

function getModalHTML() {
    return `
        <!-- Modal Añadir/Editar Servicio -->
        <div id="serviceModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeServiceModal()">&times;</span>
                <h3 id="serviceModalTitle">Añadir Servicio</h3>
                <form id="serviceForm">
                    <input type="hidden" id="serviceId" name="service_id">
                    <div class="form-group">
                        <label for="serviceFecha">Fecha:</label>
                        <input type="date" id="serviceFecha" name="fecha" required>
                    </div>
                    <div class="form-group">
                        <label for="serviceHora">Hora:</label>
                        <input type="time" id="serviceHora" name="hora" required>
                    </div>
                    <div class="form-group">
                        <label for="servicePlazas">Plazas Totales:</label>
                        <input type="number" id="servicePlazas" name="plazas_totales" min="1" max="100" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="precioAdulto">Precio Adulto (€):</label>
                            <input type="number" id="precioAdulto" name="precio_adulto" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="precioNino">Precio Niño (€):</label>
                            <input type="number" id="precioNino" name="precio_nino" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="precioResidente">Precio Residente (€):</label>
                        <input type="number" id="precioResidente" name="precio_residente" step="0.01" min="0" required>
                    </div>
                    
                    <!-- NUEVO: Sección de descuento -->
                    <div class="form-group discount-section">
                        <label>
                            <input type="checkbox" id="tieneDescuento" name="tiene_descuento"> 
                            Activar descuento especial para este servicio
                        </label>
                        <div id="discountFields" style="display: none; margin-top: 10px;">
                            <label for="porcentajeDescuento">Porcentaje de descuento (%):</label>
                            <input type="number" id="porcentajeDescuento" name="porcentaje_descuento" 
                                   min="0" max="100" step="0.1" placeholder="Ej: 15">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Guardar Servicio</button>
                        <button type="button" class="btn-secondary" onclick="closeServiceModal()">Cancelar</button>
                        <button type="button" id="deleteServiceBtn" class="btn-danger" onclick="deleteService()" style="display: none;">Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Modal Añadir Múltiples Servicios -->
        <div id="bulkAddModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeBulkAddModal()">&times;</span>
                <h3>Añadir Múltiples Servicios</h3>
                <form id="bulkAddForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="bulkFechaInicio">Fecha Inicio:</label>
                            <input type="date" id="bulkFechaInicio" name="fecha_inicio" required>
                        </div>
                        <div class="form-group">
                            <label for="bulkFechaFin">Fecha Fin:</label>
                            <input type="date" id="bulkFechaFin" name="fecha_fin" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Días de la semana:</label>
                        <div class="dias-semana">
                            <div class="dia-checkbox">
                                <input type="checkbox" id="dom" name="dias_semana[]" value="0">
                                <label for="dom">Dom</label>
                            </div>
                            <div class="dia-checkbox">
                                <input type="checkbox" id="lun" name="dias_semana[]" value="1">
                                <label for="lun">Lun</label>
                            </div>
                            <div class="dia-checkbox">
                                <input type="checkbox" id="mar" name="dias_semana[]" value="2">
                                <label for="mar">Mar</label>
                            </div>
                            <div class="dia-checkbox">
                                <input type="checkbox" id="mie" name="dias_semana[]" value="3">
                                <label for="mie">Mié</label>
                            </div>
                            <div class="dia-checkbox">
                                <input type="checkbox" id="jue" name="dias_semana[]" value="4">
                                <label for="jue">Jue</label>
                            </div>
                            <div class="dia-checkbox">
                                <input type="checkbox" id="vie" name="dias_semana[]" value="5">
                                <label for="vie">Vie</label>
                            </div>
                            <div class="dia-checkbox">
                                <input type="checkbox" id="sab" name="dias_semana[]" value="6">
                                <label for="sab">Sáb</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Horarios:</label>
                        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                            <input type="time" id="nuevoHorario" placeholder="Hora">
                            <button type="button" class="btn-primary" onclick="addHorario()">Añadir</button>
                        </div>
                        <div id="horariosList" class="horarios-list">
                            <!-- Los horarios se añadirán aquí -->
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bulkPlazas">Plazas por Servicio:</label>
                        <input type="number" id="bulkPlazas" name="plazas_totales" min="1" max="100" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="bulkPrecioAdulto">Precio Adulto (€):</label>
                            <input type="number" id="bulkPrecioAdulto" name="precio_adulto" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="bulkPrecioNino">Precio Niño (€):</label>
                            <input type="number" id="bulkPrecioNino" name="precio_nino" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bulkPrecioResidente">Precio Residente (€):</label>
                        <input type="number" id="bulkPrecioResidente" name="precio_residente" step="0.01" min="0" required>
                    </div>
                    
                    <!-- NUEVO: Sección de descuento para bulk -->
                    <div class="form-group discount-section">
                        <label>
                            <input type="checkbox" id="bulkTieneDescuento" name="bulk_tiene_descuento"> 
                            Aplicar descuento especial a todos los servicios
                        </label>
                        <div id="bulkDiscountFields" style="display: none; margin-top: 10px;">
                            <label for="bulkPorcentajeDescuento">Porcentaje de descuento (%):</label>
                            <input type="number" id="bulkPorcentajeDescuento" name="bulk_porcentaje_descuento" 
                                   min="0" max="100" step="0.1" placeholder="Ej: 15">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Crear Servicios</button>
                        <button type="button" class="btn-secondary" onclick="closeBulkAddModal()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    `;
}

function initModalEvents() {
    // Formulario de servicio individual
    document.getElementById('serviceForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveService();
    });

    // Formulario de servicios masivos
    document.getElementById('bulkAddForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveBulkServices();
    });

    // NUEVO: Eventos para los checkboxes de descuento
    document.getElementById('tieneDescuento').addEventListener('change', function() {
        const discountFields = document.getElementById('discountFields');
        if (this.checked) {
            discountFields.style.display = 'block';
        } else {
            discountFields.style.display = 'none';
            document.getElementById('porcentajeDescuento').value = '';
        }
    });

    document.getElementById('bulkTieneDescuento').addEventListener('change', function() {
        const bulkDiscountFields = document.getElementById('bulkDiscountFields');
        if (this.checked) {
            bulkDiscountFields.style.display = 'block';
        } else {
            bulkDiscountFields.style.display = 'none';
            document.getElementById('bulkPorcentajeDescuento').value = '';
        }
    });
}

function addService(fecha) {
    document.getElementById('serviceModalTitle').textContent = 'Añadir Servicio';
    document.getElementById('serviceForm').reset();
    document.getElementById('serviceId').value = '';
    document.getElementById('serviceFecha').value = fecha;
    document.getElementById('deleteServiceBtn').style.display = 'none';

    // Valores por defecto
    document.getElementById('servicePlazas').value = 50;
    document.getElementById('precioAdulto').value = 10.00;
    document.getElementById('precioNino').value = 5.00;
    document.getElementById('precioResidente').value = 5.00;

    // NUEVO: Ocultar campos de descuento por defecto
    document.getElementById('discountFields').style.display = 'none';
    document.getElementById('tieneDescuento').checked = false;
    document.getElementById('porcentajeDescuento').value = '';

    document.getElementById('serviceModal').style.display = 'block';
}

function editService(serviceId) {
    const formData = new FormData();
    formData.append('action', 'get_service_details');
    formData.append('service_id', serviceId);
    formData.append('nonce', reservasAjax.nonce);

    fetch(reservasAjax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const service = data.data;
                document.getElementById('serviceModalTitle').textContent = 'Editar Servicio';
                document.getElementById('serviceId').value = service.id;
                document.getElementById('serviceFecha').value = service.fecha;
                document.getElementById('serviceHora').value = service.hora;
                document.getElementById('servicePlazas').value = service.plazas_totales;
                document.getElementById('precioAdulto').value = service.precio_adulto;
                document.getElementById('precioNino').value = service.precio_nino;
                document.getElementById('precioResidente').value = service.precio_residente;
                
                // NUEVO: Cargar datos de descuento
                const tieneDescuento = service.tiene_descuento == '1';
                document.getElementById('tieneDescuento').checked = tieneDescuento;
                
                if (tieneDescuento) {
                    document.getElementById('discountFields').style.display = 'block';
                    document.getElementById('porcentajeDescuento').value = service.porcentaje_descuento || '';
                } else {
                    document.getElementById('discountFields').style.display = 'none';
                    document.getElementById('porcentajeDescuento').value = '';
                }

                document.getElementById('deleteServiceBtn').style.display = 'block';
                document.getElementById('serviceModal').style.display = 'block';
            } else {
                alert('Error al cargar el servicio: ' + data.data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión');
        });
}

function saveService() {
    const formData = new FormData(document.getElementById('serviceForm'));
    formData.append('action', 'save_service');
    formData.append('nonce', reservasAjax.nonce);

    fetch(reservasAjax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Servicio guardado correctamente');
                closeServiceModal();
                loadCalendarData();
            } else {
                alert('Error: ' + data.data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión');
        });
}

function deleteService() {
    if (!confirm('¿Estás seguro de que quieres eliminar este servicio?')) {
        return;
    }

    const serviceId = document.getElementById('serviceId').value;
    const formData = new FormData();
    formData.append('action', 'delete_service');
    formData.append('service_id', serviceId);
    formData.append('nonce', reservasAjax.nonce);

    fetch(reservasAjax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Servicio eliminado correctamente');
                closeServiceModal();
                loadCalendarData();
            } else {
                alert('Error: ' + data.data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión');
        });
}

function closeServiceModal() {
    document.getElementById('serviceModal').style.display = 'none';
}

function showBulkAddModal() {
    document.getElementById('bulkAddForm').reset();
    bulkHorarios = [];
    updateHorariosList();

    // Valores por defecto
    document.getElementById('bulkPlazas').value = 50;
    document.getElementById('bulkPrecioAdulto').value = 10.00;
    document.getElementById('bulkPrecioNino').value = 5.00;
    document.getElementById('bulkPrecioResidente').value = 5.00;

    // NUEVO: Ocultar campos de descuento por defecto
    document.getElementById('bulkDiscountFields').style.display = 'none';
    document.getElementById('bulkTieneDescuento').checked = false;
    document.getElementById('bulkPorcentajeDescuento').value = '';

    document.getElementById('bulkAddModal').style.display = 'block';
}

function closeBulkAddModal() {
    document.getElementById('bulkAddModal').style.display = 'none';
}

function addHorario() {
    const horarioInput = document.getElementById('nuevoHorario');
    const horario = horarioInput.value;

    if (horario && !bulkHorarios.find(h => h.hora === horario)) {
        bulkHorarios.push({
            hora: horario
        });
        horarioInput.value = '';
        updateHorariosList();
    }
}

function removeHorario(index) {
    bulkHorarios.splice(index, 1);
    updateHorariosList();
}

function updateHorariosList() {
    const container = document.getElementById('horariosList');

    if (bulkHorarios.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #666;">No hay horarios añadidos</p>';
        return;
    }

    let html = '';
    bulkHorarios.forEach((horario, index) => {
        html += `
            <div class="horario-item">
                <span>${horario.hora}</span>
                <button type="button" class="btn-small btn-danger" onclick="removeHorario(${index})">Eliminar</button>
            </div>
        `;
    });

    container.innerHTML = html;
}

function saveBulkServices() {
    if (bulkHorarios.length === 0) {
        alert('Debes añadir al menos un horario');
        return;
    }

    const formData = new FormData(document.getElementById('bulkAddForm'));
    formData.append('action', 'bulk_add_services');
    formData.append('horarios', JSON.stringify(bulkHorarios));
    formData.append('nonce', reservasAjax.nonce);

    // Obtener días de la semana seleccionados
    const diasSeleccionados = [];
    document.querySelectorAll('input[name="dias_semana[]"]:checked').forEach(checkbox => {
        diasSeleccionados.push(checkbox.value);
    });

    diasSeleccionados.forEach(dia => {
        formData.append('dias_semana[]', dia);
    });

    fetch(reservasAjax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.data.mensaje);
                closeBulkAddModal();
                loadCalendarData();
            } else {
                alert('Error: ' + data.data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión');
        });
}

function goBackToDashboard() {
    location.reload();
}


function loadDiscountsConfigSection() {
    document.body.innerHTML = `
        <div class="discounts-management">
            <div class="discounts-header">
                <h1>Configuración de Descuentos</h1>
                <div class="discounts-actions">
                    <button class="btn-primary" onclick="showAddDiscountModal()">➕ Añadir Nueva Regla</button>
                    <button class="btn-secondary" onclick="goBackToDashboard()">← Volver al Dashboard</button>
                </div>
            </div>
            
            <div class="current-rules-section">
                <h3>Reglas de Descuento Actuales</h3>
                <div id="discounts-list">
                    <div class="loading">Cargando reglas de descuento...</div>
                </div>
            </div>
        </div>
        
        <!-- Modal Añadir/Editar Regla de Descuento -->
        <div id="discountModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeDiscountModal()">&times;</span>
                <h3 id="discountModalTitle">Añadir Regla de Descuento</h3>
                <form id="discountForm">
                    <input type="hidden" id="discountId" name="discount_id">
                    
                    <div class="form-group">
                        <label for="ruleName">Nombre de la Regla:</label>
                        <input type="text" id="ruleName" name="rule_name" placeholder="Ej: Descuento Grupo Grande" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="minimumPersons">Mínimo de Personas:</label>
                            <input type="number" id="minimumPersons" name="minimum_persons" min="1" max="100" placeholder="10" required>
                        </div>
                        <div class="form-group">
                            <label for="discountPercentage">Porcentaje de Descuento (%):</label>
                            <input type="number" id="discountPercentage" name="discount_percentage" min="1" max="100" step="0.1" placeholder="15" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="applyTo">Aplicar a:</label>
                        <select id="applyTo" name="apply_to" required>
                            <option value="total">Total de la reserva</option>
                            <option value="adults_only">Solo adultos</option>
                            <option value="all_paid">Todas las personas que pagan</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="ruleDescription">Descripción:</label>
                        <textarea id="ruleDescription" name="rule_description" rows="3" placeholder="Describe cuándo se aplica este descuento"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="isActive" name="is_active" checked>
                            Regla activa
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Guardar Regla</button>
                        <button type="button" class="btn-secondary" onclick="closeDiscountModal()">Cancelar</button>
                        <button type="button" id="deleteDiscountBtn" class="btn-danger" onclick="deleteDiscountRule()" style="display: none;">Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    // Inicializar eventos
    initDiscountEvents();
    
    // Cargar reglas existentes
    loadDiscountRules();
}

function initDiscountEvents() {
    // Formulario de regla de descuento
    document.getElementById('discountForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveDiscountRule();
    });
}

function loadDiscountRules() {
    const formData = new FormData();
    formData.append('action', 'get_discount_rules');
    formData.append('nonce', reservasAjax.nonce);

    fetch(reservasAjax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderDiscountRules(data.data);
        } else {
            document.getElementById('discounts-list').innerHTML = 
                '<p class="error">Error cargando las reglas: ' + data.data + '</p>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('discounts-list').innerHTML = 
            '<p class="error">Error de conexión</p>';
    });
}

function renderDiscountRules(rules) {
    let html = '';
    
    if (rules.length === 0) {
        html = `
            <div class="no-rules">
                <p>No hay reglas de descuento configuradas.</p>
                <button class="btn-primary" onclick="showAddDiscountModal()">Crear Primera Regla</button>
            </div>
        `;
    } else {
        html = `
            <div class="rules-table">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Mínimo Personas</th>
                            <th>Descuento</th>
                            <th>Aplicar a</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        rules.forEach(rule => {
            const statusClass = rule.is_active == 1 ? 'status-active' : 'status-inactive';
            const statusText = rule.is_active == 1 ? 'Activa' : 'Inactiva';
            const applyToText = getApplyToText(rule.apply_to);
            
            html += `
                <tr>
                    <td>${rule.rule_name}</td>
                    <td>${rule.minimum_persons} personas</td>
                    <td>${rule.discount_percentage}%</td>
                    <td>${applyToText}</td>
                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                    <td>
                        <button class="btn-edit" onclick="editDiscountRule(${rule.id})">Editar</button>
                        <button class="btn-delete" onclick="confirmDeleteRule(${rule.id})">Eliminar</button>
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
    }
    
    document.getElementById('discounts-list').innerHTML = html;
}

function getApplyToText(applyTo) {
    const texts = {
        'total': 'Total de la reserva',
        'adults_only': 'Solo adultos',
        'all_paid': 'Personas que pagan'
    };
    return texts[applyTo] || applyTo;
}

function showAddDiscountModal() {
    document.getElementById('discountModalTitle').textContent = 'Añadir Regla de Descuento';
    document.getElementById('discountForm').reset();
    document.getElementById('discountId').value = '';
    document.getElementById('deleteDiscountBtn').style.display = 'none';
    document.getElementById('isActive').checked = true;
    
    // Valores por defecto
    document.getElementById('minimumPersons').value = 10;
    document.getElementById('discountPercentage').value = 15;
    document.getElementById('applyTo').value = 'total';
    
    document.getElementById('discountModal').style.display = 'block';
}

function editDiscountRule(ruleId) {
    const formData = new FormData();
    formData.append('action', 'get_discount_rule_details');
    formData.append('rule_id', ruleId);
    formData.append('nonce', reservasAjax.nonce);

    fetch(reservasAjax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const rule = data.data;
            document.getElementById('discountModalTitle').textContent = 'Editar Regla de Descuento';
            document.getElementById('discountId').value = rule.id;
            document.getElementById('ruleName').value = rule.rule_name;
            document.getElementById('minimumPersons').value = rule.minimum_persons;
            document.getElementById('discountPercentage').value = rule.discount_percentage;
            document.getElementById('applyTo').value = rule.apply_to;
            document.getElementById('ruleDescription').value = rule.rule_description || '';
            document.getElementById('isActive').checked = rule.is_active == 1;
            document.getElementById('deleteDiscountBtn').style.display = 'block';

            document.getElementById('discountModal').style.display = 'block';
        } else {
            alert('Error al cargar la regla: ' + data.data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}

function saveDiscountRule() {
    const formData = new FormData(document.getElementById('discountForm'));
    formData.append('action', 'save_discount_rule');
    formData.append('nonce', reservasAjax.nonce);

    fetch(reservasAjax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Regla guardada correctamente');
            closeDiscountModal();
            loadDiscountRules();
        } else {
            alert('Error: ' + data.data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}

function confirmDeleteRule(ruleId) {
    if (confirm('¿Estás seguro de que quieres eliminar esta regla de descuento?')) {
        deleteDiscountRule(ruleId);
    }
}

function deleteDiscountRule(ruleId = null) {
    const id = ruleId || document.getElementById('discountId').value;
    
    const formData = new FormData();
    formData.append('action', 'delete_discount_rule');
    formData.append('rule_id', id);
    formData.append('nonce', reservasAjax.nonce);

    fetch(reservasAjax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Regla eliminada correctamente');
            closeDiscountModal();
            loadDiscountRules();
        } else {
            alert('Error: ' + data.data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}

function closeDiscountModal() {
    document.getElementById('discountModal').style.display = 'none';
}