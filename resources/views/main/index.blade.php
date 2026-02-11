@extends('layouts.main')

@section('styles')
<style>
  body {
      margin: 0;
      height: 200vh; /* Para demostrar desplazamiento */
  }
  .lineas {
      position: absolute; /* O usa 'absolute' si prefieres */
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none; /* Esto hace que la línea no interfiera con los eventos de ratón */
      z-index: 1000; /* Asegura que esté sobre otros elementos */
  }
  .arrow {
      width: 0;
      height: 0;
      top: 57;
      left: 86;
      border-left: 5px solid transparent;
      border-right: 5px solid transparent;
      border-bottom: 10px solid rgba(38, 43, 67, 0.065);
      position: absolute;
      pointer-events: none; /* Esto hace que la línea no interfiera con los eventos de ratón */
      z-index: 1000; /* Asegura que esté sobre otros elementos */
      transform: rotate(180deg); /* Rotar 90 grados hacia la derecha */
  }

  .arrow-line {
        position: relative;
        width: 100%;
        height: 2px; /* Grosor de la línea */
        top: 50%;
        background-color: rgba(38, 43, 67, 0.065); /* Color de la línea */
        z-index: 1000; /* Asegura que esté sobre otros elementos */
    }

    .arrow-line::after {
        content: '';
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        border: 5px solid transparent; /* Tamaño de la flecha */
        border-left-color: rgba(38, 43, 67, 0.065); /* Color de la flecha */
    }

    .arrow-line-left {
        position: relative;
        width: 100%;
        height: 2px; /* Grosor de la línea */
        top: 50%;
        background-color: rgba(38, 43, 67, 0.065); /* Color de la línea */
        z-index: 1000; /* Asegura que esté sobre otros elementos */
    }

    .arrow-line-left::after {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        border: 5px solid transparent; /* Tamaño de la flecha */
        border-right-color: rgba(38, 43, 67, 0.065); /* Color de la flecha */
    }
</style>
@endsection

<!-- Content -->
@section('content')

<!-- Content -->

<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row g-6">

    <!-- SERVICIOS -->
    <!-- @ if(auth()->user()->can('services.index')) -->
    <div class="col-12 col-xxl-8">
      <div class="card h-100">
        <div class="row row-bordered g-0 h-100">
            <div class="col-md-12 col-12 order-2 order-md-0">
                <div class="card-header" style="padding-bottom: 0px;padding-top: 10px;">
                <!-- {{-- <h5 class="mb-0">Total Transactions</h5> --}} -->
                <h5 class="card-title mb-4">Legajos</h5>
                </div>

                <div class="card-body pt-6">
                    <div class="row" style="margin-left: 0px">
                        <!-- @ can('services.infdrivers') -->
                            <div class="col-2" id="btnInformeCond" name="btnInformeCond">
                                <div class="d-flex flex-column align-items-center">
                                <div class="avatar">
                                    <a href="/legajos" class="avatar-initial bg-label-primary rounded-3">
                                    <i class="ri-user-line ri-24px"></i>
                                    </div>
                                </a>
                                <p class="mt-3 mb-1 text-center">Empleados activos</p>
                                </div>
                            </div>

                            <!-- <div class="col-1 arrow-line" style="margin-top: 20px;">
                            </div> -->
                        <!-- @ endcan -->
                        <!-- @ can('services.balanza') -->
                            <div class="col-2" id="btnImportarSicoss" name="btnImportarSicoss">
                                <div class="d-flex flex-column align-items-center">
                                <div class="avatar">
                                    <a href="/sicoss/importar" class="avatar-initial bg-label-primary rounded-3">
                                    <i class="ri-download-line ri-24px"></i>
                                    </div>
                                </a>
                                <p class="mt-3 mb-1 text-center">Importación Sicoss</p>
                                </div>
                            </div>

                            <div class="col-1 arrow-line" style="margin-top: 20px;" hidden>
                            </div>
                        <!-- @ endcan -->

                            <div class="col-2" id="btnImportarSicoss" name="btnImportarSicoss">
                                <div class="d-flex flex-column align-items-center">
                                <div class="avatar">
                                    <a href="/arca/importar" class="avatar-initial bg-label-success rounded-3">
                                    <i class="ri-download-line ri-24px"></i>
                                    </div>
                                </a>
                                <p class="mt-3 mb-1 text-center">Importar conceptos desde ARCA</p>
                                </div>
                            </div>

                            <div class="col-2" id="btnImportarSicoss" name="btnImportarSicoss">
                                <div class="d-flex flex-column align-items-center">
                                <div class="avatar">
                                    <a href="/basedat/importar" class="avatar-initial bg-label-warning rounded-3">
                                    <i class="ri-download-line ri-24px"></i>
                                    </div>
                                </a>
                                <p class="mt-3 mb-1 text-center">Importar Liquidación de haberes</p>
                                </div>
                            </div>

                            <!-- @ can('services.requisition') -->
                            <div class="col-2" id="btnRequisicionServ" name="btnRequisicionServ">
                                <div class="d-flex flex-column align-items-center">
                                <a href="/lsd/generar" class="avatar">
                                    <div class="avatar-initial bg-label-primary rounded-3">
                                    <i class="ri-file-text-line ri-24px"></i>
                                    </div>
                                </a>
                                <p class="mt-3 mb-1 text-center">Generación Libro sueldo Digital</p>
                                </div>
                            </div>

                            <div class="col-1 arrow-line" style="margin-top: 20px;" hidden>
                            </div>
                            <!-- @ endcan -->

                            <!-- @ can('services.ordenes') -->
                                <!-- <div class="col-2" id="btnRequisicionServ" name="btnRequisicionServ">
                                    <div class="d-flex flex-column align-items-center">
                                    <a href="/servicios/orden-serv1" class="avatar">
                                        <div class="avatar-initial bg-label-primary rounded-3">
                                        <div class="ri-money-dollar-circle-line ri-24px"></div>
                                        </div>
                                    </a>
                                    <p class="mt-3 mb-1 text-center">Ordenes de servicio</p>
                                    </div>
                                </div> -->
                            <!-- @ endcan -->
                    </div>

                </div>

            </div>



        </div>
      </div>
    </div>
    <!-- @ endif -->
    <!--/ SERVICIOS -->
  </div>
