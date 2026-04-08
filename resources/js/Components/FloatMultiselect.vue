<script setup>
import Multiselect from '@vueform/multiselect';

const props = defineProps({
    modelValue: { default: '' },
    options:    { type: Array, required: true },
    label:      { type: String, required: true },
    id:         { type: String, default: '' },
    disabled:   { type: Boolean, default: false },
    error:      { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div class="ms-float-outline">
        <Multiselect
            :id="id"
            :name="id"
            :model-value="modelValue"
            @update:model-value="emit('update:modelValue', $event)"
            :options="options"
            :searchable="true"
            :can-clear="true"
            placeholder=""
            no-options-text="Sin opciones"
            no-results-text="Sin resultados"
            :disabled="disabled"
            :class="{ 'is-invalid': error }"
        />
        <label :for="id">{{ label }}</label>
    </div>
    <div class="text-danger small mt-1" v-if="error">{{ error }}</div>
</template>

<style src="@vueform/multiselect/themes/default.css"></style>
<style>
/* ── Multiselect con label flotante, estilo form-floating-outline de Materialize ── */

.ms-float-outline {
    position: relative;
}

.ms-float-outline .multiselect {
    --ms-border-color:                  #cfd0d6;
    --ms-border-color-active:           #666cff;
    --ms-border-width:                  1px;
    --ms-border-width-active:           2px;
    --ms-radius:                        0.5rem;
    --ms-font-size:                     0.9375rem;
    --ms-line-height:                   1.5;
    --ms-bg:                            transparent;
    --ms-bg-disabled:                   transparent;
    --ms-option-bg-selected:            #666cff;
    --ms-option-bg-selected-pointed:    #666cff;
    --ms-option-color-selected:         #fff;
    --ms-option-color-selected-pointed: #fff;
    height: calc(3.0000625rem + 2px);
}

.ms-float-outline .multiselect-wrapper {
    align-self: stretch;
    min-height: 0;
}

.ms-float-outline .multiselect ~ label {
    position: absolute;
    top: 0;
    left: 0;
    z-index: 3;
    pointer-events: none;
    width: auto;
    height: auto;
    padding: 0 0.25rem;
    margin: -0.6rem 0.75rem 0;
    font-size: 0.75rem;
    line-height: 1.375;
    color: #a8aab4;
    background: var(--bs-card-bg);
    transition: color 0.15s ease-in-out;
}

.ms-float-outline .multiselect.is-open ~ label,
.ms-float-outline .multiselect.is-active ~ label {
    color: #666cff;
}

.ms-float-outline .multiselect.is-invalid {
    --ms-border-color:        #ff3e1d;
    --ms-border-color-active: #ff3e1d;
}
.ms-float-outline .multiselect.is-invalid ~ label {
    color: #ff3e1d;
}

.ms-float-outline .multiselect.is-disabled {
    opacity: 0.65;
    --ms-color: #000;
}
.ms-float-outline .multiselect.is-disabled .multiselect-single-label,
.ms-float-outline .multiselect.is-disabled .multiselect-placeholder {
    color: #000 !important;
}
</style>
