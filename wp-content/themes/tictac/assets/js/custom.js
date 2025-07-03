jQuery(document).ready(function () {


  jQuery(document).ready(function () {
    function initSplide() {
      if (jQuery(".slider_full").length > 0) {
        var splideHome = new Splide('.slider_full', {
          type: 'loop',
          arrows: true,  // Asegúrate de que las flechas estén activadas
          pagination: false,
          lazyLoad: "nearby",
          autoplay: true,
          preloadPages: 1
        });
        splideHome.mount();
      }
    }

    // Inicializa el slider en la carga de la página
    initSplide();

    // Vuelve a inicializar el slider después de que WooCommerce actualiza los productos vía AJAX
    jQuery(document.body).on('updated_wc_div', function () {
      initSplide();
    });
  });


  if (jQuery(".slider_social").length > 0) {
    var splide_social = new Splide('.slider_social', {
      type: 'loop',
      arrows: false,
      pagination: false,
      perPage: 3,
      perMove: 3,
      height: "650px",
      autoplay: true,
      grid: {
        // You can define rows/cols instead of dimensions.
        dimensions: [[1, 1], [2, 1], [1, 1], [1, 1], [2, 1], [1, 1]],
        gap: {
          row: '6px',
          col: '6px',
        },
      },
      breakpoints: {
        640: {
          height: "250px",
          grid: {
            dimensions: [[1, 1], [2, 1], [1, 1], [1, 1], [2, 1], [1, 1]],
          },
        },
      },
    });
    splide_social.mount(window.splide.Extensions);
  }

  if (jQuery(".logotipos").length > 0) {
    var splideLogotipos = new Splide('.logotipos', {
      arrows: true,
      pagination: false,
      type: 'loop',
      perPage: 5,
      perMove: 1,
      breakpoints: {
        768: {
          perPage: 2,
        }
      }
    });
    splideLogotipos.mount();
  }

  if (jQuery(".slider_opiniones").length > 0) {
    var splideHome = new Splide('.slider_opiniones', {
      type: 'loop',
      arrows: false,
      pagination: true,
      autoplay: true,
      classes: {
        pagination: 'splide__pagination custom_pagination', // container
        page: 'splide__pagination__page custom_paginate', // each button
      },
    });
    splideHome.mount();
  }

  if (jQuery(".bloque_seo").length > 0) {
    jQuery(".bloque_seo .open").on("click", function () {
      jQuery(this).parent().find(".seoclosed").toggleClass("closed");
      jQuery(this).toggleClass("change");
    });
    jQuery(window).scroll(function () {
      if (jQuery(this).scrollTop() > 50) {
        if (!jQuery(".bloque_seo").hasClass("toggled")) {
          jQuery(".bloque_seo").addClass("toggled");
          jQuery(".bloque_seo .seoclosed").toggleClass("closed");
        }
      }
    });
  }

  if (jQuery(".filaTextoAmpliable").length > 0) {
    jQuery(".filaTextoAmpliable .titulo").on("click", function () {
      jQuery(this).toggleClass("rotar");
      jQuery(this).parent().find(".textoCerrado").slideToggle();
    });
  }

  if (jQuery(".filaAcordeon").length > 0) {
    jQuery(".filaAcordeon .titulo").on("click", function () {
      jQuery(this).parent().toggleClass("openFAQ");
      jQuery(this).parent().find(".contenido").slideToggle();
    });
  }

  if (jQuery(".menu-movil .menu-item-has-children").length > 0) {
    var openSubMenu = "<span class='openSubMenu'>+</span>";
    jQuery(".menu-movil .menu-item-has-children").append(openSubMenu);
    jQuery(".openSubMenu").on("click", function () {
      jQuery(this).siblings(".sub-menu").slideToggle();
    });
  }
  if (jQuery(".menuOpen").length > 0) {
    jQuery(".menuOpen").on("click", function () {
      jQuery(".menuOpen").toggleClass("opened");
      jQuery("#menu.menu-mobile").toggleClass("opened");
    });
  }

  const popup = jQuery('#popup');
  const imagenPopup = jQuery('#imagen-popup');

  if (popup.length) {
    jQuery('.foto img').click(function () {
      popup.addClass('mostrar');
      imagenPopup.attr('src', jQuery(this).attr('src'));
    });

    popup.click(function () {
      popup.removeClass('mostrar');
    });
  }

  if (jQuery("#portfolio").length > 0) {
    // Inicializar Isotope

    var $grid = jQuery('.grid_portfolio').isotope({
      itemSelector: '.grid-item',
      layoutMode: 'fitRows'
    });

    // Agregar los botones de filtro
    jQuery('.filter-button').on('click', function () {
      var filterValue = jQuery(this).attr('data-filter');
      $grid.isotope({ filter: filterValue });
      jQuery('.filter-button').removeClass('active');
      jQuery(this).addClass('active');
    });

    // Inicializar Fancybox
    Fancybox.bind("[data-fancybox]");

  }




});



