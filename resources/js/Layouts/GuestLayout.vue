<script setup>
    import ApplicationLogo from '@/Components/ApplicationLogo.vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        imageUrl: {
            type: String,
            default: 'img/welcome.jpeg', // Cambiá la ruta a la imagen que quieras
        },
        imageAlt: {
            type: String,
            default: 'Imagen de fondo',
        },
    });
</script>

<template>
    <div class="login-wrapper">
        <div class="row no-margin no-padding">

            <!-- Columna izquierda: formulario -->
            <div class="col s12 m6 left-side valign-wrapper logo-area">
                <div class="form-container">

                    <div class="logo-container center-align">
                        <Link href="/">
                            <ApplicationLogo />
                        </Link>
                    </div>

                    <div class="row">
                        <h3 class="login-title" style="margin-top: 0px;">Payroll 2025</h3>
                    </div>

                    <!-- Acá entra el formulario -->
                    <slot />
                </div>
            </div>

            <!-- Columna derecha: imagen (oculta en mobile) -->
            <div class="col s12 m6 right-side hide-on-small-only">
                <img :src="props.imageUrl" :alt="props.imageAlt" class="right-image" />
            </div>
        </div>
    </div>
</template>

<style scoped>
    .login-wrapper {
        min-height: 100vh;
        background: #fafafa;
    }

    .logo-area {
        position: relative; /* El logo se posiciona respecto a esta columna */
    }

    /* El logo queda SIEMPRE a 90px del borde superior */
    .logo-container {
        /* position: absolute; */
        padding-top: 60px;
        left: 100px; /* mismo alineado que tus inputs */
    }

    .login-title {
        /* position: absolute; */
        font-size: 30px;
        color: #000;
        padding-top: 50px;
        margin-bottom: 30px;
    }

    /* Elimina márgenes heredados */
    .no-margin {
        margin: 0 !important;
    }
    .no-padding {
        padding: 0 !important;
    }

    /* 🟦 Lado izquierdo */
    .left-side {
        display: -webkit-box;
        display: flex;
        -webkit-box-orient: vertical;
        -webkit-box-direction: normal;
                flex-direction: column;
        background-color: #fff;
        justify-content: center;
        align-items: flex-start;
        padding: 40px 0 40px 85px !important;
        height: 100vh;
    }

    @media (max-width: 991px) {
        .login-section-wrapper {
        padding-left: 50px;
        padding-right: 50px; } }

    @media (max-width: 575px) {
        .login-section-wrapper {
        padding-top: 20px;
        padding-bottom: 20px;
        min-height: 100vh; }
    }

    .form-container {
        width: 100%;
        max-width: 380px;
    }

    /* Mobile: margin normal para que no quede raro */
    @media (max-width: 600px) {
        .form-container {
            margin-left: 0;
        }

        .left-side {
            padding-left: 20px;
            padding-right: 20px;
        }

        .logo-container {
            padding-top: 40px;   /* más razonable en pantallas chicas */
        }
    }

    /* Inputs Materialize sin bordes visibles */
    input[type="text"],
    input[type="password"],
    input[type="email"] {
        border-bottom: 1px solid #ccc !important;
        box-shadow: none !important;
        padding-left: 0 !important;
    }

    input:focus {
        border-bottom: 2px solid #1976d2 !important; /* un toque de color moderno */
        box-shadow: none !important;
    }

    /* 🟩 Lado derecho */
    .right-side {
        padding: 0 !important;
        height: 100vh;
        overflow: hidden;
    }

    /* Imagen ocupa toda la media pantalla */
    .right-image {
        width: 100%;
        height: 100%;
        object-fit: cover;       /* llena toda la superficie */
        display: block;
    }
</style>
