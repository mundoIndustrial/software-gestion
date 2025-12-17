@extends('layouts.insumos.app')

@section('page-title', 'Dashboard Insumos')

@section('content')
<div class="container-fluid">
    
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Materiales</h5>
                    <p class="card-text h3">{{ $total ?? 0 }}</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">No Iniciado</h5>
                    <p class="card-text h3">{{ $estados['no_iniciado'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">En Ejecución</h5>
                    <p class="card-text h3">{{ $estados['en_ejecucion'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Anulada</h5>
                    <p class="card-text h3">{{ $estados['anulada'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
