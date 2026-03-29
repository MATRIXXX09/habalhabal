import './bootstrap.js';
import './styles/app.css';
import './styles/landing-page.css';
import 'tw-elements';

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