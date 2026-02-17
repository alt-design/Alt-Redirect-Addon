import AltRedirect from './components/AltRedirect.vue';

Statamic.booting(() => {
    Statamic.$inertia.register('alt-redirect::Index', AltRedirect);
});
