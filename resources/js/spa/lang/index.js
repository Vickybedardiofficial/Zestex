import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';

const resolveLocale = () => {
    const savedLocale = localStorage.getItem('selected_locale');
    const availableLocales = Array.isArray(BackendEmbeds?.available_locales)
        ? BackendEmbeds.available_locales
        : [];

    if (savedLocale && availableLocales.includes(savedLocale)) {
        return savedLocale;
    }

    return BackendEmbeds.locale;
};

export default {
    langLocale: resolveLocale(),
    messages: async function () {
        try {
            return await ZESTEXAPI().translations().params({
                locale: this.langLocale
            }).getFrom('app').then((response) => {
                return response.data.data;
            });
        } catch (error) {
            console.error(`Could not load messages for locale: ${this.langLocale}`, error);
        }
    }
}
