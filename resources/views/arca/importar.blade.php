@extends('layouts.legajos2')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/bs-stepper.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/bd-wizard.css') }}">

<style>
    .form-control {
        background-color: #ffffff !important;
    }
    .tab-content {
        padding-left: 24px !important;
    }
    .row.g-6 {
        padding-left: 15px !important;
    }
    .col-md-3 {
        padding-left: 15px !important;
    }
    .lbl-md {
        padding-left: 0px !important;
        padding-top: 13px !important;
    }
    .content {
        width: 450px;
    }
</style>
@endsection

<!-- Content -->
@section('content')

<!-- Content -->

<form method="post" id="formImportar" name="formImportar" action="{{ route('arca.importar2') }}" enctype="multipart/form-data">
{{ csrf_field() }}

@php
    $outline = "form-floating-outline";
@endphp

  <div class="container-xxl flex-grow-1 container-p-y">
  
    <!-- Add Product -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
      <div class="d-flex flex-column justify-content-center">
        <h4 class="mb-1">Importar conceptos relacionados desde ARCA</h4>
      </div>
    </div>

    <!-- Form with Tabs -->
    <div class="row">
      <div class="col">
        <!-- {{-- <h6 class="mt-4">Form with Tabs</h6> --}} -->
        <div class="card mb-12">
          <!-- <div class="card-header overflow-hidden">
            <ul class="nav nav-tabs" role="tablist">
              <li class="nav-item">
                <a
                  class="nav-link active"
                  data-bs-toggle="tab"
                  data-bs-target="#form-tabs-personal"
                  role="tab"
                  aria-selected="true">
                  <span class="ri-user-line ri-20px d-sm-none"></span
                  ><span class="d-none d-sm-block">Información Principal</span>
                </a>
              </li>
            </ul>
          </div> -->

          
            

              @if ($errors->any())
                  <div class="alert alert-danger">
                      <ul>
                          @foreach ($errors->all() as $error)
                              <li>{{ $error }}</li>
                          @endforeach
                      </ul>
                  </div>
              @endif

              
              <div class="col-xl-12">
                @if ($errors->any())
                    <div class="alert alert-danger" id="div_errors">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div id="mensaje-procesando"
                    style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%);
                            background:#fff; padding:20px; border-radius:10px; box-shadow:0 0 15px rgba(0,0,0,0.3);
                            font-size:18px; font-weight:bold;">
                    Procesando...
                </div>

                <!-- Vertical Steppers -->
                <nav class="navbar navbar-expand-sm navbar-light bg-white mt-8 pt-4">
                    <div class="container mt-6 ml-5" style="margin-left: 56px;">
                        <div id="wizard" style="height: 600px;">
                            <h3 hidden>Step 1 Title</h3>
                            <section class="pt-2" hidden>
                                <h5 class="bd-wizard-step-title mb-2">Paso 1</h5>
                                <!-- <h3 class="section-heading">Datos de la liquidación </h3> -->
                                <p>Datos de la liquidación</p>

                                <div class="col-md-12 pl-0 mt-5">
                                    <div class="form-row">

                                        <div class="col-lg-12 mt-4 mb-2 mr-4">
                                            <label class="col-form-label" style="padding-top: 0px">Empresa</label>
                                            <select class="form-control selected2"
                                                id="empresa"
                                                name="empresa"
                                                autofocus
                                                style="padding-left: 16px;padding-right: 16px;padding-top: 6px;padding-bottom: 6px;height: 36px;">

                                                @foreach ($empresas as $empresa)
                                                    <option value="{{ $empresa->id }}">{{ $empresa->detalle }}</option>
                                                @endforeach
                                                
                                            </select>
                                        </div>

                                        <div class="col-lg-6 mb-2 mr-4">
                                            <label class="col-form-label">CUIT</label>
                                            <div class="input-group " id="divCuit" data-provide="" keyboardNavigation="false" title="Ingrese un Nro. de CUIT">

                                                <input class="form-control"
                                                    type="text"
                                                    id="cuit"
                                                    name="cuit"
                                                    autocomplete="off"
                                                    maxlength="7"
                                                    style="padding-left: 16px;padding-right: 16px;padding-top: 6px;padding-bottom: 6px;width: 110px"
                                                    value="{{ old('cuit', $cuit ?? '') }}"
                                                    readonly
                                                >

                                                <ul class="parsley-errors-list filled" id="labelError1" aria-hidden="false" hidden>
                                                    <li class="parsley-required">CUIT obligatorio.</li>
                                                </ul>

                                            </div>
                                        </div>

                                        <!-- Activo -->
                                        <div class="col-lg-12 pt-2">
                                            <div class="form-check form-switch mt-4">
                                                <input
                                                    type="hidden"
                                                    name="generar"
                                                    value="0"
                                                />
                                                <input
                                                    type="checkbox"
                                                    id="generar"
                                                    name="generar"
                                                    value="1"
                                                    class="form-check-input"
                                                />
                                                <label class="form-check-label" for="generar">Generar concepto de liquidación interno si no existe ?</label>
                                            </div>
                                        </div>

                                        
                                    </div>
                                </div>

                            </section>
                            <h3 hidden>Step 2 Title</h3>
                            <section class="pt-2" hidden>
                                <h5 class="bd-wizard-step-title mb-2">Paso 2</h5>
                                <!-- <h5 class="section-heading" style="margin-bottom: 5px">Elija por favor el archivo a importar</h5> -->
                                <p>Selecciona el archivo de origen descargado desde la plataforma de ARCA:</p>
                                <!-- <div class="form-group">
                                <label for="firstName" class="sr-only">First Name</label>
                                <input type="text" name="firstName" id="firstName" class="form-control" placeholder="First Name">
                                </div> -->
                                <div class="form-row mt-5">
                                    <!-- Campos ocultos con datos del archivo -->
                                    <div class="col-lg-4 mb-2 mr-4" hidden>
                                        <label class="col-form-label">Nombre archivo * </label>
                                        <div class="input-group " id="divNomArch" data-provide="" keyboardNavigation="false" title="Ingrese un Nro. legajo">

                                            <input class="form-control"
                                                type="text"
                                                id="nom_arch"
                                                name="nom_arch"
                                                autocomplete="off"
                                                maxlength="255"
                                                style="padding-left: 16px;padding-right: 16px;padding-top: 6px;padding-bottom: 6px;"
                                                value="{{ old('nom_arch',$legajo->nom_arch) }}"
                                                readonly
                                                >
                                        </div>
                                    </div>


                                    <div class="col-lg-4 mb-2 mr-4" hidden>
                                        <label class="col-form-label">Tamaño archivo * </label>
                                        <div class="input-group " id="divTamanio" data-provide="" keyboardNavigation="false" title="Ingrese un Nro. legajo">

                                            <input class="form-control"
                                                type="text"
                                                id="tam_arch"
                                                name="tam_arch"
                                                autocomplete="off"
                                                maxlength="255"
                                                style="padding-left: 16px;padding-right: 16px;padding-top: 6px;padding-bottom: 6px;"
                                                value="{{ old('tam_arch',$legajo->tam_arch) }}"
                                                readonly
                                                >
                                        </div>
                                    </div>

                                    <div class="form-group col-lg-12 row align-items-center">
                                        <div class="col-md-12">
                                            <label for="file">El archivo suele llamarse Conceptos_Contribuyente...txt :</label>
                                            <input type="file"
                                                name="file"
                                                id="file"
                                                class="form-control @error('file') is-invalid @enderror"
                                                required
                                                accept=".txt" />
                                            <small class="form-text text-muted">El archivo debe ser un archivo de texto separado por comas.</small>
                                        </div>
                                        <div class="alert alert-danger ml-3" role="alert" hidden>
                                            <strong>Cuidado</strong> Debe seleccionar un archivo válido.
                                        </div>

                                        <div class="invalid-feedback d-block ml-3" id="fileError" style="display: none;"></div>


                                    </div>

                                    <div class="invalid-feedback">
                                        {{ $errors->first('file') }}
                                    </div>
                                    <br>
                                    <div class="alert alert-danger" id="errorsize" style="display: none;>
                                        <ul>
                                            <li id="lierror">El archivo excede los 2 mb. </li>
                                        </ul>
                                    </div>
                                    <div class="row g-12">
                                    </div>
                                    <br><br>
                                </div>
                            </section>
                            <h3 hidden>Step 3 Title</h3>
                            <section class="pt-2" hidden>
                                <h5 class="bd-wizard-step-title">Paso 3</h5>
                                <h2 class="section-heading mb-5" id="titleFinish" name="titleFinish">Procesando importación...</h2>

                                <div id="loadingDiv" name="loadingDiv" class="pt-0">
                                    <div class="ball-grid-pulse">
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                    </div>
                                </div>


                                <h6 class="font-weight-bold" id="registrosOk" name="registrosOk" hidden>
                                    Esperando registros importados correctamente
                                </h6>

                                <a class="btn btn-primary"
                                    id="btn-ok"
                                    name="btn-ok"
                                    type="button"
                                    hidden
                                    mt-5
                                    mb-4
                                    href="{{ route('arca.importar.exportarOk') }}"
                                    >Descargar registros importados
                                </a>

                                <h6 class="font-weight-bold mt-4" id="registrosErr" name="registrosErr" hidden>
                                    Esperando registros rechazados
                                </h6>

                                <a class="btn btn-danger mt-4"
                                    id="btn-err"
                                    name="btn-err"
                                    type="button"
                                    hidden
                                    mb-2
                                    href="{{ route('arca.importar.exportarErr') }}">Descargar registros rechazados
                                </a>

                            </section>
                        </div>
                    </div>
                </nav>
                <!-- /.Vertical Steppers -->

                      

                <!----------------------------------------------
                //           Legajo - Novedad - Fechas
                //--------------------------------------------->
                <div class="col-lg-12 mb-1 mt-5">

                    <!-- {{-- @i f (session('error') or $errors->any() or session('success')) -->
                        <div class="col-md-12 ml-0 pl-0">
                            <div class="card card-default mb-1 border-warning">
                                <div class="card-header text-white bg-warning" id="headingTwo">
                                <h5 class="mb-0">
                                    <a class="text-inherit collapsed" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo" href="">
                                    Resultado de la importación</a>
                                </h5>
                                </div>
                                <div class="collapse show" id="collapseTwo" aria-labelledby="headingTwo" data-parent="#accordion">
                                <div class="col-md-10">
                                    <div class="form-row">

                                        <div class="col-md-10 mt-3 mb-3">
                                            <div class="form-row">

                                                @if (session('error') or $errors->any())
                                                    <h4 class="mb-2">
                                                        Problemas detectados :
                                                    </h4>

                                                    @if (session('error'))
                                                        {{ session('error') }}
                                                    @endif

                                                    @if ($errors->any())
                                                        <ul>
                                                            @foreach ($errors->all() as $error)
                                                                <li>{{ $error }}</li>
                                                            @endforeach
                                                        </ul>
                                                    @endif

                                                    @error('file')
                                                        <div class="invalid-feedback">
                                                            {{ $message }}
                                                        </div>
                                                    @enderror

                                                @endif

                                                @if (session('success'))
                                                    <ul>
                                                        <li>
                                                            <h4 class="mb-2">
                                                                1. {{ session('success') }} <br>
                                                                2. {{ session('rechazados') }}
                                                            </h4>
                                                        </li>
                                                    </ul>
                                                @endif


                                            </div>
                                        </div>

                                    </div>
                                </div>

                                </div>
                            </div>
                        </div>
                    <!-- @ endif --}} -->

                </div>
                    

                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- Formulario de carga de legajos -->
                <div class="row">
                    <div class="col-lg-12 ml-4 mb-12 mt-1 ml-12">

                        <h4 id="titleProgress" style="height: 30px; display: none;">Importando liquidación</h4>

                        <!-- Barra de progreso -->
                        <div class="progress mt-2" style="height: 30px; display: none;" id="progressContainer">
                            <div class="progress-bar progress-bar-striped progress-bar-animated"
                                role="progressbar"
                                style="width: 10%"
                                id="progressBar">
                                10%
                            </div>
                        </div>

                        <!-- Estado -->
                        <div class="mt-2" id="progressStatus"></div>

                    </div>
                </div>

                <!-- Alerta de OK -->
                <div class="alert alert-success mt-3" role="alert" style="display: none;" id="alertOk"></div>

                <!-- Alerta de errores -->
                <div class="alert alert-danger mt-3" role="alert" style="display: none;" id="alertErr"></div>

              </div>

              <br><br><br>

              <!-- <div class="tab-pane fade" id="form-tabs-social" role="tabpanel">

                  <div class="row g-6">
                    <div class="col-md-6">
                      <div class="form-floating form-floating-outline">
                        <input
                          type="text"
                          id="formtabs-twitter"
                          class="form-control"
                          placeholder="https://twitter.com/abc" />
                        <label for="formtabs-twitter">Twitter</label>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-floating form-floating-outline">
                        <input
                          type="text"
                          id="formtabs-facebook"
                          class="form-control"
                          placeholder="https://facebook.com/abc" />
                        <label for="formtabs-facebook">Facebook</label>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-floating form-floating-outline">
                        <input
                          type="text"
                          id="formtabs-google"
                          class="form-control"
                          placeholder="https://plus.google.com/abc" />
                        <label for="formtabs-google">Google+</label>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-floating form-floating-outline">
                        <input
                          type="text"
                          id="formtabs-linkedin"
                          class="form-control"
                          placeholder="https://linkedin.com/abc" />
                        <label for="formtabs-linkedin">Linkedin</label>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-floating form-floating-outline">
                        <input
                          type="text"
                          id="formtabs-instagram"
                          class="form-control"
                          placeholder="https://instagram.com/abc" />
                        <label for="formtabs-instagram">Instagram</label>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-floating form-floating-outline">
                        <input
                          type="text"
                          id="formtabs-quora"
                          class="form-control"
                          placeholder="https://quora.com/abc" />
                        <label for="formtabs-quora">Quora</label>
                      </div>
                    </div>
                  </div>

              </div> -->
          
        </div>

      </div>
    </div>
  </div>

