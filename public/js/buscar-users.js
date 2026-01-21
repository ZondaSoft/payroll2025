/**
 * App Invoice List (jquery)
 */

'use strict';

$(function () {
  // Variable declaration for table
  var dt_invoice_table = $('#users-table');

  // Invoice datatable
  // --------------------------------------------------------------------
  if (dt_invoice_table.length) {
    // ajax: assetsPath + 'json/invoice-list.json',
    // ajax: '/requisition1/json',

    var dt_invoice = dt_invoice_table.DataTable({
      ajax: '/users/json',    // JSON file to add data
      autoWidth: false,
      columns: [
        // columns according to JSON
        { data: 'id' },
        { data: 'name' },
        { data: 'email' },
        { data: 'updated_at' },
        { data: 'roles' },
        { data: 'id' }
      ],
      columnDefs: [
        // =====================
        // COLUMNA 0 → ID con link
        // =====================
        {
          targets: 0,
          render: function (data, type, full) {
            return `<a href="/user/edit/${full.id}">
                  <span>${full.id}</span>
                </a>`;
          }
        },

        // =====================
        // COLUMNA 1 → Avatar + Nombre + rol
        // =====================
        {
          targets: 1,
          responsivePriority: 1,
          render: function (data, type, full) {

            var name = full.name || '';
            var roles = full.roles || '';
            var id = full.id;

            // Iniciales
            var initials = (name.match(/\b\w/g) || [])
              .join('')
              .substring(0, 2)
              .toUpperCase();

            // Colores aleatorios como antes
            var states = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'];
            var stateNum = Math.floor(Math.random() * states.length);
            var state = states[stateNum];

            var output = `
              <span class="avatar-initial rounded-circle bg-label-${state}">
                ${initials}
              </span>
            `;

            return `
              <div class="d-flex align-items-center">
                <div class="avatar avatar-sm me-3">
                  ${output}
                </div>
                <div class="d-flex flex-column">
                  <a href="/user/edit/${id}" class="text-heading fw-medium">
                    ${name}
                  </a>
                  <small class="text-muted">
                    ${roles}
                  </small>
                </div>
              </div>
            `;
          }
        },

        // =====================
        // COLUMNA 3 → Fecha formateada
        // =====================
        {
          targets: 3,
          render: function (data) {
            let fecha = moment(data);
            return `
            <span class="d-none">${fecha.format('YYYYMMDD')}</span>
            ${fecha.format('DD/MM/YYYY')}
        `;
          }
        },

        // =====================
        // COLUMNA 4 → Roles con íconos
        // =====================
        {
          targets: 4,
          render: function (data, type, full) {

            let role = full.roles;
            let icons = {
              Servicios: '<i class="ri-user-line ri-22px text-success me-2"></i>',
              Relleno: '<i class="ri-vip-crown-line ri-22px text-primary me-2"></i>',
              Mecanicos: '<i class="ri-truck-line ri-22px text-warning me-2"></i>',
              Panol: '<i class="ri-home-gear-fill ri-22px text-info me-2"></i>',
              Compras: '<i class="ri-edit-box-line ri-22px text-warning me-2"></i>',
              Administracion: '<i class="ri-user-line ri-22px text-warning me-2"></i>',
              RRHH: '<i class="ri-edit-box-line ri-22px text-warning me-2"></i>',
              Manager: '<i class="ri-computer-line ri-22px text-danger me-2"></i>',
            };

            const icon = icons[role] || '<i class="ri-user-line ri-22px text-info me-2"></i>';

            return `
          <span class="d-flex align-items-center">
            ${icon}
          </span>
        `;
          }
        },

        // =====================
        // COLUMNA 5 → Acciones
        // =====================
        {
          targets: -1,
          orderable: false,
          searchable: false,
          title: 'Acciones',
          render: function (data, type, full) {
            let id = full.id;
            return `
          <div class="d-flex align-items-center">
            <a href="/users/roles/${id}" class="btn btn-sm btn-icon btn-text-secondary" title="Roles">
              <i class="ri-edit-box-line ri-20px"></i>
            </a>
            <a href="/user/edit/${id}" class="btn btn-sm btn-icon btn-text-secondary" title="Editar">
              <i class="ri-edit-line ri-20px"></i>
            </a>
            <a href="/users/delete/${id}" class="btn btn-sm btn-icon btn-text-secondary delete-record" title="Borrar">
              <i class="ri-delete-bin-7-line ri-20px"></i>
            </a>
          </div>
        `;
          }
        }
      ]
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
