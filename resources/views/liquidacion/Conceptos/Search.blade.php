@extends('layouts.main')

@section('styles')
<style>
    .table-responsive {
        border-radius: 0.25rem;
    }
    .badge {
        padding: 0.35rem 0.65rem;
    }
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Encabezado -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="d-flex flex-column justify-content-center">
            <h4 class="mb-1">Conceptos de Liquidación</h4>
            <p class="mb-0">Búsqueda de conceptos para liquidación de sueldos</p>
        </div>
        <div class="d-flex align-items-center flex-wrap row-gap-3">
            <a href="{{ route('liquidacion.conceptos.create') }}" class="btn btn-primary">
                <span class="tf-icons ri-add-line"></span>&nbsp; Nuevo Concepto
            </a>
            <a href="{{ route('liquidacion.conceptos.index') }}" class="btn btn-secondary">
                <span class="tf-icons ri-list-view"></span>&nbsp; Ver Todos
            </a>
        </div>
    </div>

    <!-- Card de búsqueda -->
    <div class="card mb-6">
        <div class="card-body">
            <form method="GET" action="{{ route('liquidacion.conceptos.search') }}" class="row g-3">
                <div class="col-md-8">
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="ri-search-line"></i></span>
                        <input 
                            type="text" 
                            class="form-control" 
                            placeholder="Buscar por descripción o código..." 
                            name="search"
                            value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <span class="tf-icons ri-search-line"></span>&nbsp; Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de resultados -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Resultados de Búsqueda</h5>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Tipo</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($conceptos as $concepto)
                        <tr>
                            <td>
                                <strong>#{{ $concepto->codigo }}</strong>
                            </td>
                            <td>{{ $concepto->detalle }}</td>
                            <td>
                                <span class="badge bg-label-info">
                                    {{ $concepto->tipo_nombre }}
                                </span>
                            </td>
                            <td>
                                @if($concepto->activo)
                                    <span class="badge bg-label-success">Activo</span>
                                @else
                                    <span class="badge bg-label-danger">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill" data-bs-toggle="dropdown">
                                        <i class="ri-more-2-line"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end m-0">
                                        <a href="{{ route('liquidacion.conceptos.show', $concepto->id) }}" class="dropdown-item">
                                            <i class="ri-eye-line me-2"></i> Ver
                                        </a>
                                        <a href="{{ route('liquidacion.conceptos.edit', $concepto->id) }}" class="dropdown-item">
                                            <i class="ri-edit-line me-2"></i> Editar
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <form method="POST" action="{{ route('liquidacion.conceptos.destroy', $concepto->id) }}" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger" onclick="return confirm('¿Estás seguro?');">
                                                <i class="ri-delete-bin-line me-2"></i> Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <p class="text-muted mb-0">No se encontraron resultados</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($conceptos->hasPages())
            <div class="card-footer">
                {{ $conceptos->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
