import { ref } from 'vue';

const isDark = ref(false);

function apply() {
    document.documentElement.classList.toggle('dark', isDark.value);
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
}

export function useDarkMode() {
    function init() {
        const stored = localStorage.getItem('theme');
        isDark.value = stored
            ? stored === 'dark'
            : window.matchMedia('(prefers-color-scheme: dark)').matches;
        apply();
    }

    function toggle() {
        isDark.value = !isDark.value;
        apply();
    }

    return { isDark, init, toggle };
}
