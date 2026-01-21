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
      ajax: '/permissions/json',    // JSON file to add data
      columns: [
        // columns according to JSON
        { data: 'id' },
        { data: 'description' },
        { data: 'name' },
        { data: 'description' },
        { data: 'description' },
        { data: 'updated_at' }
      ],
      columnDefs: [
        {
          // For Responsive
          className: 'control',
          orderable: false,
          searchable: false,
          responsivePriority: 2,
          targets: 0,
          render: function (data, type, full, meta) {
            return '';
          }
        },
        // {
        //   // For Checkboxes
        //   targets: 1,
        //   orderable: false,
        //   checkboxes: {
        //     selectAllRender: '<input type="checkbox" class="form-check-input">'
        //   },
        //   render: function () {
        //     return '<input type="checkbox" class="dt-checkboxes form-check-input">';
        //   },
        //   searchable: false
        // },
        // {
        //   // Invoice ID
        //   targets: 2,
        //   render: function (data, type, full, meta) {
        //     var $id = full['id'];
        //     // Creates full output for row
        //     var $row_output = '<a href="/user/' + $id + '"/><span>' + $id + '</span></a>';
        //     return $row_output;
        //   }
        // },
        {
          // Client name and Service
          targets: 1,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            var $name = full['vehiculo'],
              $roles = full['roles'],
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
                $description = full['description'],
                $initials = $description.match(/\b\w/g) || [];
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
              '<a href="#" class="text-truncate text-heading"><p class="mb-0 fw-medium">' +
              $description +
              '</p></a>' +
              '</div>' +
              '</div>';
            return $row_output;
          }
        },
        {
          // Email
          targets: 2,
          render: function (data, type, full, meta) {
            var $name = full['name'];
            return '<span>' + $name + '</span>';
          }
        },
        {
          // Due Date
          targets: 3,
          render: function (data, type, full, meta) {
            var $fecha = new Date(full['updated_at']);
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
            // User Role
            targets: 4,
            orderable: false,
            render: function (data, type, full, meta) {
              var $assignedTo = full['roles'],
                $output = '',
                $userList = '';

              var roleBadgeObj = {
                Admin:
                  '<a href="' +
                $userList +
                  '"><span class="badge rounded-pill bg-label-primary me-4">Administrator</span></a>',
                Manager:
                  '<a href="' + $userList + '"><span class="badge rounded-pill bg-label-warning me-4">Manager</span></a>',
                Administracion:
                  '<a href="' + $userList + '"><span class="badge rounded-pill bg-label-success me-4">Administracion</span></a>',
                Servicios:
                  '<a href="' + $userList + '"><span class="badge rounded-pill bg-label-info me-4">Servicios</span></a>',
                Relleno:
                  '<a href="' + $userList + '"><span class="badge rounded-pill bg-label-danger me-4">Relleno</span></a>',
                Mecanicos:
                  '<a href="' + $userList + '"><span class="badge rounded-pill bg-label-info me-4">Mecanicos</span></a>',
                Panol:
                  '<a href="' + $userList + '"><span class="badge rounded-pill bg-label-warning me-4">Panol</span></a>',
                Compras:
                  '<a href="' + $userList + '"><span class="badge rounded-pill bg-label-danger me-4">Compras</span></a>',
                Testing:
                  '<a href="' + $userList + '"><span class="badge rounded-pill bg-label-danger me-4">testing</span></a>',
                RRHH:
                  '<a href="' + $userList + '"><span class="badge rounded-pill bg-label-info me-4">RRHH</span></a>'
              };
              for (var i = 0; i < $assignedTo.length; i++) {
                var val = $assignedTo[i];
                if (roleBadgeObj[val] == undefined) {
                    console.log(val);
                    $output += '<a href="' + $userList + '"><span class="badge rounded-pill bg-label-danger me-4">' + val + '</span></a>'
                } else {
                    $output += roleBadgeObj[val];
                }
              }
              return '<span class="text-nowrap">' + $output + '</span>';
            }
        },
        {
          // Actions: Se saco el titulo: Acciones
          targets: -1,
          title: '',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            //console.log(full['id']);
            var $id = full['id'];

            return ('');

            return (
              '<div class="d-flex align-items-center">' +
              '<a href="/users/roles/' + $id + '" data-bs-toggle="tooltip" class="btn btn-sm btn-icon btn-text-secondary waves-effect waves-light rounded-pill" data-bs-placement="top" title="Asignación de roles"><i class="ri-edit-box-line me-2 ri-20px"></i></a>' +
              '<a href="/user/edit/' + $id + '" data-bs-toggle="tooltip" class="btn btn-sm btn-icon btn-text-secondary waves-effect waves-light rounded-pill" data-bs-placement="top" title="Modificar usuario"><i class="ri-edit-line ri-20px"></i></a>' +
              '<a href="/users/delete/' + $id + '" data-bs-toggle="tooltip" class="btn btn-sm btn-icon btn-text-secondary waves-effect waves-light rounded-pill delete-record" data-bs-placement="top" title="Borrar usuario"><i class="ri-delete-bin-7-line ri-20px"></i></a>' +
              '</div>'
            );
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
        // {
        //   text: '<i class="ri-add-line ri-16px me-md-2 align-baseline"></i><span class="d-md-inline-block d-none">Crear nuevo usuario</span>',
        //   className: 'btn btn-primary waves-effect waves-light',
        //   action: function (e, dt, button, config) {
        //     window.location = '/register';
        //   }
        // }
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
                    '<td id="a">' +
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
          .columns(4)
          .every(function () {
            var column = this;
            var select = $(
              '<select id="UserRole" class="form-select"><option value=""> Todos </option></select>'
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
