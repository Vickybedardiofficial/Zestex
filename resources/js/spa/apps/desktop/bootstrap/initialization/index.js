/*
|------------------------------------------------------------------
| Desktop Bootstrap Initialization
|------------------------------------------------------------------
| This file is part of the pre initialization of the VueJS application.
| It prepares the framework before the actual application starts.
|
| @Author: (Vicky Bedardi Yadav)
*/

import '@/kernel/helpers/helpers.js';
import '@/kernel/helpers/javascript/index.js';

import axios from 'axios';

import '@/kernel/helpers/embeds/index.js';
import '@/kernel/websockets/index.js';
import '@D/core/global/global.js';

import { toastSuccess, toastError } from '@D/core/services/toasts/index.js';

window.toastSuccess = toastSuccess;
window.toastError = toastError;

const THEME_KEY = 'theme';
const ALLOWED_THEMES = ['light', 'dark', 'system'];

const getSystemTheme = () => {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
};

const resolveUiTheme = () => {
    const storedTheme = localStorage.getItem(THEME_KEY);
    const safeStoredTheme = ALLOWED_THEMES.includes(storedTheme) ? storedTheme : null;
    const htmlTheme = document.documentElement.getAttribute('data-ui-theme');
    const backendTheme = (window.BackendEmbeds?.theme === 'dark' || htmlTheme === 'dark') ? 'dark' : 'light';
    const selectedTheme = safeStoredTheme ?? backendTheme;

    return {
        selectedTheme: selectedTheme,
        uiTheme: selectedTheme === 'system' ? getSystemTheme() : selectedTheme
    };
};

const applyUiTheme = (uiTheme) => {
    const safeTheme = uiTheme === 'dark' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-ui-theme', safeTheme);
};

const themeState = resolveUiTheme();
applyUiTheme(themeState.uiTheme);

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (localStorage.getItem(THEME_KEY) === 'system') {
        applyUiTheme(getSystemTheme());
    }
});

window.HIDE_AUTHOR_ATTRIBUTION = import.meta.env.VITE_HIDE_AUTHOR_ATTRIBUTION;

axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
