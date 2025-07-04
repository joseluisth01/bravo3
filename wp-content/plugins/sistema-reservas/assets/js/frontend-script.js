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
        $('#adultos, #residentes, #ninos-5-12, #ninos-menores').on('change', function () {
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

        // Días del mes actual
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const today = new Date();
            const isToday = dateStr === today.toISOString().split('T')[0];
            const isPast = new Date(dateStr) < today.setHours(0, 0, 0, 0);

            let dayClass = 'calendar-day';
            let clickHandler = '';

            if (isPast) {
                dayClass += ' no-disponible';
            } else if (servicesData[dateStr] && servicesData[dateStr].length > 0) {
                dayClass += ' disponible';
                clickHandler = `onclick="selectDate('${dateStr}')"`;

                // Verificar si hay ofertas (esto se puede personalizar)
                if (day % 7 === 0) { // Ejemplo: domingos con oferta
                    dayClass += ' oferta';
                }
            } else {
                dayClass += ' no-disponible';
            }

            if (selectedDate === dateStr) {
                dayClass += ' selected';
            }

            calendarHTML += `<div class="${dayClass}" ${clickHandler}>${day}</div>`;
        }

        $('#calendar-grid').html(calendarHTML);

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
        if (!selectedServiceId) return;

        const adultos = parseInt($('#adultos').val()) || 0;
        const residentes = parseInt($('#residentes').val()) || 0;
        const ninos512 = parseInt($('#ninos-5-12').val()) || 0;
        const ninosMenores = parseInt($('#ninos-menores').val()) || 0;

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
                    $('#total-discount').text('-' + result.descuento.toFixed(2) + '€');
                    $('#total-price').text(result.total.toFixed(2) + '€');
                }
            })
            .catch(error => {
                console.error('Error calculando precio:', error);
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


// Nueva función para ir a la página de detalles
window.proceedToDetails = function() {
    // Guardar datos en sessionStorage
    const reservationData = {
        fecha: selectedDate,
        service_id: selectedServiceId,
        adultos: parseInt($('#adultos').val()) || 0,
        residentes: parseInt($('#residentes').val()) || 0,
        ninos_5_12: parseInt($('#ninos-5-12').val()) || 0,
        ninos_menores: parseInt($('#ninos-menores').val()) || 0,
        total_price: $('#total-price').text()
    };
    
    sessionStorage.setItem('reservationData', JSON.stringify(reservationData));
    
    // Construir URL correcta basada en la URL actual
    const currentUrl = window.location.href;
    const baseUrl = currentUrl.substring(0, currentUrl.indexOf('/', 8)); // Después de http://
    const pathParts = window.location.pathname.split('/').filter(part => part !== '');
    
    // Si hay un subdirectorio (como 'bravo'), incluirlo
    if (pathParts.length > 0 && pathParts[0] !== '') {
        window.location.href = baseUrl + '/' + pathParts[0] + '/detalles-reserva/';
    } else {
        window.location.href = baseUrl + '/detalles-reserva/';
    }
};

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

        $('#adultos, #residentes, #ninos-5-12, #ninos-menores').val(0);
        $('#horarios-select').html('<option value="">Selecciona primero una fecha</option>').prop('disabled', true);

        $('.calendar-day').removeClass('selected');

        calculateTotalPrice();
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

});