</form>
<!-- / Content -->

<!-- MODAL -->
<div class="col-lg-4 col-md-3">
  {{-- <small class="text-light fw-medium">Backdrop</small> --}}
  <div class="mt-4">
    <!-- Modal -->
    <div class="modal fade" id="backDropModal" data-bs-backdrop="static" tabindex="-1">
      <div class="modal-dialog">
        <form class="modal-content">
          <div class="modal-header">
            <h4 class="onboarding-title text-body">Borrar registro ?</h4>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col mb-6 mt-1">

                <div class="alert d-flex align-items-center alert-warning mb-0 h6" role="alert">
                  <span class="alert-icon rounded-4"><i class="ri-information-line ri-22px"></i></span>

                  <span> Esta seguro de eliminar el registro seleccionado? <br> No podra recuperar el registro borrado... </span>
                </div>

              </div>
            </div>
            <div class="row g-4">
              <br>
            </div>
          </div>
          <div class="modal-footer">
            <a href="{{ asset('/sicoss/importar/delete') }}/{{ $legajo->id }}" type="button" class="btn btn-danger waves-effect waves-light" style="color: white">Borrar</a>

            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
              Cancelar
            </button>

          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
{{-- <div class="modal fade" id="youTubeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <iframe height="350" src="https://www.youtube.com/embed/EngW7tLk6R8"></iframe>
    </div>
  </div>
</div> --}}

