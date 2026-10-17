import { createInertiaApp, type ResolvedComponent } from '@inertiajs/svelte';

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob<ResolvedComponent>('./pages/**/*.svelte', { eager: true });
        return pages[`./pages/${name}.svelte`];
    },
    progress: {
        color: '#12b886',
    },
});
