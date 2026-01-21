/**
 * DataTables Vehículos
 */
'use strict';

$(function () {
  var dt_basic_table = $('.datatables-basic'),
    dt_basic;

  if (dt_basic_table.length) {
    dt_basic = dt_basic_table.DataTable({
      ajax: '/vehiculos/json',
      // --------------------------------------------------
      // Definición de columnas (SIN checkbox)
      // --------------------------------------------------
      columns: [
        { data: '' },        // 0: columna de control (responsive)
        { data: 'codigo' },  // 1: código + avatar
        { data: 'detalle' }, // 2: detalle
        { data: 'obs1' },    // 3: obs1
        { data: 'id' },      // 4: id (oculta, para ordenar / exportar)
        { data: '' }         // 5: acciones
      ],
      columnDefs: [
        {
          // Columna de control para responsive (+)
          className: 'control',
          orderable: false,
          searchable: false,
          responsivePriority: 2,
          targets: 0,
          render: function (data, type, full, meta) {
            return '';
          }
        },
        {
          // Oculto el id en columna 4
          targets: 4,
          visible: false,
          searchable: false
        },
        {
          //---------------------------------------------------
          // Avatar + código + #id (columna 1)
          //---------------------------------------------------
          targets: 1,
          responsivePriority: 1,
          render: function (data, type, full, meta) {
            var $user_img = full['avatar'],
              $id = full['id'],
              $name = full['codigo'];

            var $output;

            if ($user_img) {
              // Avatar con imagen
              $output =
                '<img src="' + assetsPath + 'img/avatars/' + $user_img + '" alt="Avatar" class="rounded-circle">';
            } else {
              // Avatar con iniciales
              var stateNum = Math.floor(Math.random() * 6);
              var states = ['success', 'danger', 'warning', 'info', 'dark', 'primary', 'secondary'];
              var $state = states[stateNum];
              var $initials = ($name || '').match(/\b\w/g) || [];
              $initials = (($initials.shift() || '') + ($initials.pop() || '')).toUpperCase();
              $output =
                '<span class="avatar-initial rounded-circle bg-label-' + $state + '">' + $initials + '</span>';
            }

            var $row_output =
              '<div class="d-flex justify-content-start align-items-center user-name">' +
              '  <div class="avatar-wrapper">' +
              '    <div class="avatar me-2">' + $output + '</div>' +
              '  </div>' +
              '  <div class="d-flex flex-column">' +
              '    <span class="emp_name text-truncate text-heading fw-medium">' + $name + '</span>' +
              '    <small class="emp_post text-truncate"># ' + $id + '</small>' +
              '  </div>' +
              '</div>';

            return $row_output;
          }
        },
        {
          // detalle (columna 2) con prioridad alta en responsive
          responsivePriority: 3,
          targets: 2
        },
        {
          // obs1 (columna 3)
          responsivePriority: 4,
          targets: 3
        },
        {
          //-------------------------------------------
          // Acciones: última columna (5)
          //-------------------------------------------
          targets: -1,
          title: 'Comandos',
          orderable: false,
          searchable: false,
          render: function (data, type, full, meta) {
            var $id = full['id'];

            return `
              <a href="/vehiculos/${$id}"
                class="btn btn-sm btn-text-secondary rounded-pill btn-icon"
                title="Ver vehículo">
                <i class="ri-edit-box-line"></i>
              </a>
            `;
          }
        }
      ],
      // Ordeno por la columna id (oculta, índice 4)
      order: [[4, 'desc']],
      dom:
        '<"card-header flex-column flex-md-row border-bottom"' +
        '<"head-label text-center">' +
        '<"dt-action-buttons text-end pt-3 pt-md-0"B>' +
        '>' +
        '<"row"' +
        '<"col-sm-12 col-md-6 mt-5 mt-md-0"l>' +
        '<"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>' +
        '>' +
        't' +
        '<"row"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      displayLength: 7,
      lengthMenu: [7, 10, 25, 50, 75, 100],
      language: {
        paginate: {
          next: '<i class="ri-arrow-right-s-line"></i>',
          previous: '<i class="ri-arrow-left-s-line"></i>'
        }
      },
      buttons: [
        {
          extend: 'collection',
          className: 'btn btn-label-primary dropdown-toggle me-4 waves-effect waves-light',
          text: '<i class="ri-external-link-line me-sm-1"></i> <span class="d-none d-sm-inline-block">Exportar</span>',
          buttons: [
            {
              extend: 'print',
              text: '<i class="ri-printer-line me-1" ></i>Imprimir',
              className: 'dropdown-item',
              exportOptions: {
                // Exporto: código, detalle, obs1, id
                columns: [1, 2, 3, 4],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (!inner || inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        // saco solo el texto del nombre (código)
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else {
                        result = result + item.innerText;
                      }
                    });
                    return result;
                  }
                }
              },
              customize: function (win) {
                // vista print para dark
                $(win.document.body)
                  .css('color', config.colors.headingColor)
                  .css('border-color', config.colors.borderColor)
                  .css('background-color', config.colors.bodyBg);
                $(win.document.body)
                  .find('table')
                  .addClass('compact')
                  .css('color', 'inherit')
                  .css('border-color', 'inherit')
                  .css('background-color', 'inherit');
              }
            },
            {
              extend: 'excel',
              text: '<i class="ri-file-excel-line me-1"></i>Excel',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (!inner || inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else {
                        result = result + item.innerText;
                      }
                    });
                    return result;
                  }
                }
              }
            },
            {
              extend: 'pdf',
              text: '<i class="ri-file-pdf-line me-1"></i>Pdf',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (!inner || inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else {
                        result = result + item.innerText;
                      }
                    });
                    return result;
                  }
                }
              }
            }
          ]
        }
      ],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Detalles de ' + data['codigo'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== ''
                ? '<tr data-dt-row="' +
                col.rowIndex +
                '" data-dt-column="' +
                col.columnIndex +
                '">' +
                '<td>' +
                col.title +
                ':</td> ' +
                '<td>' +
                col.data +
                '</td>' +
                '</tr>'
                : '';
            }).join('');

            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
      }
    });

    $('div.head-label').html('<h5 class="card-title mb-0">Buscar vehículos</h5>');
  }

  // Add New record (esto lo dejo tal cual lo tenías)
  var count = 101;
  fv.on('core.form.valid', function () {
    var $new_name = $('.add-new-record .dt-full-name').val(),
      $new_post = $('.add-new-record .dt-post').val(),
      $new_detalle = $('.add-new-record .dt-detalle').val(),
      $new_date = $('.add-new-record .dt-date').val();

    if ($new_name != '') {
      dt_basic.row
        .add({
          id: count,
          codigo: $new_name,
          post: $new_post,
          detalle: $new_detalle,
          obs1: $new_date
        })
        .draw();
      count++;
      offCanvasEl.hide();
    }
  });

  // Delete Record (borra la fila de la tabla, ojo que no llama al backend)
  $('.datatables-basic tbody').on('click', '.delete-record', function () {
    dt_basic.row($(this).parents('tr')).remove().draw();
  });
});
