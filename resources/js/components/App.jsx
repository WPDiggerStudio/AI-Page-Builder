/**
 * WP Jarvis - React App Component
 *
 * @package BraCalculator
 */

import React from 'react';

// Example components
const Dashboard = () => (
    <div className="wpjarvis-dashboard">
        <h2>WP Jarvis Dashboard</h2>
        <p>Welcome to your plugin dashboard!</p>
    </div>
);

const Settings = ({ settings }) => (
    <div className="wpjarvis-settings">
        <h2>Settings</h2>
        <pre>{JSON.stringify(settings, null, 2)}</pre>
    </div>
);

// Component registry
const components = {
    Dashboard,
    Settings,
};

/**
 * Main App component
 *
 * Dynamically renders the requested component.
 */
const App = ({ component, ...props }) => {
    const Component = components[component] || Dashboard;

    return (
        <div className="wpjarvis-react-app">
            <Component {...props} />
        </div>
    );
};

export default App;
