/**
 * DataTables Basic
 */

'use strict';



// datatable (jquery)
$(function () {
  var dt_basic_table = $('.datatables-basic'),
    dt_basic;

  // DataTable with buttons
  // --------------------------------------------------------------------
  if (dt_basic_table.length) {
    // ajax: assetsPath + 'json/table-datatable.json', 
    // ajax: '/rutas/json',
    
    dt_basic = dt_basic_table.DataTable({
      ajax: '/rutas/json',
      columns: [
        { data: '' },
        { data: 'id' },
        { data: 'id' },
        { data: 'codigo' },
        { data: 'detalle' },
        { data: 'obs1' },
        { data: '' }
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
        {
          // For Checkboxes
          targets: 1,
          orderable: false,
          searchable: false,
          responsivePriority: 3,
          checkboxes: true,
          render: function () {
            return '<input type="checkbox" class="dt-checkboxes form-check-input">';
          },
          checkboxes: {
            selectAllRender: '<input type="checkbox" class="form-check-input">'
          }
        },
        {
          targets: 2,
          searchable: false,
          visible: false
        },
        {
          //---------------------------------------------------
          // Avatar image/badge, Name and post: Columna 2
          //---------------------------------------------------
          targets: 3,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            var $user_img = full['avatar'],
              $id = full['id'],
              $name = full['codigo'],
              $post = '';   // full['post'];
            if ($user_img) {
              // For Avatar image
              var $output =
                '<img src="' + assetsPath + 'img/avatars/' + $user_img + '" alt="Avatar" class="rounded-circle">';
            } else {
              // For Avatar badge
              var stateNum = Math.floor(Math.random() * 6);
              var states = ['success', 'danger', 'warning', 'info', 'dark', 'primary', 'secondary'];
              var $state = states[stateNum],
                $name = full['codigo'],
                $initials = $name.match(/\b\w/g) || [];
              $initials = (($initials.shift() || '') + ($initials.pop() || '')).toUpperCase();
              $output = '<span class="avatar-initial rounded-circle bg-label-' + $state + '">' + $initials + '</span>';
            }

            // Daniel chequear
            //alert($name);

            // Creates full output for row
            var $row_output =
              '<div class="d-flex justify-content-start align-items-center user-name">' +
              '<div class="avatar-wrapper">' +
              '<div class="avatar me-2">' +
              $output +
              '</div>' +
              '</div>' +
              '<div class="d-flex flex-column">' +
              '<span class="emp_name text-truncate text-heading fw-medium">' +
              $name +
              '</span>' +
              '<small class="emp_post text-truncate">' +
              '# ' + $id +
              '</small>' +
              '</div>' +
              '</div>';

            //alert('$id : ' . $id);

            return $row_output;
          }
        },
        {
          responsivePriority: 1,
          targets: 4
        },
        {
          // Label
          targets: -2,
          render: function (data, type, full, meta) {
            var $status_number = full['status'];
            var $status = {
              1: { title: 'Current', class: 'bg-label-primary' },
              2: { title: 'Professional', class: ' bg-label-success' },
              3: { title: 'Rejected', class: ' bg-label-danger' },
              4: { title: 'Resigned', class: ' bg-label-warning' },
              5: { title: 'Applied', class: ' bg-label-info' }
            };
            if (typeof $status[$status_number] === 'undefined') {
              return data;
            }
            return (
              '<span class="badge rounded-pill ' +
              $status[$status_number].class +
              '">' +
              $status[$status_number].title +
              '</span>'
            );
          }
        },
        {
          //-------------------------------------------
          // Actions: Botones derecha de la grilla
          //-------------------------------------------
          targets: -1,
          title: 'Comandos',
          orderable: false,
          searchable: false,
          render: function (data, type, full, meta) {
            var $id = full['id'];

            return (
              '<div class="d-inline-block">' +
              '<a href="javascript:;" class="btn btn-sm btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="ri-more-2-line"></i></a>' +
              '<ul class="dropdown-menu dropdown-menu-end m-0">' +
              '<li><a href="/rutas/' + $id + '" class="dropdown-item">Detalles</a></li>' +
              '<div class="dropdown-divider"></div>' +
              '<li><a href="/rutas/' + $id + '" class="dropdown-item text-danger delete-record">Borrar</a></li>' +
              '</ul>' +
              '</div>' +
              '<a href="/rutas/' + $id + '" class="btn btn-sm btn-text-secondary rounded-pill btn-icon item-edit"><i class="ri-edit-box-line"></i></a>'
            );
          }
        }
      ],
      order: [[2, 'desc']],
      dom: '<"card-header flex-column flex-md-row border-bottom"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"B>><"row"<"col-sm-12 col-md-6 mt-5 mt-md-0"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
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
                columns: [3, 4, 5, 6, 7],
                // prevent avatar to be display
                format: {
                  body: function (inner, coldex, rowdex) {
                    alert(inner.length);
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              },
              customize: function (win) {
                //customize print view for dark
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
                columns: [3, 4, 5, 6, 7],
                // prevent avatar to be display
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
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
                columns: [3, 4, 5, 6, 7],
                // prevent avatar to be display
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            }
          ]
        }
        // Se borro boton de agregar 
        //  ,
        // {
        //   text: '<i class="ri-add-line ri-16px me-sm-2"></i> <span class="d-none d-sm-inline-block">Add New Record</span>',
        //   className: 'create-new btn btn-primary waves-effect waves-light'
        // }
      ],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of ' + data['codigo'];
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
      }
    });
    $('div.head-label').html('<h5 class="card-title mb-0">Buscar rutas</h5>');
  }

  // Add New record
  // ? Remove/Update this code as per your requirements
  var count = 101;
  // On form submit, if form is valid
  fv.on('core.form.valid', function () {
    var $new_name = $('.add-new-record .dt-full-name').val(),
      $new_post = $('.add-new-record .dt-post').val(),
      $new_detalle = $('.add-new-record .dt-detalle').val(),
      $new_date = $('.add-new-record .dt-date').val() //,
      //$new_salary = $('.add-new-record .dt-salary').val();

    //alert($new_name);

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

      // Hide offcanvas using javascript method
      offCanvasEl.hide();
    }
  });

  // Delete Record
  $('.datatables-basic tbody').on('click', '.delete-record', function () {
    dt_basic.row($(this).parents('tr')).remove().draw();
  });

});
