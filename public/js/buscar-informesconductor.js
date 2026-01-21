/**
 * App Invoice List (jquery)
 */

'use strict';

$(function () {
  // Variable declaration for table
  var dt_invoice_table = $('.invoice-list-table');

  // Invoice datatable
  // --------------------------------------------------------------------
  if (dt_invoice_table.length) {
    // ajax: assetsPath + 'json/invoice-list.json',
    // ajax: '/requisition1/json',

    var dt_invoice = dt_invoice_table.DataTable({
      ajax: '/infconductores/json',    // JSON file to add data
      columns: [
        // columns according to JSON
        { data: 'id' },
        { data: 'id' },
        { data: 'ruta' },
        { data: 'turno' },
        { data: 'fecha' },
        { data: 'vehiculo' },
        { data: 'litros' },
        { data: 'chofer' },
        { data: 'vehiculo' },
        { data: 'vehiculo_detalle' }
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
          orderable: false,
          checkboxes: {
            selectAllRender: '<input type="checkbox" class="form-check-input">'
          },
          render: function () {
            return '<input type="checkbox" class="dt-checkboxes form-check-input">';
          },
          searchable: false
        },
        {
          // Invoice ID
          targets: 2,
          render: function (data, type, full, meta) {
            var $id = full['id'];
            // Creates full output for row
            var $row_output = '<a href="/add-informe/' + $id + '"/><span>' + $id + '</span></a>';
            return $row_output;
          }
        },
        {
          // Ruta
          targets: 3,
          render: function (data, type, full, meta) {
            var $vehiculo = full['vehiculo'],
              $fecha = full['fecha'],
              $ruta = full['ruta'];

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
              $ruta +
              '<br> <span class="fw-medium">vehiculo:</span> ' +
              $vehiculo +
              '<br> <span class="fw-medium">Due Date:</span> ' +
              $fecha +
              "</span>'>" + $ruta +
              '</div>'
            );
          }
        },
        {
          // Detalle del vehiculo
          targets: 4,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            var $name = full['vehiculo'],
              $destino = full['vehiculo_detalle'],
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
                $name = full['vehiculo'],
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
              '<small class="text-truncate">' +
              $destino +
              '</small>' +
              '</div>' +
              '</div>';
            return $row_output;
          }
        },
        {
          // Litros
          targets: 5,
          render: function (data, type, full, meta) {
            var $litros = full['litros'];
            return '<span>' + $litros + '</span>';
          }
        },
        {
          // Fecha
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
          // Kilometros
          targets: 7,
          orderable: false,
          render: function (data, type, full, meta) {
            var $tot_recor = full['tot_recor'];
            if ($tot_recor === 0) {
              var $badge_class = 'bg-label-success';
              return '<span class="badge rounded-pill ' + $badge_class + '" text-capitalized> Paid </span>';
            } else {
              return '<span class="text-heading">' + $tot_recor + '</span>';
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

            return (
              '<div class="d-flex align-items-center">' +
              '<a href="/add-informe/' + full['id'] + '" data-bs-toggle="tooltip" class="btn btn-sm btn-icon btn-text-secondary waves-effect waves-light rounded-pill ri-check-line" data-bs-placement="top" title="Seleccionar"><i class="ri-check-line-7-line ri-20px"></i></a>' +
            //   '<div class="dropdown">' +
            //   '<a href="javascript:;" class="btn btn-icon btn-sm btn-text-secondary waves-effect waves-light rounded-pill dropdown-toggle hide-arrow p-0" data-bs-toggle="dropdown"><i class="ri-more-2-line ri-20px"></i></a>' +
            //   '<div class="dropdown-menu dropdown-menu-end">' +
            //   '<a href="javascript:;" class="dropdown-item">Descargar</a>' +
            //   '<a href="/add-informe/' + full['id'] + '" class="dropdown-item">Editar</a>' +
            //   '<a href="/add-informe/add" class="dropdown-item">Duplicar</a>' +
            //   '</div>' +
            //   '</div>' +
              '</div>'
            );
          }
        }
      ],
      order: [[2, 'desc']],
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
          text: '<i class="ri-add-line ri-16px me-md-2 align-baseline"></i><span class="d-md-inline-block d-none">Crear nuevo informe</span>',
          className: 'btn btn-primary waves-effect waves-light',
          action: function (e, dt, button, config) {
            window.location = '/add-informe/add';
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

  // Delete Record
  $('.invoice-list-table tbody').on('click', '.delete-record', function () {
    // To hide tooltip on clicking delete icon
    $(this).closest($('[data-bs-toggle="tooltip"]').tooltip('hide'));
    // To delete the whole row
    dt_invoice.row($(this).parents('tr')).remove().draw();
  });

  // Filter form control to default size
  // ? setTimeout used for multilingual table initialization
  setTimeout(() => {
    $('.estado .form-select').addClass('form-select-sm');
  }, 300);
});
