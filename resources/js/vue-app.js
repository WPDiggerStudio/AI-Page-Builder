/**
 * WP Jarvis - Vue Entry Point
 *
 * Example Vue app initialization for WordPress admin.
 *
 * @package WpJarvis
 */

import { createApp } from 'vue';
import App from './components/App.vue';

// Find all Vue mount points
const mountPoints = document.querySelectorAll('[data-bracalulator-vue]');

mountPoints.forEach((element) => {
    const app = createApp(App, {
        component: element.dataset.wpjarvisVue,
        ...JSON.parse(element.dataset.props || '{}'),
    });

    app.mount(element);
});

export { };