</div>

@endsection
<!-- / Content -->

@section('scripts')
<script>
  function adjustLine() {
    let button1 = document.getElementById('btnNovedadesTaller');
    let button2 = document.getElementById('btnBandejaTaller');
    let line = document.getElementById('linea1');

    // Solo seguir si los elementos existen
    if (button1 && button2 && line) {
      let rect1 = button1.getBoundingClientRect();
      let rect2 = button2.getBoundingClientRect();

      let x1 = rect1.left + (rect1.width / 2) - 16;
      let y1 = rect1.top + (rect1.height / 2) + 28;
      let x2 = rect2.left + (rect2.width / 2) - 15;
      let y2 = rect2.top - 56;

      // line.setAttribute('x1', x1);
      // line.setAttribute('y1', y1);
      // line.setAttribute('x2', x2);
      // line.setAttribute('y2', y2);
    }

    //------------------------------------------------------
    let button3 = document.getElementById('btnRequisicionServ');
    let button4 = document.getElementById('btnRequisicionTaller');
    let line2 = document.getElementById('linea2');

    // Solo seguir si los elementos existen
    if (button3 && button4 && line2) {
      let rect3 = button3.getBoundingClientRect();
      let rect4 = button4.getBoundingClientRect();

      let xx1 = rect3.left + (rect3.width / 2) - 16;
      let yy1 = rect3.top + (rect3.height / 2) + 28;
      let xx2 = rect4.left + (rect4.width / 2) - 15;
      let yy2 = rect4.top - 56;

      line2.setAttribute('x1', xx1);
      line2.setAttribute('y1', yy1);
      line2.setAttribute('x2', xx2);
      line2.setAttribute('y2', yy2);
    }

    button3 = document.getElementById('btnRequisicionTaller');
    button4 = document.getElementById('btnIngresoIns');
    line3 = document.getElementById('linea3');

    if (button3 && button4 && line3) {
      let rect3 = button3.getBoundingClientRect();
      let rect4 = button4.getBoundingClientRect();

      let xx1 = rect3.left + (rect3.width / 2) - 16;
      let yy1 = rect3.top + (rect3.height / 2) + 28;
      let xx2 = rect4.left + (rect4.width / 2) - 15;
      let yy2 = rect4.top - 66;

      line3.setAttribute('x1', xx1);
      line3.setAttribute('y1', yy1);
      line3.setAttribute('x2', xx2);
      line3.setAttribute('y2', yy2);
    }

    //------------------------------------------------------
    button5 = document.getElementById('btnIngresoIns');
    button6 = document.getElementById('btnEntregaIns');
    line4 = document.getElementById('linea4');

    // Solo seguir si los elementos existen
    if (button5 && button6 && line4) {
      let rect5 = button5.getBoundingClientRect();
      let rect6 = button6.getBoundingClientRect();

      let xx1 = rect5.left + (rect5.width / 2) - 16;
      let yy1 = rect5.top + (rect5.height / 2) + 28;
      let xx2 = rect6.left + (rect6.width / 2) - 15;
      let yy2 = rect6.top - 56;
    }

    // line4.setAttribute('x1', xx1);
    // line4.setAttribute('y1', yy1);
    // line4.setAttribute('x2', xx2);
    // line4.setAttribute('y2', yy2);

    //------------------------------------------------------
    button5 = document.getElementById('btnRequisicionPanol');
    button6 = document.getElementById('btnOrderCpa');
    //line5 = document.getElementById('linea5');

    // Solo seguir si los elementos existen
    if (button5 && button6) {
      let rect5 = button5.getBoundingClientRect();
      let rect6 = button6.getBoundingClientRect();

      let xx1 = rect5.left + (rect5.width / 2) - 15;
      let yy1 = rect5.top + (rect5.height / 2) + 50;
      let xx2 = rect6.left + (rect6.width / 2) - 15;
      let yy2 = rect6.top - 87;

      //line5.setAttribute('x1', xx1);
      //line5.setAttribute('y1', yy1);
      //line5.setAttribute('x2', xx1);
      //line5.setAttribute('y2', yy2);

      //---------------------------------------------------------
      var arrow = document.getElementById("arrow");

      // Define la posición específica
      var position = {
          top: 47,  // Posición desde la parte superior de la página
          left: xx1 - 276  // Posición desde la parte izquierda de la página
      };
    }
  }

  // Ajustar la línea al cargar la página y al redimensionar la ventana
  window.addEventListener('load', adjustLine);
  window.addEventListener('resize', adjustLine);
</script>
@endsection
