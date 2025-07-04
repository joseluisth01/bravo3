// Variables globales
let currentStep = 1;
let currentDate = new Date();
let selectedDate = null;
let selectedServiceId = null;
let servicesData = {};

jQuery(document).ready(function ($) {

    // Inicializar formulario de reservas
    initBookingForm();

    function initBookingForm() {
        loadCalendar();
        setupEventListeners();
        
        // Limpiar precios al inicializar
        clearPricing();
    }

    function setupEventListeners() {
        // Navegación del calendario
        $('#prev-month').on('click', function () {
            currentDate.setMonth(currentDate.getMonth() - 1);
            loadCalendar();
        });

        $('#next-month').on('click', function () {
            currentDate.setMonth(currentDate.getMonth() + 1);
            loadCalendar();
        });

        // Selección de horario
        $('#horarios-select').on('change', function () {
            selectedServiceId = $(this).val();
            if (selectedServiceId) {
                $('#btn-siguiente').prop('disabled', false);
                loadPrices();
            } else {
                $('#btn-siguiente').prop('disabled', true);
            }
        });

        // Cambios en selectores de personas
        $('#adultos, #residentes, #ninos-5-12, #ninos-menores').on('input change', function () {
    calculateTotalPrice();
    validatePersonSelection();
});

        // Navegación entre pasos
        $('#btn-siguiente').on('click', function () {
            nextStep();
        });

        $('#btn-anterior').on('click', function () {
            previousStep();
        });
    }

    function loadCalendar() {
        updateCalendarHeader();

        const formData = new FormData();
        formData.append('action', 'get_available_services');
        formData.append('month', currentDate.getMonth() + 1);
        formData.append('year', currentDate.getFullYear());
        formData.append('nonce', reservasAjax.nonce);

        fetch(reservasAjax.ajax_url, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    servicesData = data.data;
                    renderCalendar();
                } else {
                    console.error('Error cargando servicios:', data.data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }

    function updateCalendarHeader() {
        const monthNames = [
            'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO',
            'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'
        ];

        const monthYear = monthNames[currentDate.getMonth()] + ' ' + currentDate.getFullYear();
        $('#current-month-year').text(monthYear);
    }

function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        let firstDayOfWeek = firstDay.getDay();
        firstDayOfWeek = (firstDayOfWeek + 6) % 7; // Lunes = 0

        const daysInMonth = lastDay.getDate();
        const dayNames = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];

        let calendarHTML = '';

        // Encabezados de días
        dayNames.forEach(day => {
            calendarHTML += `<div class="calendar-day-header">${day}</div>`;
        });

        // Días del mes anterior
        for (let i = 0; i < firstDayOfWeek; i++) {
            const dayNum = new Date(year, month, -firstDayOfWeek + i + 1).getDate();
            calendarHTML += `<div class="calendar-day other-month">${dayNum}</div>`;
        }

        // CORRECCIÓN: Obtener fecha actual de manera más precisa
        const today = new Date();
        const todayYear = today.getFullYear();
        const todayMonth = today.getMonth();
        const todayDay = today.getDate();

        // Días del mes actual
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            
            // CORRECCIÓN: Comparación más directa y precisa
            let isPastOrToday = false;
            
            if (year < todayYear) {
                // Año anterior
                isPastOrToday = true;
            } else if (year === todayYear && month < todayMonth) {
                // Mismo año, mes anterior
                isPastOrToday = true;
            } else if (year === todayYear && month === todayMonth && day <= todayDay) {
                // Mismo año, mismo mes, día anterior o actual
                isPastOrToday = true;
            }

            let dayClass = 'calendar-day';
            let clickHandler = '';

            // Bloquear días pasados Y el día actual
            if (isPastOrToday) {
                dayClass += ' no-disponible';
                console.log(`Día ${day} bloqueado (pasado o actual)`);
            } else if (servicesData[dateStr] && servicesData[dateStr].length > 0) {
                dayClass += ' disponible';
                clickHandler = `onclick="selectDate('${dateStr}')"`;
                console.log(`Día ${day} disponible con servicios`);

                // Verificar si hay ofertas (esto se puede personalizar)
                if (day % 7 === 0) { // Ejemplo: domingos con oferta
                    dayClass += ' oferta';
                }
            } else {
                dayClass += ' no-disponible';
                console.log(`Día ${day} no disponible (sin servicios)`);
            }

            if (selectedDate === dateStr) {
                dayClass += ' selected';
            }

            calendarHTML += `<div class="${dayClass}" ${clickHandler}>${day}</div>`;
        }

        $('#calendar-grid').html(calendarHTML);

        // Debug: Mostrar información de la fecha actual
        console.log(`Fecha actual: ${todayDay}/${todayMonth + 1}/${todayYear}`);
        console.log(`Mes del calendario: ${month + 1}/${year}`);

        // Reasignar eventos de clic después de regenerar el HTML
        setupCalendarClickEvents();
    }

    function setupCalendarClickEvents() {
        $('.calendar-day.disponible').off('click').on('click', function () {
            const dayNumber = $(this).text();
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(dayNumber).padStart(2, '0')}`;

            selectDate(dateStr, $(this));
        });
    }

    function selectDate(dateStr, dayElement) {
        selectedDate = dateStr;
        selectedServiceId = null;

        // Actualizar visual del calendario
        $('.calendar-day').removeClass('selected');
        if (dayElement) {
            dayElement.addClass('selected');
        }

        // Cargar horarios disponibles
        loadAvailableSchedules(dateStr);
    }

    function loadAvailableSchedules(dateStr) {
        const services = servicesData[dateStr] || [];

        let optionsHTML = '<option value="">Selecciona un horario</option>';

        services.forEach(service => {
            optionsHTML += `<option value="${service.id}">${service.hora} - ${service.plazas_disponibles} plazas disponibles</option>`;
        });

        $('#horarios-select').html(optionsHTML).prop('disabled', false);
        $('#btn-siguiente').prop('disabled', true);
    }

    function loadPrices() {
        if (!selectedServiceId) return;

        const service = findServiceById(selectedServiceId);
        if (service) {
            $('#price-adultos').text(service.precio_adulto + '€');
            $('#price-ninos').text(service.precio_nino + '€');
            calculateTotalPrice();
        }
    }

    function findServiceById(serviceId) {
        for (let date in servicesData) {
            for (let service of servicesData[date]) {
                if (service.id == serviceId) {
                    return service;
                }
            }
        }
        return null;
    }

    function calculateTotalPrice() {
        if (!selectedServiceId) {
            // Si no hay servicio seleccionado, limpiar precios
            clearPricing();
            return;
        }

        const adultos = parseInt($('#adultos').val()) || 0;
        const residentes = parseInt($('#residentes').val()) || 0;
        const ninos512 = parseInt($('#ninos-5-12').val()) || 0;
        const ninosMenores = parseInt($('#ninos-menores').val()) || 0;

        const totalPersonas = adultos + residentes + ninos512 + ninosMenores;

        // Si no hay personas seleccionadas, limpiar precios
        if (totalPersonas === 0) {
            clearPricing();
            return;
        }

        const formData = new FormData();
        formData.append('action', 'calculate_price');
        formData.append('service_id', selectedServiceId);
        formData.append('adultos', adultos);
        formData.append('residentes', residentes);
        formData.append('ninos_5_12', ninos512);
        formData.append('ninos_menores', ninosMenores);
        formData.append('nonce', reservasAjax.nonce);

        fetch(reservasAjax.ajax_url, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const result = data.data;
                    updatePricingDisplay(result);
                } else {
                    console.error('Error calculando precio:', data);
                    clearPricing();
                }
            })
            .catch(error => {
                console.error('Error calculando precio:', error);
                clearPricing();
            });
    }

    function clearPricing() {
    $('#total-discount').text('');
    $('#total-price').text('');
    $('#discount-row').hide();
    $('#discount-message').removeClass('show');
    console.log('Precios limpiados');
}

function updatePricingDisplay(result) {
    console.log('Datos recibidos del servidor:', result);
    
    // Manejar descuentos
    if (result.descuento > 0) {
        $('#total-discount').text('-' + result.descuento.toFixed(2) + '€');
        $('#discount-row').show();
    } else {
        $('#discount-row').hide();
    }
    
    // Manejar mensaje de descuento por grupo
    if (result.regla_descuento_aplicada && result.regla_descuento_aplicada.rule_name) {
        const regla = result.regla_descuento_aplicada;
        const mensaje = `Descuento del ${regla.discount_percentage}% por ${regla.rule_name.toLowerCase()}`;
        
        $('#discount-text').text(mensaje);
        $('#discount-message').addClass('show');
        
        console.log('Descuento por grupo aplicado:', mensaje);
    } else {
        $('#discount-message').removeClass('show');
    }

    window.lastDiscountRule = result.regla_descuento_aplicada;
    
    // Actualizar precio total
    $('#total-price').text(result.total.toFixed(2) + '€');
    
    console.log('Precios actualizados:', {
        descuento: result.descuento,
        total: result.total,
        regla_aplicada: result.regla_descuento_aplicada
    });
}


    function validatePersonSelection() {
        const adultos = parseInt($('#adultos').val()) || 0;
        const residentes = parseInt($('#residentes').val()) || 0;
        const ninos512 = parseInt($('#ninos-5-12').val()) || 0;
        const ninosMenores = parseInt($('#ninos-menores').val()) || 0;

        const totalAdults = adultos + residentes;
        const totalChildren = ninos512 + ninosMenores;

        // Validar que hay al menos un adulto si hay niños
        if (totalChildren > 0 && totalAdults === 0) {
            alert('Debe haber al menos un adulto si hay niños en la reserva.');
            $('#ninos-5-12, #ninos-menores').val(0);
            calculateTotalPrice();
            return false;
        }

        return true;
    }

    function nextStep() {
        // Validar que se ha seleccionado fecha y horario
        if (!selectedDate || !selectedServiceId) {
            alert('Por favor, selecciona una fecha y horario.');
            return;
        }

        // Validar selección de personas
        const adultos = parseInt($('#adultos').val()) || 0;
        const residentes = parseInt($('#residentes').val()) || 0;
        const ninos512 = parseInt($('#ninos-5-12').val()) || 0;
        const ninosMenores = parseInt($('#ninos-menores').val()) || 0;

        const totalPersonas = adultos + residentes + ninos512 + ninosMenores;

        if (totalPersonas === 0) {
            alert('Debe seleccionar al menos una persona.');
            return;
        }

        if (!validatePersonSelection()) {
            return;
        }

        // Mostrar el paso 2 (botón de completar)
        $('#step-2').show();
        $('#btn-siguiente').hide();
    }

    function previousStep() {
        if (currentStep === 2) {
            currentStep = 1;
            $('#step-2').hide();
            $('#step-1').show();
            $('#btn-anterior').hide();
            $('#btn-siguiente').text('Siguiente →').show();

        } else if (currentStep === 3) {
            currentStep = 2;
            $('#step-3').hide();
            $('#step-2').show();
            $('#btn-siguiente').text('Siguiente →').show();
        }
    }

function resetForm() {
        currentStep = 1;
        selectedDate = null;
        selectedServiceId = null;

        $('#step-2, #step-3').hide();
        $('#step-1').show();
        $('#btn-anterior').hide();
        $('#btn-siguiente').text('Siguiente →').show().prop('disabled', true);

        $('#adultos, #residentes, #ninos-5-12, #ninos-menores').val(0).trigger('change');
        $('#horarios-select').html('<option value="">Selecciona primero una fecha</option>').prop('disabled', true);

        $('.calendar-day').removeClass('selected');

        // Limpiar precios al resetear
        clearPricing();
    }

    // Función global para el botón de completar reserva
    window.proceedToPayment = function () {
        const service = findServiceById(selectedServiceId);
        const adultos = parseInt($('#adultos').val()) || 0;
        const residentes = parseInt($('#residentes').val()) || 0;
        const ninos512 = parseInt($('#ninos-5-12').val()) || 0;
        const ninosMenores = parseInt($('#ninos-menores').val()) || 0;

        const resumen = `
            RESUMEN DE LA RESERVA:
            
            Fecha: ${selectedDate}
            Hora: ${service.hora}
            
            Adultos: ${adultos}
            Residentes: ${residentes}
            Niños (5-12 años): ${ninos512}
            Niños (-5 años): ${ninosMenores}
            
            Total: ${$('#total-price').text()}
            
            ¿Proceder con la reserva?
        `;

        if (confirm(resumen)) {
            // Aquí redirigir al sistema de pago o procesar la reserva
            alert('Función de pago en desarrollo. La reserva se procesaría aquí.');

            // Reiniciar formulario
            resetForm();
        }
    };

    // FUNCIÓN COMPLETAMENTE REESCRITA: Nueva función para ir a la página de detalles
    window.proceedToDetails = function() {
        console.log('=== INICIANDO proceedToDetails ===');
        
        // Debug: Mostrar URL actual
        console.log('URL actual:', window.location.href);
        console.log('Pathname actual:', window.location.pathname);
        
        // Validar que tenemos todos los datos necesarios
        if (!selectedDate || !selectedServiceId) {
            alert('Error: No hay fecha o servicio seleccionado');
            console.log('selectedDate:', selectedDate, 'selectedServiceId:', selectedServiceId);
            return;
        }
        
        // Obtener datos del servicio seleccionado
        const service = findServiceById(selectedServiceId);
        if (!service) {
            alert('Error: No se encontraron datos del servicio');
            console.log('Servicio no encontrado para ID:', selectedServiceId);
            return;
        }
        console.log('Servicio encontrado:', service);
        
        // Recopilar todos los datos del formulario
        const adultos = parseInt($('#adultos').val()) || 0;
        const residentes = parseInt($('#residentes').val()) || 0;
        const ninos_5_12 = parseInt($('#ninos-5-12').val()) || 0;
        const ninos_menores = parseInt($('#ninos-menores').val()) || 0;
        
        // Obtener el precio total con mejor manejo de errores
        let totalPrice = '0';
        try {
            const totalPriceElement = $('#total-price');
            if (totalPriceElement.length > 0) {
                const totalPriceText = totalPriceElement.text();
                console.log('Texto precio total:', totalPriceText);
                totalPrice = totalPriceText.replace('€', '').trim();
            } else {
                console.log('Elemento #total-price no encontrado');
            }
        } catch (error) {
            console.error('Error obteniendo precio total:', error);
        }
        
const reservationData = {
    fecha: selectedDate,
    service_id: selectedServiceId,
    hora_ida: service.hora,
    adultos: adultos,
    residentes: residentes,
    ninos_5_12: ninos_5_12,
    ninos_menores: ninos_menores,
    precio_adulto: service.precio_adulto,
    precio_nino: service.precio_nino,
    precio_residente: service.precio_residente,
    total_price: totalPrice,
    // AÑADIR ESTOS CAMPOS:
    descuento_grupo: $('#total-discount').text().includes('€') ? 
        parseFloat($('#total-discount').text().replace('€', '').replace('-', '')) : 0,
    regla_descuento_aplicada: window.lastDiscountRule || null // Variable global para guardar la última regla
};
        
        console.log('Datos de reserva preparados:', reservationData);
        
        // Guardar en sessionStorage con mejor manejo de errores
        try {
            const dataString = JSON.stringify(reservationData);
            sessionStorage.setItem('reservationData', dataString);
            console.log('Datos guardados en sessionStorage exitosamente');
            
            // Verificar que se guardó correctamente
            const savedData = sessionStorage.getItem('reservationData');
            console.log('Verificación datos guardados:', savedData);
        } catch (error) {
            console.error('Error guardando en sessionStorage:', error);
            alert('Error guardando los datos de la reserva: ' + error.message);
            return;
        }
        
        // NUEVA LÓGICA: Usar la función de WordPress para obtener URLs
        let targetUrl;
        
        // Método 1: Intentar detectar automáticamente
        const currentPath = window.location.pathname;
        console.log('Path actual:', currentPath);
        
        if (currentPath.includes('/bravo/')) {
            // Estamos en el subdirectorio bravo
            targetUrl = window.location.origin + '/bravo/detalles-reserva/';
        } else if (currentPath.includes('/')) {
            // Detectar otros subdirectorios
            const pathParts = currentPath.split('/').filter(part => part !== '');
            if (pathParts.length > 0 && pathParts[0] !== 'detalles-reserva') {
                targetUrl = window.location.origin + '/' + pathParts[0] + '/detalles-reserva/';
            } else {
                targetUrl = window.location.origin + '/detalles-reserva/';
            }
        } else {
            targetUrl = window.location.origin + '/detalles-reserva/';
        }
        
        console.log('URL destino calculada:', targetUrl);
        console.log('Redirigiendo a:', targetUrl);
        window.location.href = targetUrl;
    };

    // Hacer disponibles las funciones globalmente
    window.selectDate = selectDate;
    window.findServiceById = findServiceById;

});