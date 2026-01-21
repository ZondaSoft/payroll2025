/**
 * App Invoice List (jquery)
 */

'use strict';

$(function () {
  // Variable declaration for table
  var dt_invoice_table = $('.invoice-list-table');
  var urlNewReq = '/add-requisition1/add/';
  var urlEditReq = '/add-requisition1/';

  //console.log(dt_invoice_table.length);
  // Invoice datatable

  // --------------------------------------------------------------------
  if (dt_invoice_table.length) {
    // ajax: assetsPath + 'json/invoice-list.json',
    // ajax: '/requisition1/json',

    // Seleccionamos el elemento por ID y obtenemos el atributo data-modulo
    var modulo = document.getElementById('grilla').getAttribute('data-modulo');
    var urlToload = '/requisition1/json';

    if (modulo == 'SERVICIOS') {
        urlToload = '/requisition1/json';
        urlNewReq = '/add-requisition1/add/';
    } else if (modulo == 'TALLER') {
        urlToload = '/requisition2/json';
        urlNewReq = '/add-requisition2/add/';
        urlEditReq = '/add-requisition2/';
    } else if (modulo == 'PANOL') {
        urlToload = '/requisition3/json';
        urlNewReq = '/requisition4/add/';
        urlEditReq = '/add-requisition3/';
    } else if (modulo == 'CAO') {
        urlToload = '/requisition5/json';
        urlNewReq = '/requisition5/add/';
        urlEditReq = '/requisition5/';
    //} else if (modulo == 'PANOL') {
    //    urlToload = '/requisition4/json';
    //    urlNewReq = '/requisition4/add/';
    //    urlEditReq = '/requisition4/';
    } else if (modulo == 'PAÑOL') {
        urlToload = '/requisition4/json';
        urlNewReq = '/requisition4/add/';
        urlEditReq = '/requisition4/';
    } else if (modulo == 'IT') {
        urlToload = '/requisition6/json';
        urlNewReq = '/requisition6/add/';
        urlEditReq = '/requisition6/';
    } else if (modulo == 'RELLENO') {
        urlToload = '/requisition7/json';
        urlNewReq = '/requisition7/add/';
        urlEditReq = '/requisition7/';
    } else if (modulo == 'SEGURIDAD') {
        urlToload = '/requisition9/json';
        urlNewReq = '/requisition9/add/';
        urlEditReq = '/requisition9/';
    } else {
        urlToload = '/requisition9/json';
        urlNewReq = '/requisition9/add/';
        urlEditReq = '/requisition9/';
    }

    var dt_invoice = dt_invoice_table.DataTable({
      ajax: urlToload,    // JSON file to add data
      columns: [
        // columns according to JSON
        { data: 'id' },
        { data: 'numero' },
        { data: 'tipo' },
        { data: 'requisitor' },
        { data: 'fecha' },
        { data: 'unidad_req' },
        { data: 'total' },
        { data: 'destino' },
        { data: 'estado' },
        { data: 'estado' }
      ],
      columnDefs: [
        {
          // For Responsive
          className: 'control',
          responsivePriority: 2,
          searchable: false,
          targets: 0,
          render: function (data, type, full, meta) {
            return '';
          }
        },
        {
          // For Checkboxes
          targets: 1,
          orderable: true,
          checkboxes: {
            selectAllRender: '<input type="checkbox" class="form-check-input">'
          },
          render: function (data, type, full, meta) {
            var $id = full['id'];
            // id="ids[{{ $pedido->id }}]"
            return '<input type="checkbox" id="ids[' + $id + ']" name="ids[]" class="dt-checkboxes form-check-input">';
          },
          searchable: false
        },
        {
          // Invoice ID
          targets: 2,
          render: function (data, type, full, meta) {
            var $id = full['id'];
            var $numero = full['numero'];
            // Creates full output for row
            if (modulo == 'PANOL') {
                var $row_output = '<a href="' + urlEditReq + "generate/" + $id + '"><span># ' + $numero + '</span></a>' +
                '<a href="' + urlEditReq + "generate/" + full['id'] + '" data-bs-toggle="tooltip" class="btn btn-sm btn-icon btn-text-primary waves-effect waves-light rounded-pill"  data-bs-placement="top" title="Validar requisición y generar req.externa"><i class="ri-pencil-line ri-20px"></i></a>';
            } else {
                var $row_output = '<a href="' + urlEditReq + $id + '"><span># ' + $numero + '</span></a>';
            }
            return $row_output;
          }
        },
        {
          // Invoice status
          targets: 3,
          render: function (data, type, full, meta) {
            var $estado = full['estado'],
              $fecha = full['fecha'],
              $tipo = full['tipo'];

              //console.log(full);
            var roleBadgeObj = {
              INTERNA: '<span class="avatar avatar-sm"> <span class="avatar-initial rounded-circle bg-label-secondary"><i class="ri-save-line ri-16px"></i></span></span>',
              Sent: '<span class="avatar avatar-sm"> <span class="avatar-initial rounded-circle bg-label-secondary"><i class="ri-save-line ri-16px"></i></span></span>',
              Draft:
                '<span class="avatar avatar-sm"> <span class="avatar-initial rounded-circle bg-label-primary"><i class="ri-mail-line ri-16px"></i></span></span>',
              'Past Due':
                '<span class="avatar avatar-sm"> <span class="avatar-initial rounded-circle bg-label-danger"><i class="ri-error-warning-line ri-16px"></i></span></span>',
              'Partial Payment':
                '<span class="avatar avatar-sm"> <span class="avatar-initial rounded-circle bg-label-success"><i class="ri-check-line ri-16px"></i></span></span>',
              Paid: '<span class="avatar avatar-sm"> <span class="avatar-initial rounded-circle bg-label-warning"><i class="ri-line-chart-line ri-16px"></i></span></span>',
              Downloaded:
                '<span class="avatar avatar-sm"> <span class="avatar-initial rounded-circle bg-label-info"><i class="ri-arrow-down-line ri-16px"></i></span></span>'
            };
            return (
              "<div class='d-inline-flex' data-bs-toggle='tooltip' data-bs-html='true' title='<span>" +
              $tipo +
              '<br> <span class="fw-medium">estado:</span> ' +
              $estado +
              '<br> <span class="fw-medium">Due Date:</span> ' +
              $fecha +
              "</span>'>" + $tipo +
              '</div>'
            );
          }
        },
        {
          // Client name and Service
          targets: 4,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            var $name = full['unidad_req'],
              $requisitor = full['requisitor'],
              $image = full['avatar_image'],
              $rand_num = Math.floor(Math.random() * 11) + 1,
              $user_img = $rand_num + '.png';
            if ($image === true) {
              // For Avatar image
              var $output =
                '<img src="' + assetsPath + 'img/avatars/' + $user_img + '" alt="Avatar" class="rounded-circle">';
            } else {
              // For Avatar badge
              //alert($name);

              var stateNum = Math.floor(Math.random() * 6),
                states = ['success', 'danger', 'warning', 'info', 'dark', 'primary', 'secondary'],
                $state = states[stateNum],
                $name = full['unidad_req'],
                $initials = $name.match(/\b\w/g) || [];
              $initials = (($initials.shift() || '') + ($initials.pop() || '')).toUpperCase();
              $output = '<span class="avatar-initial rounded-circle bg-label-' + $state + '">' + $initials + '</span>';
            }
            // Creates full output for row
            var $row_output =
              '<div class="d-flex justify-content-start align-items-center">' +
              '<div class="avatar-wrapper">' +
              '<div class="avatar avatar-sm me-3">' +
              $output +
              '</div>' +
              '</div>' +
              '<div class="d-flex flex-column">' +
              '<a href="pages-profile-user.html" class="text-truncate text-heading"><p class="mb-0 fw-medium">' +
              $name +
              '</p></a>' +
              '<small class="text-truncate">Requisitor: ' +
              $requisitor +
              '</small>' +
              '</div>' +
              '</div>';
            return $row_output;
          }
        },
        {
          // Total Invoice Amount
          targets: 5,
          render: function (data, type, full, meta) {
            var $total = full['total'];
            return '<span>$' + $total + '</span>';
          }
        },
        {
          // Due Date
          targets: 6,
          render: function (data, type, full, meta) {
            var $fecha = new Date(full['fecha']);
            // Creates full output for row
            var $row_output =
              '<span class="d-none">' +
              moment($fecha).format('YYYYMMDD') +
              '</span>' +
              moment($fecha).format('DD/MM/YYYY');
            $fecha;
            return $row_output;
          }
        },
        {
          // Client estado/Status
          targets: 7,
          orderable: false,
          render: function (data, type, full, meta) {
            var $estado = full['estado'];
            if ($estado === 0) {
                var $badge_class = 'bg-label-success';
                return '<span class="badge rounded-pill ' + $badge_class + '" text-capitalized> Paid </span>';
            } else {
                //return '<span class="text-heading">1' + $estado + '</span>';
                var $badge_class = 'bg-label-success';
                if ($estado === 'ANULADO') {
                    $badge_class = 'bg-label-danger';
                } else if ($estado === 'PENDIENTE') {
                    $badge_class = 'bg-label-warning';
                } else if ($estado === 'VALIDADO') {
                    $badge_class = 'bg-label-primary';
                } else if ($estado === 'Enviado') {
                    $badge_class = 'bg-label-info';
                } else {
                    $badge_class = 'bg-label-secondary';
                }
                return '<span class="badge rounded-pill ' + $badge_class + '" text-capitalized>' + $estado + '</span>';
            }
          }
        },
        {
          targets: 8,
          visible: false
        },
        {
          // Actions
          targets: -1,
          title: 'Acciones',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            var $id = full['id'];


            //data-bs-toggle="modal" data-bs-target="#backDropModal"

            return (
              '<div class="d-flex align-items-center">' +
              '<a href="javascript:;" class="btn btn-sm btn-icon btn-text-secondary waves-effect waves-light rounded-pill delete-record" data-bs-toggle="modal" data-bs-target="#backDropModal" data-id=' + full['id'] + ' onclick="setModalId(this)" data-bs-placement="top" title="Anular requisición"><i class="ri-delete-bin-7-line ri-20px"></i></a>' +
              '<a href="' + urlEditReq + full['id'] + '" data-bs-toggle="tooltip" class="btn btn-sm btn-icon btn-text-secondary waves-effect waves-light rounded-pill"  data-bs-placement="top" title="Ver requisición"><i class="ri-eye-line ri-20px"></i></a>' +
              '<div class="dropdown">' +
              '<a href="javascript:;" class="btn btn-icon btn-sm btn-text-secondary waves-effect waves-light rounded-pill dropdown-toggle hide-arrow p-0" data-bs-toggle="dropdown"><i class="ri-more-2-line ri-20px"></i></a>' +
              '<div class="dropdown-menu dropdown-menu-end">' +
              '<a href="' + urlEditReq + 'download/' + full['id'] + '" class="dropdown-item">Descargar</a>' +
              '<a href="' + urlEditReq + 'edit/' + full['id'] + '" class="dropdown-item">Editar</a>' +
              '</div>' +
              '</div>' +
              '</div>'
            );

            // '<a href="' + urlNewReq + full['id'] + '" class="dropdown-item">Duplicar</a>' +
            // '<a href="' + "/requisiciones/email/" + full['id'] + "/true" + '" class="dropdown-item" clickz>Notifcar vía email</a>' +
          }
        }
      ],
      order: [[0, 'desc']],
      dom:
        '<"row mx-1"' +
        '<"col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-4 mt-md-0 mt-5"l<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start"B>>' +
        '<"col-12 col-md-6 d-flex align-items-center justify-content-end flex-column flex-md-row pe-3 gap-md-4"f<"estado mb-5 mb-md-0">>' +
        '>t' +
        '<"row mx-2"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      language: {
        sLengthMenu: 'Show _MENU_',
        search: '',
        searchPlaceholder: 'Buscar'
      },
      // Buttons with Dropdown
      buttons: [
        {
            text: '<i class="ri-add-line ri-16px me-md-2 align-baseline"></i><span class="d-md-inline-block d-none">Crear Nueva requisición interna</span>',
            className: 'btn btn-primary waves-effect waves-light',
            action: function (e, dt, button, config) {
                window.location = urlNewReq;
            }
        }
      ],
      // For responsive popup
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of ' + data['full_name'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== '' // ? Do not show row in modal popup if title is blank (for check box)
                ? '<tr data-dt-row="' +
                    col.rowIndex +
                    '" data-dt-column="' +
                    col.columnIndex +
                    '">' +
                    '<td>' +
                    col.title +
                    ':' +
                    '</td> ' +
                    '<td>' +
                    col.data +
                    '</td>' +
                    '</tr>'
                : '';
            }).join('');

            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
      },
      initComplete: function () {
        // Adding role filter once table initialized
        this.api()
          .columns(8)
          .every(function () {
            var column = this;
            var select = $(
              '<select id="UserRole" class="form-select"><option value=""> Estado </option></select>'
            )
              .appendTo('.estado')
              .on('change', function () {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                column.search(val ? '^' + val + '$' : '', true, false).draw();
              });

            column
              .data()
              .unique()
              .sort()
              .each(function (d, j) {
                select.append('<option value="' + d + '" class="text-capitalize">' + d + '</option>');
              });
          });
      }
    });
  }

  // On each datatable draw, initialize tooltip
  dt_invoice_table.on('draw.dt', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl, {
        boundary: document.body
      });
    });
  });

  document.addEventListener("DOMContentLoaded", async function () {
    const checkboxes = document.querySelectorAll("input[name='ids[]']");
    const form = document.getElementById("frmGenerar");
    const hiddenInput = document.getElementById("idsSeleccionados");

    // Al enviar el formulario, almacenar los IDs seleccionados
    form.addEventListener("submit", function (event) {
        event.preventDefault(); // Evitar el envío inmediato

        const selectedIds = Array.from(checkboxes)
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.value);

        if (selectedIds.length === 0) {
            alert("Por favor, selecciona al menos una solicitud.");
            return;
        }

        alert('llamo formulario');

        hiddenInput.value = JSON.stringify(selectedIds);
        this.submit(); // Ahora sí, enviar el formulario
    });
});

  // Delete Record
  //$('.invoice-list-table tbody').on('click', '.delete-record', function () {
    // To hide tooltip on clicking delete icon
    //$(this).closest($('[data-bs-toggle="tooltip"]').tooltip('hide'));
    // To delete the whole row
    //dt_invoice.row($(this).parents('tr')).remove().draw();
  //});

  // Filter form control to default size
  // ? setTimeout used for multilingual table initialization
  setTimeout(() => {
    $('.estado .form-select').addClass('form-select-sm');
  }, 300);
});
