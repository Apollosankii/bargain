/*!
 * Bargain color mode
 * Dashboard/portal pages honor the stored theme.
 * Public/landing pages (data-force-dark="1") stay dark regardless of preference.
 */
(() => {
    'use strict'

    const html = document.documentElement
    const forceDark = () => html.getAttribute('data-force-dark') === '1'
    const getStoredTheme = () => localStorage.getItem('theme')
    const setStoredTheme = theme => localStorage.setItem('theme', theme)

    const getPreferredTheme = () => {
        if (forceDark()) return 'dark'
        const stored = getStoredTheme()
        if (stored === 'light' || stored === 'dark') return stored
        return 'dark'
    }

    const setTheme = theme => {
        const resolved = forceDark() ? 'dark' : theme
        html.setAttribute('data-bs-theme', resolved)
        html.setAttribute('data-portal-theme', resolved)
    }

    const updateToggle = theme => {
        const toggle = document.getElementById('theme-toggle')
        if (!toggle) return
        toggle.textContent = theme === 'dark' ? '☀' : '🌙'
        toggle.setAttribute(
            'aria-label',
            theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'
        )
    }

    setTheme(getPreferredTheme())

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (forceDark() || getStoredTheme()) return
        const theme = getPreferredTheme()
        setTheme(theme)
        updateToggle(theme)
    })

    document.addEventListener('DOMContentLoaded', () => {
        if (forceDark()) {
            setTheme('dark')
            return
        }

        updateToggle(getPreferredTheme())

        const toggle = document.getElementById('theme-toggle')
        if (toggle) {
            toggle.addEventListener('click', () => {
                if (forceDark()) return
                const newTheme = html.getAttribute('data-bs-theme') === 'dark'
                    ? 'light'
                    : 'dark'
                setStoredTheme(newTheme)
                setTheme(newTheme)
                updateToggle(newTheme)
            })
        }
    })
})()
