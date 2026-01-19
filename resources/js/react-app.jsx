/**
 * WP Jarvis - React Entry Point
 *
 * Example React app initialization for WordPress admin.
 *
 * @package BraCalculator
 */

import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './components/App';

// Find all React mount points
const mountPoints = document.querySelectorAll('[data-bra-calculator-react]');

mountPoints.forEach((element) => {
    const root = createRoot(element);
    const component = element.dataset.wpjarvisReact;
    const props = JSON.parse(element.dataset.props || '{}');

    root.render(
        <React.StrictMode>
            <App component={component} {...props} />
        </React.StrictMode>
    );
});

export { };