@endsection
<!-- / Content -->

@section('scripts')
<script src="{{ asset('js/jquery.steps.js') }}"></script>
<script src="{{ asset('js/bd-wizard.js') }}"></script>

<script>
    // Cargar CUIT al seleccionar empresa
    document.addEventListener('DOMContentLoaded', function() {
        const empresaSelect = document.getElementById('empresa');
        const cuitInput = document.getElementById('cuit');

        empresaSelect.addEventListener('change', function(e) {
            const empresaId = e.target.value;

            if (!empresaId) {
                cuitInput.value = '';
                return;
            }

            fetch(`{{ url('arca/empresa') }}/${empresaId}/cuit`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('No se pudo obtener el CUIT');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.cuit) {
                        cuitInput.value = data.cuit;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    cuitInput.value = '';
                });
        });
    });
</script>

<script>
  function checkcontrols() {
        var validacionFecha = true;
        var validacionPeriodo = true;

        return true;
    }

    function validarFechaLiquidacion() {
        const campoFecha = document.getElementById('fecha');
        const valorFecha = campoFecha.value.trim();

        // Verificar si está vacío
        if (valorFecha === '' || valorFecha === null) {
            mostrarErrorFecha('La fecha de liquidación es obligatoria');
            return false;
        }

        // Opcional: Validar formato de fecha (dd/mm/yyyy)
        const formatoFecha = /^\d{2}\/\d{2}\/\d{4}$/;
        if (!formatoFecha.test(valorFecha)) {
            mostrarErrorFecha('Formato de fecha inválido. Use dd/mm/yyyy');
            return false;
        }

        // Si llegamos aquí, la validación pasó
        ocultarErrorFecha();
        return true;
    }


    function mostrarErrorFecha(mensaje) {
        // Remover errores previos
        removerErrorFecha();

        const campoFecha = document.getElementById('fecha');
        const contenedorFecha = campoFecha.closest('.col-lg-3');

        // Crear el elemento de error
        const errorDiv = document.createElement('ul');
        errorDiv.className = 'parsley-errors-list filled';
        errorDiv.id = 'errorFecha';
        errorDiv.setAttribute('aria-hidden', 'false');

        const errorItem = document.createElement('li');
        errorItem.className = 'parsley-required';
        errorItem.textContent = mensaje;

        errorDiv.appendChild(errorItem);

        // Agregar el error después del input-group
        const inputGroup = contenedorFecha.querySelector('.input-group');
        inputGroup.parentNode.insertBefore(errorDiv, inputGroup.nextSibling);

        // Agregar clase de error al campo
        campoFecha.classList.add('parsley-error');
    }

    // Función para ocultar/remover error
    function ocultarErrorFecha() {
        removerErrorFecha();
    }

    function removerErrorFecha() {
        const errorExistente = document.getElementById('errorFecha');
        if (errorExistente) {
            errorExistente.remove();
        }

        const campoFecha = document.getElementById('fecha');
        campoFecha.classList.remove('parsley-error');
    }    
