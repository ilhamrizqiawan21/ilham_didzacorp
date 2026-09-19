<?php

return [
    'testing' => [
        // Vue pages are validated by the Vite production build. Inertia's PHP
        // test view-finder cannot resolve .vue components without a compiled
        // frontend manifest, so component assertions should verify the page
        // name rather than filesystem existence.
        'ensure_pages_exist' => false,
    ],
];
