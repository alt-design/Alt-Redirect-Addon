import AltRedirect from './components/AltRedirect.vue';

Statamic.booting(() => {
    Statamic.$components.register('alt-redirect', AltRedirect);
});