</script>

<script>
    document.getElementById('formImportar').addEventListener('submit', function (e) {
        const btn = document.getElementById('btngrabar');
        btn.disabled = true;
        btn.innerText = 'Importando...';

        // Muestra barra
        document.getElementById('titleProgress').style.display = 'block';
        document.getElementById('progressContainer').style.display = 'block';

        // Inicia polling
        const interval = setInterval(() => {
            fetch('import/status')
                .then(response => response.json())
                .then(data => {
                    const ok = data.ok;
                    const err = data.err;

                    // Supongamos que esperás 1000 registros máximo
                    const totalEsperado = 1000; // Ajustá a tu valor real
                    const totalActual = ok + err;
                    const porcentaje = Math.min(100, (totalActual / totalEsperado) * 100).toFixed(2);

                    const bar = document.getElementById('progressBar');
                    bar.style.width = porcentaje + '%';
                    bar.innerText = porcentaje + '%';

                    document.getElementById('progressStatus').innerText =
                        `Importados OK: ${ok} | Rechazados: ${err}`;

                    // Si se terminó (ajustá la condición según tu lógica real)
                    if (totalActual >= totalEsperado) {
                        clearInterval(interval);
                    }
                })
                .catch(error => {
                    console.error(error);
                    clearInterval(interval);
                    document.getElementById('progressStatus').innerText = 'Error al consultar estado.';
                });
        }, 2000);
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('file');
        const alertDanger = document.querySelector('.alert.alert-danger[role="alert"]');
        const fileError = document.getElementById('fileError');
        const errorSizeAlert = document.getElementById('errorsize');

        // Configuración
        const maxFileSize = 2 * 1024 * 1024; // 2MB en bytes
        const allowedExtensions = ['txt'];
        const allowedMimeTypes = [
            'text/csv',
            'text/plain',
            'application/csv',
            'application/vnd.ms-excel',
        ];

        // Función para validar el archivo
        function validarArchivo(file) {
            const errores = [];

            // Verificar si se seleccionó un archivo
            if (!file) {
                errores.push('Debe seleccionar un archivo');
                return errores;
            }

            // Verificar extensión del archivo
            const extension = file.name.split('.').pop().toLowerCase();
            //alert(extension);
            if (!allowedExtensions.includes(extension)) {
                errores.push('El archivo debe ser de formato texto TXT o CSV');
            }

            // Verificar tipo MIME
            if (!allowedMimeTypes.includes(file.type)) {
                errores.push('Tipo de archivo no válido ');
                console.log(file.type);
            }

            // Verificar tamaño del archivo
            if (file.size > maxFileSize) {
                errores.push('El archivo excede los 2 MB');
            }

            // Verificar que el archivo no esté vacío
            if (file.size === 0) {
                errores.push('El archivo está vacío');
            }

            /* ---------------------------
            VALIDACIÓN CUIT EN NOMBRE
            ----------------------------*/

            const cuitArchivo = obtenerCuitDesdeNombreArchivo(file.name);

            if (!cuitArchivo) {
                errores.push('No se pudo detectar el CUIT en el nombre del archivo');
            } else {

                if (!validarCUIT(cuitArchivo)) {
                    errores.push('El CUIT incluido en el nombre del archivo no es válido');
                } else {

                    const cuitFormulario = obtenerCuitFormulario();

                    if (!cuitFormulario) {
                        errores.push('Debe ingresar el CUIT de la empresa');
                    } else if (cuitFormulario !== cuitArchivo) {
                        errores.push(
                            `El CUIT del archivo (${cuitArchivo}) no coincide con el CUIT ingresado (${cuitFormulario})`
                        );
                    }
                }
            }

            return errores;
        }

        function obtenerCuitDesdeNombreArchivo(filename) {
            const partes = filename.split('_');

            // buscamos un bloque de 11 dígitos
            const posibleCuit = partes.find(p => /^\d{11}$/.test(p));

            return posibleCuit || null;
        }

        function obtenerCuitDesdeNombreArchivo(filename) {
            const partes = filename.split('_');

            // buscamos un bloque de 11 dígitos
            const posibleCuit = partes.find(p => /^\d{11}$/.test(p));

            return posibleCuit || null;
        }

        function obtenerCuitFormulario() {
            const input = document.getElementById('cuit');
            return input ? input.value.replace(/\D/g, '') : '';
        }

        
        function validarCUIT(cuit) {

            if (!/^\d{11}$/.test(cuit)) return false;

            const coef = [5,4,3,2,7,6,5,4,3,2];
            let suma = 0;

            for (let i = 0; i < 10; i++) {
                suma += parseInt(cuit[i]) * coef[i];
            }

            let resto = suma % 11;
            let digito = 11 - resto;

            if (digito === 11) digito = 0;
            if (digito === 10) digito = 9;

            return digito === parseInt(cuit[10]);
        }


        // Función para mostrar errores
        function mostrarErrores(errores) {
            ocultarErrores();

            if (errores.length > 0) {
                // Mostrar error de tamaño si corresponde
                if (errores.some(error => error.includes('2 MB'))) {
                    errorSizeAlert.style.display = 'block';
                }

                // Mostrar alerta general
                alertDanger.removeAttribute('hidden');
                alertDanger.style.display = 'block';

                // Mostrar errores específicos
                fileError.innerHTML = errores.join('<br>');
                fileError.style.display = 'block';

                // Agregar clase de error al input
                fileInput.classList.add('is-invalid');

                return false;
            }

            return true;
        }

        // Función para ocultar errores
        function ocultarErrores() {
            alertDanger.setAttribute('hidden', true);
            alertDanger.style.display = 'none';
            errorSizeAlert.style.display = 'none';
            fileError.style.display = 'none';
            fileInput.classList.remove('is-invalid');
        }

        // Función para mostrar información del archivo válido
        function mostrarInfoArchivo(file) {
            const nomarch = document.getElementById('nom_arch');
            const tamarch = document.getElementById('tam_arch');
            const info = document.getElementById('archivoInfo');
            if (info) {
                info.remove();
            }

            const infoDiv = document.createElement('div');
            infoDiv.id = 'archivoInfo';
            infoDiv.className = 'alert alert-success mt-2';
            infoDiv.innerHTML = `
                <strong>Archivo seleccionado:</strong><br>
                <small>
                    <i class="fas fa-file-excel"></i> ${file.name}<br>
                    <i class="fas fa-weight"></i> Tamaño: ${(file.size / 1024).toFixed(2)} KB<br>
                    <i class="fas fa-calendar"></i> Modificado: ${new Date(file.lastModified).toLocaleDateString('es-ES')}
                </small>
            `;

            fileInput.parentNode.appendChild(infoDiv);

            nomarch.value = file.name;
            tamarch.value = file.size;
        }

        // Event listener para cambio de archivo
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const errores = validarArchivo(file);

            if (mostrarErrores(errores)) {
                mostrarInfoArchivo(file);
            }
        });

        // Validación antes de avanzar al siguiente paso del wizard
        window.validarPaso2 = function() {
            const file = fileInput.files[0];
            const errores = validarArchivo(file);

            if (errores.length > 0) {
                mostrarErrores(errores);
                return false;
            }

            return true;
        };

        // Función para limpiar la selección
        window.limpiarArchivo = function() {
            fileInput.value = '';
            ocultarErrores();
            const info = document.getElementById('archivoInfo');
            if (info) {
                info.remove();
            }
        };

        // Drag and drop functionality (opcional)
        const fileInputContainer = fileInput.parentNode;

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileInputContainer.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            fileInputContainer.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileInputContainer.addEventListener(eventName, unhighlight, false);
        });

        fileInputContainer.addEventListener('drop', handleDrop, false);

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function highlight(e) {
            fileInputContainer.classList.add('border-primary', 'bg-light');
        }

        function unhighlight(e) {
            fileInputContainer.classList.remove('border-primary', 'bg-light');
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files.length > 0) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }


        // Función para subir el archivo (para usar con AJAX si es necesario)
        window.subirArchivo = function(callback) {
            const file = fileInput.files[0];
            const loadingDiv = document.getElementById('loadingDiv');

            if (!validarPaso2()) {
                if (callback) callback(false, 'Archivo no válido');
                return;
            }

            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            formData.append('empresa', document.getElementById('empresa').value);   // ej: 1
            //formData.append('fecha', document.getElementById('fecha').value);       // ej: 16/08/2025
            //formData.append('comenta1', document.getElementById('comenta1').value);       // ej: 16/08/2025
            formData.append('nom_arch', document.getElementById('nom_arch').value);
            formData.append('tam_arch', document.getElementById('tam_arch').value);
            formData.append('generar', document.getElementById('generar').checked ? '1' : '0');

            // Mostrar loading
            loadingDiv.removeAttribute('hidden');

            // Realizar upload con fetch
            fetch("{{ route('arca.importar2') }}", {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    //throw new Error('Error en la subida');
                    console.log(response);
                }
                return response.json();
            })
            .then(data => {
                // Ocultar loading
                loadingDiv.setAttribute('hidden', true);

                console.log(data);

                if (data.success) {
                    // Actualizar contadores en el DOM

                    // Que hago con este proceso ???
                    // Deuda tecnica
                }

                if (callback) callback(true, data);
            })
            .catch(error => {
                loadingDiv.setAttribute('hidden', true);

                console.error('Error:', error);
                mostrarErrores(['Error al subir el archivo: ' + error.message]);

                if (callback) callback(false, error.message);
            });
        };



        // Función para previsualizar el archivo Excel (opcional)
        window.previsualizarExcel = function() {
            const file = fileInput.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                // Aquí podrías usar una librería como SheetJS para leer el Excel
                console.log('Archivo leído para previsualización');
                // Implementar previsualización si es necesario
            };
            reader.readAsArrayBuffer(file);
        };
    });

    // Función para integrar con el wizard
    document.addEventListener('DOMContentLoaded', function() {
        // Si estás usando un wizard específico, integra la validación
        const nextButton = document.querySelector('.btn-next'); // Ajusta el selector

        if (nextButton) {
            nextButton.addEventListener('click', function(e) {
                const currentStep = getCurrentStep(); // Implementa según tu wizard

                if (currentStep === 2) { // Si estamos en el paso 2
                    if (!validarPaso2()) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }
                }
            });
        }
    });

    // Muestro los elementos del wizard ocultos al cargar toda la página web
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.pt-2[hidden]').forEach(el => {
            el.removeAttribute('hidden');
        });
    });

</script>

@endsection

