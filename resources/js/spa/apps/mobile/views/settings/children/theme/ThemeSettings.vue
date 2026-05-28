<template>
    <Toolbar v-on:close="$router.back()" v-bind:title="$t('settings.theme_settings')"></Toolbar>
    <SettingsDesc v-bind:text="$t('settings.forms.theme.page_desc')"></SettingsDesc>

    <div class="px-4">
        <RadioGroup v-model="defaultValue" v-on:update:modelValue="switchTheme" class="block space-y-3">
            <RadioGroupOption
                v-slot="{ checked }" 
                value="light"
                v-bind:class="[
                    'px-4 py-4 cursor-pointer text-par-m rounded-xl border transition-all duration-150',
                    checked ? 'border-brand-900/20 bg-brand-900/10' : 'border-bord-tr bg-fill-fv hover:border-fill-qt hover:bg-fill-fv'
                ]"
            >

                <div class="flex items-center">
                    <span class="inline-flex gap-3 items-center">
                        <span class="shrink-0">
                            <SvgIcon name="sun" type="solid" v-bind:classes="['size-icon-small', (checked ? 'text-brand-900' : 'text-lab-sc')].join(' ')"></SvgIcon>
                        </span>
                        <span v-bind:class="['text-par-s', (checked ? 'text-brand-900' : 'text-lab-sc')]">
                            {{ $t('settings.forms.theme.light') }}
                        </span>
                    </span>

                    <span class="ml-auto">
                        <SvgIcon v-if="checked" name="check-circle" type="solid" classes="size-icon-small text-brand-900"></SvgIcon>
                        <SvgIcon v-else name="placeholder" type="line" classes="size-icon-small text-lab-sc"></SvgIcon>
                    </span>
                </div>
            </RadioGroupOption>
            <RadioGroupOption
                v-slot="{ checked }" 
                value="dark"
                v-bind:class="[
                    'px-4 py-4 cursor-pointer text-par-m rounded-xl border transition-all duration-150',
                    checked ? 'border-brand-900/20 bg-brand-900/10' : 'border-bord-tr bg-fill-fv hover:border-fill-qt hover:bg-fill-fv'
                ]"
            >

                <div class="flex items-center">
                    <span class="inline-flex gap-3 items-center">
                        <span class="shrink-0">
                            <SvgIcon name="moon-02" type="solid" v-bind:classes="['size-icon-small', (checked ? 'text-brand-900' : 'text-lab-sc')].join(' ')"></SvgIcon>
                        </span>
                        <span v-bind:class="['text-par-s', (checked ? 'text-brand-900' : 'text-lab-sc')]">
                            {{ $t('settings.forms.theme.dark') }}
                        </span>
                    </span>

                    <span class="ml-auto">
                        <SvgIcon v-if="checked" name="check-circle" type="solid" classes="size-icon-small text-brand-900"></SvgIcon>
                        <SvgIcon v-else name="placeholder" type="line" classes="size-icon-small text-lab-sc"></SvgIcon>
                    </span>
                </div>
            </RadioGroupOption>
            <RadioGroupOption
                v-slot="{ checked }" 
                value="system"
                v-bind:class="[
                    'px-4 py-4 cursor-pointer text-par-m rounded-xl border transition-all duration-150',
                    checked ? 'border-brand-900/20 bg-brand-900/10' : 'border-bord-tr bg-fill-fv hover:border-fill-qt hover:bg-fill-fv'
                ]"
            >

                <div class="flex items-center">
                    <span class="inline-flex gap-3 items-center">
                        <span class="shrink-0">
                            <SvgIcon name="monitor-01" type="solid" v-bind:classes="['size-icon-small', (checked ? 'text-brand-900' : 'text-lab-sc')].join(' ')"></SvgIcon>
                        </span>
                        <span v-bind:class="['text-par-s', (checked ? 'text-brand-900' : 'text-lab-sc')]">
                            {{ $t('settings.forms.theme.system') }}
                        </span>
                    </span>

                    <span class="ml-auto">
                        <SvgIcon v-if="checked" name="check-circle" type="solid" classes="size-icon-small text-brand-900"></SvgIcon>
                        <SvgIcon v-else name="placeholder" type="line" classes="size-icon-small text-lab-sc"></SvgIcon>
                    </span>
                </div>
            </RadioGroupOption>
        </RadioGroup>
    </div>
</template>

<script>
    import { defineComponent, onMounted, ref } from 'vue';

    import { RadioGroup, RadioGroupOption } from '@headlessui/vue';
    import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';
    
    import Toolbar from '@M/components/layout/Toolbar.vue';
    import SettingsDesc from '@M/views/settings/parts/SettingsDesc.vue';

    export default defineComponent({
        setup: function() {
            const ALLOWED_THEMES = ['light', 'dark', 'system'];
            const currentTheme = ref('light');

            onMounted(() => {
                const storedTheme = localStorage.getItem('theme');
                currentTheme.value = ALLOWED_THEMES.includes(storedTheme) ? storedTheme : 'light';
            });

            const getSystemThemeMode = () => {
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }

            const getUiTheme = (theme) => {
                if(theme === 'system') {
                    return getSystemThemeMode();
                }

                return (theme === 'dark') ? 'dark' : 'light';
            };

            return {
                defaultValue: currentTheme,
                switchTheme: async (selectedTheme) => {
                    if(ALLOWED_THEMES.includes(selectedTheme)) {
                        currentTheme.value = selectedTheme;
                    }

                    const selected = ALLOWED_THEMES.includes(currentTheme.value) ? currentTheme.value : 'light';
                    const uiTheme = getUiTheme(selected);

                    localStorage.setItem('theme', selected);
                    document.documentElement.setAttribute('data-ui-theme', uiTheme);

                    try {
                        await ZESTEXAPI().userSettings().with({
                            theme: uiTheme
                        }).putTo('account/theme/update');

                        window.location.reload();
                    }
                    catch (error) {
                        console.error('Theme update failed:', error);
                    }
                }
            }
        },
        components: {
            Toolbar: Toolbar,
            SettingsDesc: SettingsDesc,
            RadioGroup: RadioGroup,
            RadioGroupOption: RadioGroupOption
        }
    });
</script>
