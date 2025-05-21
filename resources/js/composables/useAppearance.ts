import { onMounted, ref } from 'vue';

type Appearance = 'light' | 'dark' | 'system';

export function updateTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    // Always use light mode
    document.documentElement.classList.remove('dark');
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const mediaQuery = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};

// Function removed as we only support light mode now

const handleSystemThemeChange = () => {
    // Always use light mode regardless of system preference
    updateTheme();
};

export function initializeTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    // Always initialize with light theme
    updateTheme();

    // Set up system theme change listener (will always use light mode)
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

export function useAppearance() {
    // Always use light mode
    const appearance = ref<Appearance>('light');

    onMounted(() => {
        initializeTheme();
    });

    function updateAppearance() {
        // Always set to light mode
        appearance.value = 'light';

        // Store in localStorage for client-side persistence...
        localStorage.setItem('appearance', 'light');

        // Store in cookie for SSR...
        setCookie('appearance', 'light');

        // Apply light mode
        updateTheme();
    }

    return {
        appearance,
        updateAppearance,
    };
}
