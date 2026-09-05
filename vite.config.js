import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/pages/auth-login.js',
                'resources/js/pages/auth-callback.js',
                'resources/js/pages/personal-information.js',
                'resources/js/pages/profile-photo.js',
                'resources/js/pages/profile-photo-history.js',
                'resources/js/pages/interests.js',
                'resources/js/pages/community-save.js',
                'resources/js/pages/community-like.js',
                'resources/js/pages/community-comment.js',
                'resources/js/pages/post-detail-modal.js',
                'resources/css/engagement.css',
                'resources/js/pages/engagement.js',
                'resources/js/pages/passport.js',
                'resources/css/albums.css',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
