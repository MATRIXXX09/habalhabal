import './bootstrap.js';
import './styles/app.css';
//import './styles/landing-page.css';
import 'tw-elements';
import { io } from 'socket.io-client';

// Import tw-elements with correct path
//import 'tw-elements/dist/js/tw-elements.umd.min.js';

// Start Turbo
import * as Turbo from '@hotwired/turbo';
Turbo.start();

// Initialize Stimulus
import { startStimulusApp } from '@symfony/stimulus-bridge';
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
    
));

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

document.addEventListener('DOMContentLoaded', () => {
    const usersTable = document.getElementById('usersTable');
    if (!usersTable || typeof io !== 'function') {
        return;
    }

    const socket = io(window.location.origin, {
        path: '/socket.io',
        transports: ['websocket', 'polling'],
    });

    socket.on('connect', () => {
        console.log('[Socket.IO] connected', socket.id);
    });

    socket.on('admin:user:created', (user) => {
        console.log('[Socket.IO] admin:user:created', user);
        window.location.reload();
    });

    socket.on('connect_error', (error) => {
        console.warn('[Socket.IO] connect_error', error && error.message ? error.message : error);
    });
});