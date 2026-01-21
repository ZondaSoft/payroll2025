<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div v-if="message" class="invalid-feedback d-block">
        {{ message }}
    </div>
</template>