jQuery(document).ready(function ($) {

  var diaDisponible = true;

  function verificarDisponibilidad() {
    if (!selectedDay || !selectedService) return;

    var fecha = selectedDay.year + '-' +
      (selectedDay.month + 1).toString().padStart(2, '0') + '-' +
      selectedDay.day.toString().padStart(2, '0');

    // Realizar consulta AJAX
    $.ajax({
      url: ajax_object.ajax_url,
      type: 'POST',
      data: {
        action: 'verificar_disponibilidad',
        servicio_id: selectedService,
        fecha: fecha
      },
      success: function (response) {
        if (response.success) {
          diaDisponible = response.data.disponible;
          var mensaje = response.data.mensaje || '';

          // Actualizar interfaz
          var spanDisponibilidad = $("#reserva-disponibilidad");
          var selectHora = $("#reserva-hora");

          if (diaDisponible) {
            // Available - Normal styles
            spanDisponibilidad.html('<i class="disponibilidad-icono">✓</i> AVAILABLE');
            spanDisponibilidad.removeClass('reserva-no-disponible').addClass('reserva-disponible');
            selectHora.css({
              'border-color': '',
              'background-color': ''
            });
            $("#reserva-next").removeClass('boton-deshabilitado');
          } else {
            // Not available - Alert styles
            var mensajeNoDisponible = 'NOT AVAILABLE';

            // If there's a specific message, show it as tooltip
            if (mensaje.includes('Completo')) {
              spanDisponibilidad.attr('title', 'Service is fully booked for this date');
            } else if (mensaje.includes('bloqueado')) {
              spanDisponibilidad.attr('title', 'This date is not available for bookings');
            }

            spanDisponibilidad.html('<i class="disponibilidad-icono">✗</i> ' + mensajeNoDisponible);
            spanDisponibilidad.removeClass('reserva-disponible').addClass('reserva-no-disponible');
            selectHora.css({
              'border-color': '#F44336',
              'background-color': 'rgba(244, 67, 54, 0.05)'
            });
            $("#reserva-next").addClass('boton-deshabilitado');
          }
        }
      },
      error: function () {
        console.error("Error al verificar disponibilidad");
      }
    });
  }
  // Variables globales
  var currentStep = 1;
  var selectedDay = null;
  var selectedService = null;
  var currentMonth = new Date().getMonth();
  var currentYear = new Date().getFullYear();
  var servicios = [];

  function inicializarDatosPrellenados() {
    // Verificar si hay datos pre-llenados desde URL
    if (typeof prefill_servicio_id !== 'undefined' && prefill_servicio_id) {
      console.log("Pre-llenando formulario con datos:", {
        servicio: prefill_servicio_id,
        fecha: prefill_fecha,
        hora: prefill_hora,
        adultos: prefill_adultos,
        ninos: prefill_ninos
      });

      // Pre-seleccionar servicio
      selectedService = prefill_servicio_id;
    }
  }

  // Objeto para almacenar la información del servicio seleccionado
  var servicioActual = {
    id: 0,
    nombre: "",
    horario_inicio: "",
    horario_fin: "",
    capacidad_min: 1,
    capacidad_max: 4
  };

  function cargarServicios() {
    $(".service-item").each(function () {
      var id = $(this).data("id");
      var nombreCompleto = $(this).text().trim();
      var partes = nombreCompleto.split(" - ");

      servicios.push({
        id: id,
        categoria: partes[0],
        nombre: partes[1] || "",
        horario_inicio: $(this).data("inicio") || "10:00",
        horario_fin: $(this).data("fin") || "00:00",
        capacidad_minima: $(this).data("min") || 1,
        capacidad_maxima: $(this).data("max") || 4,
        max_reservas_dia: $(this).data("max-reservas") || 10
      });
    });

    // Establecer servicio por defecto (el primero)
    if (servicios.length > 0) {
      selectedService = servicios[0].id;
      servicioActual = {
        id: servicios[0].id,
        nombre: servicios[0].categoria + " - " + servicios[0].nombre,
        horario_inicio: servicios[0].horario_inicio.substring(0, 5),
        horario_fin: servicios[0].horario_fin.substring(0, 5),
        capacidad_min: parseInt(servicios[0].capacidad_minima),
        capacidad_max: parseInt(servicios[0].capacidad_maxima)
      };
    }
  }

  // Inicialización
  cargarServicios();

  // Si hay una fecha pre-llenada, establecerla desde el principio
  if (typeof prefill_fecha !== 'undefined' && prefill_fecha) {
    var partes = prefill_fecha.split("-");
    var fecha_obj = new Date(
      parseInt(partes[0]),
      parseInt(partes[1]) - 1,
      parseInt(partes[2])
    );

    selectedDay = {
      day: fecha_obj.getDate(),
      month: fecha_obj.getMonth(),
      year: fecha_obj.getFullYear(),
      formatted: prefill_fecha // Usa el formato original
    };

    // Actualizar el calendario con el mes y año de la fecha pre-llenada
    currentMonth = fecha_obj.getMonth();
    currentYear = fecha_obj.getFullYear();

    console.log("Fecha pre-llenada establecida:", selectedDay);
  }

  // Continuar con inicialización normal
  generarDiasProximos();
  generarHorasDisponibles();
  actualizarCalendario(currentMonth, currentYear);

  // Pre-llenar servicio
  if (typeof prefill_servicio_id !== 'undefined' && prefill_servicio_id) {
    // Buscar y seleccionar el servicio
    $(".service-item").each(function () {
      if ($(this).data("id") == prefill_servicio_id) {
        var serviceName = $(this).text();
        selectedService = prefill_servicio_id;
        $(".reserva-service span:first").text(serviceName);

        // Actualizar servicioActual
        for (var i = 0; i < servicios.length; i++) {
          if (servicios[i].id == prefill_servicio_id) {
            servicioActual = {
              id: parseInt(prefill_servicio_id),
              nombre: servicios[i].categoria + " - " + servicios[i].nombre,
              horario_inicio: servicios[i].horario_inicio.substring(0, 5),
              horario_fin: servicios[i].horario_fin.substring(0, 5),
              capacidad_min: parseInt(servicios[i].capacidad_minima),
              capacidad_max: parseInt(servicios[i].capacidad_maxima)
            };

            // Regenerar horas con el servicio correcto
            generarHorasDisponibles();
            break;
          }
        }
        return false; // Salir del bucle
      }
    });
  }

  // Pre-llenar hora
  if (typeof prefill_hora !== 'undefined' && prefill_hora) {
    setTimeout(function () {
      $("#reserva-hora").val(prefill_hora);
    }, 300);
  }

  // Verificar disponibilidad al final
  verificarDisponibilidad();

  // Toggle del dropdown de servicios
  $(".reserva-service").on("click", function () {
    $(".reserva-services-dropdown").toggle();
  });

  // Selección de servicio
  $(document).on("click", ".service-item", function () {
    var serviceId = $(this).data("id");
    var serviceName = $(this).text();
    var serviceImage = $(this).data("imagen");

    // Actualizar servicio seleccionado
    selectedService = serviceId;
    $(".reserva-service span:first").text(serviceName);
    $(".reserva-services-dropdown").hide();

    // Buscar info del servicio
    for (var i = 0; i < servicios.length; i++) {
      if (servicios[i].id == serviceId) {
        servicioActual = {
          id: servicios[i].id,
          nombre: servicios[i].categoria + " - " + servicios[i].nombre,
          horario_inicio: servicios[i].horario_inicio.substring(0, 5),
          horario_fin: servicios[i].horario_fin.substring(0, 5),
          capacidad_min: parseInt(servicios[i].capacidad_minima),
          capacidad_max: parseInt(servicios[i].capacidad_maxima)
        };
        break;
      }
    }

    $("#reserva-imagen-servicio").css("opacity", 0);
    setTimeout(function () {
      $("#reserva-imagen-servicio").attr("src", serviceImage).css("opacity", 1);
    }, 300);

    // Actualizar horas disponibles
    generarHorasDisponibles();

    // Actualizar capacidad
    actualizarSelectorCapacidad();

    // Verificar disponibilidad después de cambiar el servicio
    verificarDisponibilidad();
  });

  // Cerrar dropdown al hacer clic fuera
  $(document).on("click", function (e) {
    if (!$(e.target).closest(".reserva-service, .reserva-services-dropdown").length) {
      $(".reserva-services-dropdown").hide();
    }
  });

  // Manejadores de eventos para navegación
  $("#reserva-next").on("click", function () {
    if (currentStep === 1) {
      // Validar el primer paso
      var nombre = $("#reserva-nombre").val();
      var telefono = $("#reserva-telefono").val();
      var email = $("#reserva-email").val();

      if (!nombre || !telefono || !email) {
        alert("Por favor, completa todos los campos obligatorios");
        return;
      }

      // Si todo está correcto, avanzar al paso 2
      currentStep = 2;
      $("#reserva-step-1").hide();
      $("#reserva-step-2").show();
      $("#reserva-back").show();

      // Actualizar calendario
      actualizarCalendario(currentMonth, currentYear);

      // Verificar disponibilidad al cargar paso 2
      verificarDisponibilidad();
    } else if (currentStep === 2) {
      // Validar paso 2
      if (!selectedDay) {
        alert("Por favor, selecciona una fecha");
        return;
      }

      // Verificar si el día está disponible
      if (!diaDisponible) {
        return; // El botón debería estar deshabilitado, pero por si acaso
      }

      // Si todo está correcto, avanzar al paso 3
      currentStep = 3;
      $("#reserva-step-2").hide();
      $("#reserva-step-3").show();
      $("#reserva-next").hide();
      $("#reserva-submit").show();

      // Actualizar datos de confirmación
      actualizarConfirmacion();
    }
  });

  $("#reserva-back").on("click", function () {
    if (currentStep === 2) {
      currentStep = 1;
      $("#reserva-step-2").hide();
      $("#reserva-step-1").show();
      $(this).hide();

      // Mostrar el botón "Completar Reserva" de nuevo
      $("#reserva-next").show();
    } else if (currentStep === 3) {
      currentStep = 2;
      $("#reserva-step-3").hide();
      $("#reserva-step-2").show();
      $("#reserva-next").show();
      $("#reserva-submit").hide();
    }
  });

// REEMPLAZA la sección del evento $("#reserva-submit").on("click", function () {
// en tu custom.js con este código corregido:

$("#reserva-submit").on("click", function () {
  var reservaData = {
    action: 'procesar_reserva',
    servicio_id: selectedService,
    fecha: selectedDay.year + '-' + (selectedDay.month + 1).toString().padStart(2, '0') + '-' + selectedDay.day.toString().padStart(2, '0'),
    hora: $("#reserva-hora").val(),
    nombre: $("#reserva-nombre").val(),
    telefono: $("#reserva-telefono").val(),
    email: $("#reserva-email").val(),
    adultos: $("#reserva-adultos").val(),
    ninos: $("#reserva-ninos").val(),
    comentarios: $("#reserva-comentarios").val() || ''
  };

  // Depuración: mostrar datos que se envían
  console.log("Enviando datos:", reservaData);
  console.log("Ajax URL:", ajax_object.ajax_url);

  $.ajax({
    url: ajax_object.ajax_url,
    type: 'POST',
    data: reservaData,
    beforeSend: function () {
      $("#reserva-submit").prop('disabled', true).text('Processing...');
    },
    success: function (response) {
      if (response.success) {
        // Mostrar pantalla de confirmación
        $(".reserva-steps").hide();
        $(".reserva-confirmation").show();
        $("#reserva-back").hide();
        $("#reserva-submit").hide();
        $("#reserva-another").show();

        // Enviar datos al formulario CF7 para notificación por email
        if (window.enviarConfirmacionCF7) {
          window.enviarConfirmacionCF7({
            nombre: reservaData.nombre,
            email: reservaData.email,
            telefono: reservaData.telefono,
            servicio: $("#confirm-servicio").text(),
            fecha: selectedDay.formatted,
            hora: reservaData.hora,
            adultos: reservaData.adultos,
            ninos: reservaData.ninos
          });
        }

        // FUNCIÓN CORREGIDA: Redirigir a WhatsApp después de 2 segundos
        setTimeout(function() {
          redirigirAWhatsAppRestaurante(reservaData);
        }, 2000);

      } else {
        alert(response.data.message || 'Ha ocurrido un error al procesar la reserva.');
      }
    },
    error: function (xhr, status, error) {
      alert('Ha ocurrido un error al procesar la reserva. Por favor, inténtalo de nuevo.');
    },
    complete: function () {
      $("#reserva-submit").prop('disabled', false).text('COMPLETE BOOKING');
    }
  });
});

// FUNCIÓN CORREGIDA PARA WHATSAPP - Reemplaza la función existente
function redirigirAWhatsAppRestaurante(reservaData) {
  // Formatear la fecha en formato más legible
  var fechaFormateada = selectedDay.day + '/' + (selectedDay.month + 1) + '/' + selectedDay.year;
  
  // Formatear la hora en formato 12 horas (AM/PM)
  var hora24 = reservaData.hora;
  var partesHora = hora24.split(':');
  var horas = parseInt(partesHora[0]);
  var minutos = partesHora[1];
  var ampm = horas >= 12 ? 'PM' : 'AM';
  horas = horas % 12;
  horas = horas ? horas : 12; // 0 debe ser 12
  var hora12 = horas + ':' + minutos + ' ' + ampm;
  
  // Obtener el nombre del servicio desde la confirmación
  var nombreServicio = $("#confirm-servicio").text() || servicioActual.nombre;
  
  // Construir el mensaje de WhatsApp
  var mensaje = "I want to make a reservation.\n\n";
  mensaje += "*Name:* " + reservaData.nombre + "\n";
  mensaje += "*Service:* " + nombreServicio + "\n";
  mensaje += "*Date:* " + fechaFormateada + "\n";
  mensaje += "*Adults:* " + reservaData.adultos + "\n";
  mensaje += "*Children:* " + reservaData.ninos + "\n";
  mensaje += "*Time:* " + hora12 + "\n";
  mensaje += "*Phone:* " + reservaData.telefono + "\n";
  mensaje += "*Email:* " + reservaData.email;
  
  // Añadir comentarios si existen
  if (reservaData.comentarios && reservaData.comentarios.trim() !== '') {
    mensaje += "\n*Message:* " + reservaData.comentarios;
  }
  
  // Codificar el mensaje para URL
  var mensajeCodificado = encodeURIComponent(mensaje);
  
  // Número de WhatsApp (formato internacional sin + ni espacios)
  var numeroWhatsApp = "34650413632";
  
  // Construir URL de WhatsApp
  var urlWhatsApp = "https://wa.me/" + numeroWhatsApp + "?text=" + mensajeCodificado;
  
  // Debug para verificar que se está ejecutando
  console.log("Redirigiendo a WhatsApp...");
  console.log("URL:", urlWhatsApp);
  console.log("Mensaje:", mensaje);
  
  // Abrir WhatsApp en una nueva ventana/pestaña
  window.open(urlWhatsApp, '_blank');
}

  $("#reserva-another").on("click", function () {
    resetForm();
  });

  // Selección de día en el calendario
  $(document).on("click", ".calendario-day:not(.disabled)", function () {
    $(".calendario-day").removeClass("active");
    $(this).addClass("active");

    var day = parseInt($(this).text());
    if (isNaN(day)) return; // Evitar días vacíos

    selectedDay = {
      day: day,
      month: currentMonth,
      year: currentYear,
      formatted: formatDate(new Date(currentYear, currentMonth, day))
    };

    // Actualizar también en el primer paso
    actualizarDiaSeleccionado();

    // Verificar disponibilidad después de seleccionar fecha
    verificarDisponibilidad();
  });

  // Selección de día en la primera vista
  $(document).on("click", ".reserva-day:not(.disabled)", function () {
    if ($(this).data("day") === 'add') {
      // Si es el botón +, ir al calendario
      currentStep = 2;
      $("#reserva-step-1").hide();
      $("#reserva-step-2").show();
      $("#reserva-back").show();

      // Ocultar el botón "Completar Reserva" cuando se muestra el calendario completo
      $("#reserva-next").hide();

      actualizarCalendario(currentMonth, currentYear);
      return;
    }

    $(".reserva-day").removeClass("active");
    $(this).addClass("active");

    var fecha = $(this).data("date").split("-");
    selectedDay = {
      day: parseInt(fecha[2]),
      month: parseInt(fecha[1]) - 1,
      year: parseInt(fecha[0]),
      formatted: fecha[2] + "/" + fecha[1] + "/" + fecha[0]
    };

    // Verificar disponibilidad después de seleccionar fecha
    verificarDisponibilidad();
  });

  // Selección de día en la primera vista
  $(document).on("click", ".reserva-day:not(.disabled)", function () {
    if ($(this).data("day") === 'add') {
      // Si es el botón +, ir al calendario
      currentStep = 2;
      $("#reserva-step-1").hide();
      $("#reserva-step-2").show();
      $("#reserva-back").show();
      actualizarCalendario(currentMonth, currentYear);
      return;
    }

    $(".reserva-day").removeClass("active");
    $(this).addClass("active");

    var fecha = $(this).data("date").split("-");
    selectedDay = {
      day: parseInt(fecha[2]),
      month: parseInt(fecha[1]) - 1,
      year: parseInt(fecha[0]),
      formatted: fecha[2] + "/" + fecha[1] + "/" + fecha[0]
    };

    // Verificar disponibilidad después de seleccionar fecha
    verificarDisponibilidad();
  });

  // Navegación del calendario
  $("#prev-month").on("click", function () {
    currentMonth--;
    if (currentMonth < 0) {
      currentMonth = 11;
      currentYear--;
    }
    actualizarCalendario(currentMonth, currentYear);
  });

  $("#next-month").on("click", function () {
    currentMonth++;
    if (currentMonth > 11) {
      currentMonth = 0;
      currentYear++;
    }
    actualizarCalendario(currentMonth, currentYear);
  });

  function generarDiasProximos() {
    var container = $(".reserva-days");
    container.empty();

    var today = new Date();
    var dayNames = ["<span class='notranslate'>SUN</span>", "<span class='notranslate'>MON</span>", "<span class='notranslate'>TUE</span>", "<span class='notranslate'>WED</span>", "<span class='notranslate'>THU</span>", "<span class='notranslate'>FRI</span>", "<span class='notranslate'>SAT</span>"];
    var monthNames = ["JANUARY", "FEBRUARY", "MARCH", "APRIL", "MAY", "JUNE", "JULY", "AUGUST", "SEPTEMBER", "OCTOBER", "NOVEMBER", "DECEMBER"];


    // Calcular fecha mínima para reservar (mañana)
    var minBookingDate = new Date();
    minBookingDate.setDate(today.getDate() + 1);

    // Formato de fecha para comparación
    function formatFechaComparacion(date) {
      return date.getFullYear() + "-" +
        String(date.getMonth() + 1).padStart(2, "0") + "-" +
        String(date.getDate()).padStart(2, "0");
    }

    // Verificar si ya tenemos una fecha seleccionada de antemano (prefill)
    var tieneFechaPrellenada = selectedDay !== null;
    var fechaPrellenadaStr = tieneFechaPrellenada ?
      selectedDay.year + "-" +
      String(selectedDay.month + 1).padStart(2, "0") + "-" +
      String(selectedDay.day).padStart(2, "0") : "";

    console.log("Fecha prellenada:", fechaPrellenadaStr);

    // Generar próximos 7 días, empezando desde hoy
    for (var i = 0; i < 7; i++) {
      var date = new Date();
      date.setDate(today.getDate() + i);

      var dateStr = formatDate(date);
      var fechaComparacion = formatFechaComparacion(date);

      console.log("Comparando:", fechaComparacion, "con prellenada:", fechaPrellenadaStr);

      // Determinar si el día debe estar deshabilitado (hoy)
      var isDisabled = date.getTime() < minBookingDate.getTime();

      var dayElement = $("<div class='reserva-day" + (isDisabled ? " disabled" : "") + "' data-date='" + dateStr + "'></div>");

      // Determinar si este día debe estar activo
      var shouldBeActive = false;

      if (!isDisabled) {
        if (tieneFechaPrellenada && fechaComparacion === fechaPrellenadaStr) {
          // Este es el día pre-llenado
          shouldBeActive = true;
          console.log("Activando día pre-llenado:", dateStr);
        } else if (!tieneFechaPrellenada && i === 1) {
          // No hay día pre-llenado, activar mañana por defecto
          shouldBeActive = true;

          // Si no hay fecha pre-llenada, establecer selectedDay ahora
          selectedDay = {
            day: date.getDate(),
            month: date.getMonth(),
            year: date.getFullYear(),
            formatted: dateStr
          };
          console.log("Activando día por defecto (mañana):", dateStr);
        }
      }

      if (shouldBeActive) {
        dayElement.addClass("active");
      }

      dayElement.append("<span class='reserva-day-label'>" + dayNames[date.getDay()] + "</span>");
      dayElement.append("<span class='reserva-day-number'>" + date.getDate() + "</span>");
      dayElement.append("<span class='reserva-day-label labelmes'>" + monthNames[date.getMonth()] + "</span>");

      container.append(dayElement);
    }

    // Añadir botón +
    var plusBtn = $("<div class='reserva-day' data-day='add' style='display: flex; align-items: center; justify-content: center;background-color:#92D0D0; color: white;'><span style='font-size: 41px; line-height: 40px; color: black; font-weight: bold;'>+</span></div>");
    container.append(plusBtn);
  }
  function generarHorasDisponibles() {
    var horasSelect = $("#reserva-hora");
    horasSelect.empty();

    // Convertir horarios a minutos para facilitar cálculos
    var inicio = servicioActual.horario_inicio.split(":");
    var fin = servicioActual.horario_fin.split(":");

    var inicioMinutos = parseInt(inicio[0]) * 60 + parseInt(inicio[1]);
    var finMinutos = parseInt(fin[0]) * 60 + parseInt(fin[1]);

    // Si el fin es menor que el inicio, asumimos que pasa a día siguiente
    if (finMinutos < inicioMinutos) {
      finMinutos += 24 * 60;
    }

    // Intervalo de 15 minutos
    var intervalo = 15;

    // Asegurarse de que realmente solo generamos horas dentro del rango del servicio
    var horaActual = inicioMinutos;
    while (horaActual <= finMinutos) {
      var horas = Math.floor(horaActual / 60) % 24;
      var minutos = horaActual % 60;

      var horaTexto = horas.toString().padStart(2, "0") + ":" + minutos.toString().padStart(2, "0");
      horasSelect.append("<option value='" + horaTexto + "'>" + horaTexto + "</option>");

      horaActual += intervalo;
    }

    // Si está en el DOM, establecer la primera opción como predeterminada
    if (horasSelect.children().length > 0) {
      horasSelect.val(horasSelect.children().first().val());
    }
  }

  function actualizarSelectorCapacidad() {
    var adultos = $("#reserva-adultos");
    var ninos = $("#reserva-ninos");

    adultos.empty();
    ninos.empty();

    // Generar opciones para adultos
    for (var i = servicioActual.capacidad_min; i <= servicioActual.capacidad_max; i++) {
      var optionValue = i.toString().padStart(2, "0");
      adultos.append("<option value='" + optionValue + "'>" + optionValue + "</option>");
    }

    // Generar opciones para niños (0 hasta capacidad máxima - 1)
    for (var j = 0; j <= servicioActual.capacidad_max - 1; j++) {
      var optionValueNino = j.toString().padStart(2, "0");
      ninos.append("<option value='" + optionValueNino + "'>" + optionValueNino + "</option>");
    }

    // Valores por defecto
    adultos.val("02");
    ninos.val("01");
  }

  function actualizarCalendario(mes, ano) {
    var firstDay = new Date(ano, mes, 1);
    var lastDay = new Date(ano, mes + 1, 0);

    // Ajustar para que la semana empiece en lunes
    var startingDay = firstDay.getDay();
    if (startingDay === 0) startingDay = 7; // Domingo es 7 en nuestro sistema
    startingDay--;

    var monthLength = lastDay.getDate();

    var monthNames = ["ENERO", "FEBRERO", "MARZO", "ABRIL", "MAYO", "JUNIO", "JULIO", "AGOSTO", "SEPTIEMBRE", "OCTUBRE", "NOVIEMBRE", "DICIEMBRE"];

    $(".calendario-month").text(monthNames[mes] + " " + ano);

    var calendarGrid = $(".calendario-grid");
    calendarGrid.empty();

    // Añadir días en blanco para ajustar primera semana
    for (var i = 0; i < startingDay; i++) {
      calendarGrid.append("<div class='calendario-day disabled'>-</div>");
    }

    // Obtener fecha actual y fecha mínima para reservas (24h después)
    var today = new Date();
    today.setHours(0, 0, 0, 0);

    var minBookingDate = new Date(today);
    minBookingDate.setDate(today.getDate() + 1); // Mínimo 24h de antelación

    // Añadir días del mes
    for (var day = 1; day <= monthLength; day++) {
      var date = new Date(ano, mes, day);
      date.setHours(0, 0, 0, 0);

      // Un día está deshabilitado si es anterior a la fecha mínima para reservar
      var isDisabled = date < minBookingDate;

      var dayElement = $("<div class='calendario-day" + (isDisabled ? " disabled" : "") + "'>" + day + "</div>");

      // Marcar día seleccionado SOLO si coincide exactamente con selectedDay
      if (selectedDay && selectedDay.day === day && selectedDay.month === mes && selectedDay.year === ano) {
        // Solo marcar como activo si no está deshabilitado
        if (!isDisabled) {
          dayElement.addClass("active");
          console.log("Marcando como activo en calendario:", day, mes, ano);
        }
      }

      calendarGrid.append(dayElement);
    }

    // Completar la última semana
    var remainingCells = 7 - ((startingDay + monthLength) % 7);
    if (remainingCells < 7) {
      for (var j = 0; j < remainingCells; j++) {
        calendarGrid.append("<div class='calendario-day disabled'>-</div>");
      }
    }
  }

  function actualizarDiaSeleccionado() {
    if (!selectedDay) return;

    // Formato YYYY-MM-DD para data-attribute
    var dateStr = selectedDay.year + "-" +
      (selectedDay.month + 1).toString().padStart(2, "0") + "-" +
      selectedDay.day.toString().padStart(2, "0");

    $(".reserva-day").removeClass("active");
    $(".reserva-day[data-date='" + dateStr + "']").addClass("active");
  }

  function actualizarConfirmacion() {
    $("#confirm-servicio").text(servicioActual.nombre);
    $("#confirm-fecha").text(selectedDay ? selectedDay.formatted : "");
    $("#confirm-hora").text($("#reserva-hora").val());
    $("#confirm-adultos").text($("#reserva-adultos").val());
    $("#confirm-ninos").text($("#reserva-ninos").val());
    $("#confirm-nombre").text($("#reserva-nombre").val());
    $("#confirm-telefono").text($("#reserva-telefono").val());
    $("#confirm-email").text($("#reserva-email").val());
  }

  function resetForm() {
    // Reiniciar formulario
    $("#reserva-nombre, #reserva-telefono, #reserva-email").val("");

    // Volver al paso 1
    currentStep = 1;
    $(".reserva-confirmation").hide();
    $("#reserva-another").hide();
    $(".reserva-steps").show();
    $("#reserva-step-1").show();
    $("#reserva-step-2, #reserva-step-3").hide();
    $("#reserva-back").hide();
    $("#reserva-next").show();
    $("#reserva-submit").hide();

    // Reiniciar día seleccionado
    var today = new Date();
    selectedDay = {
      day: today.getDate(),
      month: today.getMonth(),
      year: today.getFullYear(),
      formatted: formatDate(today)
    };

    // Regenerar días próximos
    generarDiasProximos();
  }

  function formatDate(date) {
    var day = date.getDate().toString().padStart(2, "0");
    var month = (date.getMonth() + 1).toString().padStart(2, "0");
    var year = date.getFullYear();

    return year + "-" + month + "-" + day;
  }
});


