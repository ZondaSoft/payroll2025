/**
 * File Upload
 */

'use strict';

(function () {
  // previewTemplate: Updated Dropzone default previewTemplate
  // ! Don't change it unless you really know what you are doing
  const previewTemplate = `<div class="dz-preview dz-file-preview">
<div class="dz-details">
  <div class="dz-thumbnail">
    <img data-dz-thumbnail>
    <span class="dz-nopreview">No preview</span>
    <div class="dz-success-mark"></div>
    <div class="dz-error-mark"></div>
    <div class="dz-error-message"><span data-dz-errormessage></span></div>
    <div class="progress">
      <div class="progress-bar progress-bar-primary" role="progressbar" aria-valuemin="0" aria-valuemax="100" data-dz-uploadprogress></div>
    </div>
  </div>
  <div class="dz-filename" data-dz-name></div>
  <div class="dz-size" data-dz-size></div>
</div>
</div>`;

  // ? Start your code from here

  // Basic Dropzone
  // --------------------------------------------------------------------
  const dataTransfer = new DataTransfer();

  const dropzoneBasic = document.querySelector('#dropzone-basic');

  // Configurar Dropzone
  Dropzone.autoDiscover = false; // Deshabilitar auto-descubrimiento de Dropzone

  if (dropzoneBasic) {
    const myDropzone = new Dropzone(dropzoneBasic, {
      paramName: "file", // Nombre del parámetro que contendrá el archivo
      previewTemplate: previewTemplate,
      parallelUploads: 1,
      maxFilesize: 3,
      addRemoveLinks: true,
      maxFiles: 1,
      acceptedFiles: "image/jpeg,image/png", // Tipos de archivo permitidos
      autoProcessQueue: false,
      dictRemoveFile: "Eliminar archivo",
        init: function() {
            this.on("addedfile", function(file) {
                console.log("Archivo añadido:", file.name);

                // Agregar los archivos subidos en Dropzone al DataTransfer
                dataTransfer.items.add(file);

                console.log(dataTransfer.files);

                // Asignar los archivos al campo <input>
                const inputArchivos = document.getElementById('archivos');
                inputArchivos.files = dataTransfer.files;
            });
            this.on("removedfile", function(file) {
                console.log("Archivo eliminado:", file.name);
            });
        }
    });
  }

  // Multiple Dropzone
  // --------------------------------------------------------------------
  const dropzoneMulti = document.querySelector('#dropzone-multi');
  if (dropzoneMulti) {
    const myDropzoneMulti = new Dropzone(dropzoneMulti, {
      previewTemplate: previewTemplate,
      parallelUploads: 1,
      maxFilesize: 5,
      addRemoveLinks: true
    });
  }
})();
