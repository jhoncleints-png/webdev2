/*
 * This file is your main JS bootstrap file.
 * It initializes Stimulus controllers (if you use them) and any other JS libraries.
 */

import { startStimulusApp } from '@symfony/stimulus-bridge';

// Start the Stimulus application and load controllers from controllers.json
const app = startStimulusApp(require.context(
    './controllers',
    true,
    /\.js$/
));

// Optional: add global JS here
console.log('Brewery app initialized');

// If you use Turbo (from @hotwired/turbo), uncomment:
// import * as Turbo from '@hotwired/turbo';
// Turbo.start();
