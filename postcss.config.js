export default {
    plugins: {
        tailwindcss: {},
        autoprefixer: {},
        ...(process.env.NODE_ENV === 'production' ? {
            '@fullhuman/postcss-purgecss': {
                content: [
                    './resources/**/*.blade.php',
                    './resources/**/*.js',
                    './resources/**/*.vue',
                ],
                defaultExtractor: content => content.match(/[\w-/:]+(?<!:)/g) || [],
                safelist: [
                    'dark-theme',
                    'material-symbols-rounded',
                    /^prenda-card/,
                    /^metric-/,
                    /^empty-state/,
                    /^search-/,
                    /^btn-/,
                    /^page-/,
                    'container',
                    'main-content',
                    'sidebar',
                    'tableros-container',
                    'tableros-title'
                ]
            }
        } : {})
    },
};
