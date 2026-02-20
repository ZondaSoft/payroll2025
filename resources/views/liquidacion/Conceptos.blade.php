@extends('layouts.main')

@section('styles')
<style>
    .form-control {
        background-color: #ffffff !important;
    }
    .form-floating {
        margin-bottom: 1.5rem;
    }
    .btn-action {
        margin-right: 0.5rem;
    }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Encabezado -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="d-flex flex-column justify-content-center">
            <h4 class="mb-1">Conceptos de Liquidación</h4>
            <p class="mb-0">Gestión de conceptos para liquidación de sueldos</p>
        </div>
        <div class="d-flex align-items-center flex-wrap row-gap-3">
            @if($edicion == false)
                <a href="{{ route('liquidacion.conceptos.create') }}" class="btn btn-primary">
                    <span class="tf-icons ri-add-line"></span>&nbsp; Agregar Concepto
                </a>
            @endif
        </div>
    </div>

    <!-- Card principal -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ $edicion ? ($agregar ? 'Nuevo Concepto' : 'Editar Concepto') : 'Detalle del Concepto' }}</h5>
                @if($edicion == false && $concepto->id)
                    <div class="btn-group" role="group">
                        <a href="{{ route('liquidacion.conceptos.first') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="ri-skip-back-line"></i>
                        </a>
                        <a href="{{ route('liquidacion.conceptos.previous', $concepto->id) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="ri-arrow-left-s-line"></i>
                        </a>
                        <a href="{{ route('liquidacion.conceptos.next', $concepto->id) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="ri-arrow-right-s-line"></i>
                        </a>
                        <a href="{{ route('liquidacion.conceptos.last') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="ri-skip-forward-line"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h6 class="alert-heading mb-1">Errores encontrados</h6>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Formulario -->
            <form method="POST" action="{{ $edicion ? ($agregar ? route('liquidacion.conceptos.store') : route('liquidacion.conceptos.update', $concepto->id)) : '#' }}" id="conceptoForm">
                @csrf
                @if(!$agregar && $edicion)
                    @method('PUT')
                @endif

                <div class="row">
                    <!-- Código -->
                    <div class="col-md-4 form-floating">
                        <input 
                            type="number" 
                            class="form-control @error('codigo') is-invalid @enderror" 
                            id="codigo" 
                            name="codigo" 
                            placeholder="Código" 
                            value="{{ old('codigo', $concepto->codigo ?? '') }}"
                            {{ $edicion ? '' : 'disabled' }}
                            {{ $agregar ? '' : 'disabled' }}
                            required>
                        <label for="codigo">Código *</label>
                        @error('codigo')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Tipo -->
                    <div class="col-md-4 form-floating">
                        <select 
                            class="form-control form-select @error('tipo') is-invalid @enderror" 
                            id="tipo" 
                            name="tipo"
                            {{ $edicion ? '' : 'disabled' }}
                            required>
                            <option value="">Seleccionar tipo</option>
                            @foreach(\App\Models\Sue102::TIPOS as $key => $value)
                                <option value="{{ $key }}" {{ (old('tipo', $concepto->tipo ?? '') == $key) ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                            @endforeach
                        </select>
                        <label for="tipo">Tipo *</label>
                        @error('tipo')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Activo -->
                    <div class="col-md-4">
                        <div class="form-check form-switch mt-2">
                            <input 
                                class="form-check-input" 
                                type="checkbox" 
                                id="activo" 
                                name="activo"
                                value="1"
                                {{ old('activo', $concepto->activo ?? true) ? 'checked' : '' }}
                                {{ $edicion ? '' : 'disabled' }}>
                            <label class="form-check-label" for="activo">Activo</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Detalle -->
                    <div class="col-md-12 form-floating">
                        <input 
                            type="text" 
                            class="form-control @error('detalle') is-invalid @enderror" 
                            id="detalle" 
                            name="detalle" 
                            placeholder="Descripción del concepto" 
                            value="{{ old('detalle', $concepto->detalle ?? '') }}"
                            {{ $edicion ? '' : 'disabled' }}
                            maxlength="250"
                            required>
                        <label for="detalle">Descripción *</label>
                        @error('detalle')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="mb-4">Valores del Concepto</h6>

                <div class="row">
                    <!-- Porcentaje -->
                    <div class="col-md-6 form-floating">
                        <input 
                            type="number" 
                            class="form-control @error('porcentaje') is-invalid @enderror" 
                            id="porcentaje" 
                            name="porcentaje" 
                            placeholder="Porcentaje" 
                            value="{{ old('porcentaje', $concepto->porcentaje ?? '') }}"
                            {{ $edicion ? '' : 'disabled' }}
                            step="0.01"
                            min="0"
                            max="100">
                        <label for="porcentaje">Porcentaje (%)</label>
                        @error('porcentaje')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Importe Fijo -->
                    <div class="col-md-6 form-floating">
                        <input 
                            type="number" 
                            class="form-control @error('importe_fijo') is-invalid @enderror" 
                            id="importe_fijo" 
                            name="importe_fijo" 
                            placeholder="Importe fijo" 
                            value="{{ old('importe_fijo', $concepto->importe_fijo ?? '') }}"
                            {{ $edicion ? '' : 'disabled' }}
                            step="0.01"
                            min="0">
                        <label for="importe_fijo">Importe Fijo ($)</label>
                        @error('importe_fijo')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <!-- Fórmula -->
                    <div class="col-md-12 form-floating">
                        <textarea 
                            class="form-control @error('formula') is-invalid @enderror" 
                            id="formula" 
                            name="formula" 
                            placeholder="Fórmula de cálculo"
                            {{ $edicion ? '' : 'disabled' }}
                            rows="3"></textarea>
                        <label for="formula">Fórmula de Cálculo</label>
                        @error('formula')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="mb-4">Configuración de Afectaciones</h6>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input 
                                class="form-check-input" 
                                type="checkbox" 
                                id="imponible" 
                                name="imponible"
                                value="1"
                                {{ old('imponible', $concepto->imponible ?? true) ? 'checked' : '' }}
                                {{ $edicion ? '' : 'disabled' }}>
                            <label class="form-check-label" for="imponible">Imponible</label>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input 
                                class="form-check-input" 
                                type="checkbox" 
                                id="afecta_sac" 
                                name="afecta_sac"
                                value="1"
                                {{ old('afecta_sac', $concepto->afecta_sac ?? true) ? 'checked' : '' }}
                                {{ $edicion ? '' : 'disabled' }}>
                            <label class="form-check-label" for="afecta_sac">Afecta SAC</label>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input 
                                class="form-check-input" 
                                type="checkbox" 
                                id="afecta_vacaciones" 
                                name="afecta_vacaciones"
                                value="1"
                                {{ old('afecta_vacaciones', $concepto->afecta_vacaciones ?? true) ? 'checked' : '' }}
                                {{ $edicion ? '' : 'disabled' }}>
                            <label class="form-check-label" for="afecta_vacaciones">Afecta Vacaciones</label>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input 
                                class="form-check-input" 
                                type="checkbox" 
                                id="imprime_recibo" 
                                name="imprime_recibo"
                                value="1"
                                {{ old('imprime_recibo', $concepto->imprime_recibo ?? true) ? 'checked' : '' }}
                                {{ $edicion ? '' : 'disabled' }}>
                            <label class="form-check-label" for="imprime_recibo">Imprime Recibo</label>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input 
                                class="form-check-input" 
                                type="checkbox" 
                                id="sicoss_afecta" 
                                name="sicoss_afecta"
                                value="1"
                                {{ old('sicoss_afecta', $concepto->sicoss_afecta ?? false) ? 'checked' : '' }}
                                {{ $edicion ? '' : 'disabled' }}>
                            <label class="form-check-label" for="sicoss_afecta">Afecta Sicoss</label>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input 
                                class="form-check-input" 
                                type="checkbox" 
                                id="gcias_afecta" 
                                name="gcias_afecta"
                                value="1"
                                {{ old('gcias_afecta', $concepto->gcias_afecta ?? false) ? 'checked' : '' }}
                                {{ $edicion ? '' : 'disabled' }}>
                            <label class="form-check-label" for="gcias_afecta">Afecta Ganancias</label>
                        </div>
                    </div>

                    <div class="col-md-6 form-floating">
                        <input 
                            type="number" 
                            class="form-control @error('orden_impresion') is-invalid @enderror" 
                            id="orden_impresion" 
                            name="orden_impresion" 
                            placeholder="Orden de impresión" 
                            value="{{ old('orden_impresion', $concepto->orden_impresion ?? '') }}"
                            {{ $edicion ? '' : 'disabled' }}
                            min="0">
                        <label for="orden_impresion">Orden de Impresión</label>
                        @error('orden_impresion')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="mb-4">Información Contable</h6>

                <div class="row">
                    <!-- Cuenta Contable -->
                    <div class="col-md-6 form-floating">
                        <input 
                            type="text" 
                            class="form-control @error('cuenta_contable') is-invalid @enderror" 
                            id="cuenta_contable" 
                            name="cuenta_contable" 
                            placeholder="Cuenta contable" 
                            value="{{ old('cuenta_contable', $concepto->cuenta_contable ?? '') }}"
                            {{ $edicion ? '' : 'disabled' }}
                            maxlength="50">
                        <label for="cuenta_contable">Cuenta Contable</label>
                        @error('cuenta_contable')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <!-- Observaciones -->
                    <div class="col-md-12 form-floating">
                        <textarea 
                            class="form-control @error('observaciones') is-invalid @enderror" 
                            id="observaciones" 
                            name="observaciones" 
                            placeholder="Observaciones"
                            {{ $edicion ? '' : 'disabled' }}
                            rows="3"></textarea>
                        <label for="observaciones">Observaciones</label>
                        @error('observaciones')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="mt-6">
                    @if($edicion)
                        <button type="submit" class="btn btn-primary btn-action">
                            <span class="tf-icons ri-save-line"></span>&nbsp; Guardar
                        </button>
                        <a href="{{ route('liquidacion.conceptos.index') }}" class="btn btn-secondary btn-action">
                            <span class="tf-icons ri-close-line"></span>&nbsp; Cancelar
                        </a>
                    @else
                        @if($concepto->id)
                            <a href="{{ route('liquidacion.conceptos.edit', $concepto->id) }}" class="btn btn-warning btn-action">
                                <span class="tf-icons ri-edit-line"></span>&nbsp; Editar
                            </a>
                            <form method="POST" action="{{ route('liquidacion.conceptos.destroy', $concepto->id) }}" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-action" onclick="return confirm('¿Estás seguro de que deseas eliminar este concepto?');">
                                    <span class="tf-icons ri-delete-bin-line"></span>&nbsp; Eliminar
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('conceptoForm');
        
        @if(!$edicion && !$concepto->id)
            // Mostrar mensaje si no hay registros
        @endif
    });
</script>
@endsection
