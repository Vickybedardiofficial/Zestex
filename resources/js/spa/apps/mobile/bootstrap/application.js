import '@M/bootstrap/initialization/index.js';

import { createApp, defineAsyncComponent } from 'vue';
import { createI18n } from 'vue-i18n';
import { createPinia } from 'pinia';

import outsideClickDirective from '@/kernel/vue/directives/click.outside.js';
import longPressDirective from '@/kernel/vue/directives/long.press.js';

import { postDeleteListener } from '@/kernel/vue/plugins/pinia/post/delete-listener.js';

import ZestexMobile from '@M/bootstrap/boot/ZestexMobile.vue';
import Router from '@M/router/index.js';

import globalProperties from '@/kernel/vue/plugins/global.properties.js';
import globalHelpers from '@M/core/global/global.helpers.js';
import LanguageMessages from '@/lang/index.js';

const Application = createApp(ZestexMobile);

Application.directive('outside-click', outsideClickDirective);
Application.directive('longpress', longPressDirective);

async function initializeI18n() {
    const messages = await LanguageMessages.messages();

    return createI18n({
        locale: LanguageMessages.langLocale,
        warnHtmlInMessage: false,
        warnHtmlMessage: false,
        legacy: false,
        fallbackLocale: LanguageMessages.langLocale,
        messages: {
            [LanguageMessages.langLocale]: messages
        }
    });
}

const ZestexI18n = await initializeI18n();

window.__t = ZestexI18n.global.t;

const PiniaInstance = createPinia();
PiniaInstance.use(postDeleteListener);

Application.use(globalProperties);
Application.use(PiniaInstance);
Application.use(Router);
Application.use(globalHelpers);
Application.use(ZestexI18n);

Application.component('Border', defineAsyncComponent(() => {
    return import("@/kernel/vue/components/general/Border.vue");
}));

Application.component('VerificationBadge', defineAsyncComponent(() => {
    return import("@/kernel/vue/components/general/badges/VerificationBadge.vue");
}));

Application.component('Name', defineAsyncComponent(() => {
    return import("@M/components/people/Name.vue");
}));

Application.component('SvgIcon', defineAsyncComponent(() => {
    return import("@/kernel/vue/components/icons/SvgIcon.vue");
}));

Application.component('PrimaryTransition', defineAsyncComponent(() => {
    return import("@M/components/general/transitions/PrimaryTransition.vue");
}));

Application.component('FileFormatIcon', defineAsyncComponent(() => {
    return import("@/kernel/vue/components/icons/FileFormatIcon.vue");
}));

Application.component('PrimaryDotsAnimation', defineAsyncComponent(() => {
    return import("@M/components/general/animations/PrimaryDotsAnimation.vue");
}));

Application.component('PrimarySpinAnimation', defineAsyncComponent(() => {
    return import("@M/components/general/animations/PrimarySpinAnimation.vue");
}));

Application.mount("#Zestex-mobile-app");
