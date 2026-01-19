<script setup>
/**
 * WP Jarvis - Vue App Component
 *
 * @package BraCalculator
 */

import { ref, computed } from 'vue';

const props = defineProps({
    component: {
        type: String,
        default: 'Dashboard',
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const currentComponent = computed(() => props.component);
</script>

<template>
    <div class="wpjarvis-vue-app">
        <template v-if="currentComponent === 'Dashboard'">
            <div class="wpjarvis-dashboard">
                <h2>WP Jarvis Dashboard</h2>
                <p>Welcome to your plugin dashboard!</p>
            </div>
        </template>

        <template v-else-if="currentComponent === 'Settings'">
            <div class="wpjarvis-settings">
                <h2>Settings</h2>
                <pre>{{ JSON.stringify(settings, null, 2) }}</pre>
            </div>
        </template>

        <template v-else>
            <div class="wpjarvis-unknown">
                <p>Unknown component: {{ currentComponent }}</p>
            </div>
        </template>
    </div>
</template>

<style scoped>
.wpjarvis-vue-app {
    padding: 20px;
}

.wpjarvis-dashboard h2,
.wpjarvis-settings h2 {
    margin-bottom: 10px;
}
</style>
