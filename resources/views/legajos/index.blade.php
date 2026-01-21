@extends('layouts.legajos')

@section('styles')
{{-- <style>
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
</style> --}}
@endsection

<!-- Content -->
@section('content')

<!-- Content -->

@if($agregar == true)
<form method="post" action="{{ asset( url('/artic-insumos/add') ) }}" enctype="multipart/form-data">
@else
<form method="post" action="{{ asset( url('/artic-insumos/edit/'.$legajo->id) ) }}" enctype="multipart/form-data">
@endif

{{ csrf_field() }}

@php
    $outline = "form-floating-outline";
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  <div class="app-ecommerce">
    <!-- Add Product -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
      <div class="d-flex flex-column justify-content-center">
        @if($agregar == true)
          <h4 class="mb-1">Agregar empleado</h4>
        @elseif($edicion == true)
            <h4 class="mb-1">Modificar empleado</h4>
        @else
            <h4 class="mb-1">Empleados activos</h4>
        @endif

      </div>
      <div class="d-flex flex-column justify-content-center">
        <div class="btn-group" role="group" aria-label="First group">
          <a type="button" href="{{ asset('/artic-insumos') }}/0" class="btn btn-outline-secondary waves-effect" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="ir al primer registro">
            <i class="ri-arrow-left-double-line"></i>
          </a>
          <a type="button" href="{{ asset('/artic-insumos') }}/{{ $legajo?$legajo->id:'' }}/-1" class="btn btn-outline-secondary waves-effect" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="ir al registro anterior">
            <i class="ri-arrow-left-line"></i>
          </a>
          <a type="button" href="{{ asset('/artic-insumos') }}/{{ $legajo?$legajo->id:'' }}/1" class="btn btn-outline-secondary waves-effect" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="ir al registro siguiente">
            <i class="ri-arrow-right-line"></i>
          </a>
          <a type="button" href="{{ asset('/artic-insumos') }}/{{ $legajo?$legajo->id:'' }}/-9" class="btn btn-outline-secondary waves-effect" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="ir al ultimo registro">
            <i class="ri-arrow-right-double-fill"></i>
          </a>
          <a type="button" href="{{ asset('/artic-insumos/search') }}" class="btn btn-outline-secondary waves-effect" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Buscar ...">
            <i class="ri-search-line"></i>
          </a>
        </div>
      </div>
      <div class="d-flex align-content-center flex-wrap gap-4">
        @if($agregar == true or $edicion == true)
          <button type="submit" class="btn btn-primary">Grabar</button>
          <a href="{{ asset( url('/artic-insumos') ) }}" class="btn btn-outline-secondary">Cancelar</a>
        @else
          <a href="{{ asset('/artic-insumos/add') }}" type="button" class="btn btn-info waves-effect waves-light">Agregar</a>
          <a href="{{ asset('/artic-insumos/edit') }}/{{ $legajo->id }}" class="btn btn-outline-secondary">Modificar</a>
          <a type="button" class="btn btn-danger waves-effect waves-light" style="color: white"  data-bs-toggle="modal" data-bs-target="#backDropModal">Borrar</a>
        @endif
      </div>
    </div>

    <div class="row">

      <!-- Form with Tabs -->
      <div class="row">
        <div class="col">
          {{-- <h6 class="mt-4">Form with Tabs</h6> --}}
          <div class="card mb-6">
            <div class="card-header overflow-hidden">
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
                <li class="nav-item">
                  <a
                    class="nav-link"
                    data-bs-toggle="tab"
                    data-bs-target="#form-tabs-account"
                    role="tab"
                    aria-selected="false">
                    <span class="ri-folder-user-line ri-20px d-sm-none"></span
                    ><span class="d-none d-sm-block">Sicoss</span>
                    </a>
                </li>
              </ul>
            </div>

            <div class="tab-content">
              <div class="tab-pane fade active show" id="form-tabs-personal" role="tabpanel">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- <div class="row g-6">
                  <div class="col-md-3">
                    <div class="form-floating filled" style="height: 54px;padding-left: 5px;">
                      <input type="text" id="nombre2" name="nombre2" class="form-control"
                        placeholder="15 caracteres max" autocomplete="" />
                      <label for="nombre2" class="lbl-md">Nombre</label>
                    </div>
                    <br>
                    <div class="form-floating {{ $outline }}">
                      <input type="text" id="codigo3" name="codigo3" class="form-control"
                          placeholder="15 caracteres max" maxlength="15" autocomplete="" />
                      <label for="codigo3" class="lbl-md">Código principal</label>
                    </div>

                  </div>
                </div> --}}

                <br>

                <div class="row g-6">
                  <div class="col-md-2">
                    <div class="form-floating {{ $outline }}">
                      <input type="text" id="codigo" name="codigo" class="form-control" placeholder="15 caracteres max" maxlength="15" autocomplete=""
                        {{ $edicion?'':'disabled' }}
                        {{ $agregar?'enabled autofocus=""':'disabled' }}
                        value="{{ old('codigo',$legajo->codigo) }}" />
                      <label for="codigo">N° legajo</label>
                    </div>
                  </div>
                  <div class="col-md-3 mb-7">
                    <div class="form-floating {{ $outline }}">
                      <input type="text" id="cuil" name="cuil" class="form-control" placeholder="99-99999999-9" maxlength="13" autocomplete=""
                        {{ $edicion?'':'disabled' }}
                        {{ $agregar?'enabled':'' }}
                        value="{{ old('cuil',$legajo->cuil) }}" />
                      <label for="cuil">CUIL</label>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6 select2-primary">
                      <div class="form-floating {{ $outline }}">
                        <input type="text" id="detalle" name="detalle" class="form-control" placeholder="Apellidos" autocomplete=""
                          {{ $edicion?'':'disabled' }}
                          {{ $agregar?'enabled':'' }}
                          value="{{ old('detalle',$legajo->detalle) }}" />
                        <label for="detalle">Apellidos</label>
                      </div>
                    </div>

                    <div class="col-md-6 select2-primary">
                      <div class="form-floating {{ $outline }}">
                        <input type="text" id="nombres" name="nombres" class="form-control" placeholder="Nombres" autocomplete=""
                          {{ $edicion?'':'disabled' }}
                          {{ $agregar?'enabled':'' }}
                          value="{{ old('nombres',$legajo->nombres) }}" />
                        <label for="nombres">Nombres</label>
                      </div>
                    </div>
                  </div>

                  <div class="col-md-2 select2-primary">
                    <div class="form-floating {{ $outline }}">
                      <input type="date" id="alta" name="alta" class="form-control" placeholder="dd/mm/aaaa" maxlength="15" autocomplete=""
                        {{ $edicion?'':'disabled' }}
                        {{ $agregar?'enabled':'' }}
                        value="{{ old('alta',$legajo->alta) }}" />
                      <label for="alta">Fecha de alta</label>
                    </div>
                  </div>
                  <div class="col-md-2 select2-primary">
                    <div class="form-floating {{ $outline }}">
                      <input type="text" id="antiguedad" name="antiguedad" class="form-control" placeholder="Codigo contable" maxlength="25" autocomplete=""
                        {{ $edicion?'':'disabled' }}
                        {{ $agregar?'enabled':'' }}
                        value="{{ old('antiguedad',$legajo->antiguedad) }}" />
                      <label for="antiguedad">Antiguedad</label>
                    </div>
                  </div>

                  <div class="col-md-2">
                  </div>

                  <div class="col-md-2 select2-primary">
                    <div class="form-floating {{ $outline }}">
                      <input type="date" id="fecha_naci" name="fecha_naci" class="form-control" placeholder="Nro. estanteria" maxlength="10" autocomplete=""
                        {{ $edicion?'':'disabled' }}
                        {{ $agregar?'enabled':'' }}
                        value="{{ old('fecha_naci',$legajo->fecha_naci) }}" />
                      <label for="fecha_naci">Fecha de nacimiento</label>
                    </div>
                  </div>

                  <div class="col-md-2 select2-primary">
                    <div class="form-floating {{ $outline }}">
                      <input type="text" id="edad" name="edad" class="form-control" placeholder="Codigo contable" maxlength="25" autocomplete=""
                        {{ $edicion?'':'disabled' }}
                        {{ $agregar?'enabled':'' }}
                        value="{{ old('edad',$legajo->edad) }}" />
                      <label for="edad">Edad</label>
                    </div>
                  </div>


                  <div class="row">
                  </div>

                  <!-- <div class="col-md-6 select2-primary">
                    <div class="form-floating {{ $outline }}">
                      <input type="text" id="marca" name="marca" class="form-control" placeholder="Marca" maxlength="20" autocomplete=""
                        {{ $edicion?'':'disabled' }}
                        {{ $agregar?'enabled':'' }}
                        value="{{ old('marca',$legajo->marca) }}" />
                      <label for="marca">Marca</label>
                    </div>
                  </div>
                  <div class="col-md-6 select2-primary">
                    <div class="form-floating {{ $outline }}">
                      <input type="text" id="catalogo" name="catalogo" class="form-control" placeholder="Catalogo" maxlength="40" autocomplete=""
                        {{ $edicion?'':'disabled' }}
                        {{ $agregar?'enabled':'' }}
                        value="{{ old('catalogo',$legajo->catalogo) }}" />
                      <label for="catalogo">Catalogo</label>
                    </div>
                  </div> -->

                  <!-- <div class="row">
                  </div> -->

                  <div class="col-md-6">
                    <div class="form-floating {{ $outline }}">
                      <select id="grupo_emp" name="grupo_emp" class="select2 form-select" data-allow-clear="true">

                          <option value="">(Seleccione un integrante del grupo empresario)</option>
                          @foreach ($grupos as $grupo)
                              <option value = "{{ $grupo->codigo  }}"
                                @if ($legajo->grupo_emp == $grupo->codigo)  selected   @endif  >
                                  {{ $grupo->codigo  }} - {{ $grupo->detalle }}
                              </option>
                          @endforeach
                        </select>
                      </select>
                      <label for="grupo_emp">Grupo empresario</label>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-floating {{ $outline }}">
                      <select id="cod_centro" name="cod_centro" class="select2 form-select" data-allow-clear="true"
                          {{ $edicion?'':'disabled' }}
                          {{ $agregar?'enabled':'' }}>

                          <option value="">(Seleccione un centro de costos)</option>
                          @foreach ($ccostos as $ccosto)
                              <option value = "{{ $ccosto->codigo  }}"
                                @if ($legajo->cod_centro == $ccosto->codigo)  selected   @endif  >
                                  {{ $ccosto->codigo  }} - {{ $ccosto->detalle }}
                              </option>
                          @endforeach
                        </select>
                      </select>
                      <label for="cod_centro">Centro de costos</label>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-floating {{ $outline }}">
                      <select id="cod_jerarq" name="cod_jerarq" class="select2 form-select" data-allow-clear="true"
                          {{ $edicion?'':'disabled' }}
                          {{ $agregar?'enabled':'' }}>

                          <option value="">(Seleccione la jerarquia)</option>
                          @foreach ($jerarquias as $jerarquia)
                              <option value = "{{ $jerarquia->codigo  }}"
                                @if ($legajo->cod_jerarq == $jerarquia->codigo)  selected   @endif  >
                                  {{ $jerarquia->codigo  }} - {{ $jerarquia->detalle }}
                              </option>
                          @endforeach
                        </select>
                      </select>
                      <label for="cod_jerarq">Jerarquia</label>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-floating {{ $outline }}">
                      <select id="codsector" name="codsector" class="select2 form-select" data-allow-clear="true"
                          {{ $edicion?'':'disabled' }}
                          {{ $agregar?'enabled':'' }}>

                          <option value="">(Seleccione la jerarquia)</option>
                          @foreach ($sectores as $sector)
                              <option value = "{{ $sector->codigo  }}"
                                @if ($legajo->codsector == $sector->codigo)  selected   @endif  >
                                  {{ $sector->codigo  }} - {{ $sector->detalle }}
                              </option>
                          @endforeach
                        </select>
                      </select>
                      <label for="codsector">Sector</label>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-floating {{ $outline }}">
                      <input type="number" id="funcion" name="funcion" class="form-control" placeholder="Cantidad máxima del artículo permitida" aria-label="0.000"
                        {{ $edicion?'':'disabled' }}
                        {{ $agregar?'enabled':'' }}
                        value="{{ old('funcion',$legajo->funcion) }}" />
                      <label for="funcion">Tarea</label>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-floating {{ $outline }}">
                      <select id="cuadrilla" name="cuadrilla" class="select2 form-select" data-allow-clear="true"
                          {{ $edicion?'':'disabled' }}
                          {{ $agregar?'enabled':'' }}>

                          <option value="">(Seleccione la jerarquia)</option>
                          @foreach ($cuadrillas as $cuadrilla)
                              <option value = "{{ $cuadrilla->codigo  }}"
                                @if ($legajo->cuadrilla == $cuadrilla->codigo)  selected   @endif  >
                                  {{ $cuadrilla->codigo  }} - {{ $cuadrilla->detalle }}
                              </option>
                          @endforeach
                        </select>
                      </select>
                      <label for="cuadrilla">Cuadrilla</label>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-floating {{ $outline }}">
                      <select id="cuadrilla" name="cuadrilla" class="select2 form-select" data-allow-clear="true"
                          {{ $edicion?'':'disabled' }}
                          {{ $agregar?'enabled':'' }}>

                          <option value="">(Seleccione la jerarquia)</option>
                          @foreach ($cuadrillas as $cuadrilla)
                              <option value = "{{ $cuadrilla->codigo  }}"
                                @if ($legajo->cuadrilla == $cuadrilla->codigo)  selected   @endif  >
                                  {{ $cuadrilla->codigo  }} - {{ $cuadrilla->detalle }}
                              </option>
                          @endforeach
                        </select>
                      </select>
                      <label for="cuadrilla">Obra social</label>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-floating {{ $outline }}">
                      <select id="cuadrilla" name="cuadrilla" class="select2 form-select" data-allow-clear="true"
                          {{ $edicion?'':'disabled' }}
                          {{ $agregar?'enabled':'' }}>

                          <option value="">(Seleccione la jerarquia)</option>
                          @foreach ($cuadrillas as $cuadrilla)
                              <option value = "{{ $cuadrilla->codigo  }}"
                                @if ($legajo->cuadrilla == $cuadrilla->codigo)  selected   @endif  >
                                  {{ $cuadrilla->codigo  }} - {{ $cuadrilla->detalle }}
                              </option>
                          @endforeach
                        </select>
                      </select>
                      <label for="cuadrilla">Sindicato</label>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-floating {{ $outline }}">
                      <select id="cuadrilla" name="cuadrilla" class="select2 form-select" data-allow-clear="true"
                          {{ $edicion?'':'disabled' }}
                          {{ $agregar?'enabled':'' }}>

                          <option value="">(Seleccione la jerarquia)</option>
                          @foreach ($cuadrillas as $cuadrilla)
                              <option value = "{{ $cuadrilla->codigo  }}"
                                @if ($legajo->cuadrilla == $cuadrilla->codigo)  selected   @endif  >
                                  {{ $cuadrilla->codigo  }} - {{ $cuadrilla->detalle }}
                              </option>
                          @endforeach
                        </select>
                      </select>
                      <label for="cuadrilla">Situación</label>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-floating {{ $outline }}">
                      <select id="cuadrilla" name="cuadrilla" class="select2 form-select" data-allow-clear="true"
                          {{ $edicion?'':'disabled' }}
                          {{ $agregar?'enabled':'' }}>

                          <option value="">(Seleccione la jerarquia)</option>
                          @foreach ($cuadrillas as $cuadrilla)
                              <option value = "{{ $cuadrilla->codigo  }}"
                                @if ($legajo->cuadrilla == $cuadrilla->codigo)  selected   @endif  >
                                  {{ $cuadrilla->codigo  }} - {{ $cuadrilla->detalle }}
                              </option>
                          @endforeach
                        </select>
                      </select>
                      <label for="cuadrilla">Situación</label>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-floating {{ $outline }}">
                      <select id="cuadrilla" name="cuadrilla" class="select2 form-select" data-allow-clear="true"
                          {{ $edicion?'':'disabled' }}
                          {{ $agregar?'enabled':'' }}>

                          <option value="">(Seleccione la jerarquia)</option>
                          @foreach ($cuadrillas as $cuadrilla)
                              <option value = "{{ $cuadrilla->codigo  }}"
                                @if ($legajo->cuadrilla == $cuadrilla->codigo)  selected   @endif  >
                                  {{ $cuadrilla->codigo  }} - {{ $cuadrilla->detalle }}
                              </option>
                          @endforeach
                        </select>
                      </select>
                      <label for="cuadrilla">Situación</label>
                    </div>
                  </div>

                </div>
                <br><br><br>


              </div>
              <div class="tab-pane fade" id="form-tabs-account" role="tabpanel">

                  <div class="row g-6">
                   <div class="col-md-4">
                        <div class="form-floating {{ $outline }}">
                            <input type="number" id="precio_uc" name="precio_uc" class="form-control" placeholder="15 caracteres max" maxlength="15"
                            {{ $edicion?'':'disabled' }}
                            {{ $agregar?'enabled':'' }}
                            value="{{ old('precio_uc',$legajo->precio_uc) }}" />
                            <label for="precio_uc">Precio ultima compra</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating {{ $outline }}">
                            <input type="number" id="precio_rep" name="precio_rep" class="form-control" placeholder="15 caracteres max" maxlength="15"
                            {{ $edicion?'':'disabled' }}
                            {{ $agregar?'enabled':'' }}
                            value="{{ old('precio_rep',$legajo->precio_rep) }}" />
                            <label for="precio_rep">Precio reposicion</label>
                        </div>
                    </div>
                  </div>
                  {{-- <div class="pt-6">
                    <button type="submit" class="btn btn-primary me-4">Submit</button>
                    <button type="reset" class="btn btn-outline-secondary">Cancel</button>
                  </div> --}}

              </div>

              {{-- <div class="tab-pane fade" id="form-tabs-social" role="tabpanel">

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

              </div> --}}
            </div>
          </div>

            <a href="/import-articulos" type="button" class="btn btn-label-success waves-effect">
                <span class="tf-icons ri-file-excel-2-line ri-16px me-2"></span>Importar desde excel
            </a>

        </div>
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
            <a href="{{ asset('/artic-insumos/delete') }}/{{ $legajo->id }}" type="button" class="btn btn-danger waves-effect waves-light" style="color: white">Borrar</a>

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

@endsection

